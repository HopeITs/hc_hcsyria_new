<?php

use App\Actions\Admin\SetNewsPriority;
use App\Models\News;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The pictures of a newsletter item in the admin: the thumbnail, Important and Pinned
 * pictures and the item's status (Regular, Important, Pinned), and the gallery (the shared
 * content-photos component with the news item's sizes). Files go to a throwaway folder
 * standing in for MEDIA_ROOT. Needs PHP's GD extension, as the feature does; without it
 * these are skipped.
 */

beforeEach(function () {
    $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($this->media.'/images');
    config(['hcsyria.media_root' => $this->media]);
});

afterEach(fn () => File::deleteDirectory($this->media));

function newsGallery(News $news): array
{
    return DB::table('news_images')->where('news_id', $news->id)->where('sizetype', 3)->orderBy('image_order')->pluck('id')->all();
}

function newsSingle(News $news, int $sizetype): ?string
{
    return DB::table('news_images')->where('news_id', $news->id)->where('sizetype', $sizetype)->value('path');
}

function jpg(int $width = 3000, int $height = 2000): UploadedFile
{
    return UploadedFile::fake()->image('picture.jpg', $width, $height);
}

function pictures(string $role, News $news)
{
    return Livewire::actingAs(staff($role))->test('admin.news-pictures', ['newsId' => $news->id]);
}

function size(string $media, string $path): array
{
    return array_slice(getimagesize($media.$path), 0, 2);
}

describe('with GD', function () {
    // ----------------------------------------------------------------- gallery

    it('cuts a gallery upload to both sizes in the item\'s folder, numbers it apart from the single pictures, and leaves the item online', function () {
        $news = adminNews(['online' => 1]);
        $user = staff('Photos Editor');
        pictures('Admin', $news)->set('thumbnail', jpg());

        Livewire::actingAs($user)->test('admin.content-photos', ['kind' => 'news', 'itemId' => $news->id])
            ->set('uploads', [jpg(), jpg()])
            ->assertDispatched('photos-changed')
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success' && str_contains($params['message'], "news #{$news->id}"));

        $rows = DB::table('news_images')->where('news_id', $news->id)->where('sizetype', 3)->orderBy('image_order')->get();
        expect($rows)->toHaveCount(2)->and($rows->pluck('image_order')->all())->toBe([1, 2]);

        foreach ($rows as $row) {
            expect($row->path)->toStartWith("/images/news/{$news->id}/")
                ->and(size($this->media, $row->path))->toBe([920, 614])
                ->and(size($this->media, PhotoFiles::bigPath($row->path)))->toBe([2080, 1387]);
        }

        expect($news->refresh()->online)->toBe(1)
            ->and(newsLogs($news)->where(['operation' => '2 Images stored', 'user_id' => $user->id])->exists())->toBeTrue()
            ->and(DB::table('news_images')->where('news_id', $news->id)->where('sizetype', 0)->count())->toBe(1);
    });

    it('removes a gallery photo with both files and reorders, without touching the single pictures', function () {
        $news = adminNews();
        pictures('Admin', $news)->set('thumbnail', jpg());
        $thumbnail = newsSingle($news, 0);
        $page = Livewire::actingAs(staff('Admin'))->test('admin.content-photos', ['kind' => 'news', 'itemId' => $news->id])->set('uploads', [jpg(), jpg(), jpg()]);
        [$a, $b, $c] = newsGallery($news);
        $removed = DB::table('news_images')->where('id', $b)->value('path');

        $page->call('remove', $b);
        expect(newsGallery($news))->toBe([$a, $c])
            ->and(File::exists($this->media.$removed))->toBeFalse()->and(File::exists($this->media.PhotoFiles::bigPath($removed)))->toBeFalse()
            ->and(File::exists($this->media.$thumbnail))->toBeTrue();

        $page->call('makeCover', $c);
        expect(newsGallery($news))->toBe([$c, $a]);

        $page->call('remove', DB::table('news_images')->where('news_id', $news->id)->where('sizetype', 0)->value('id'));
        expect(newsSingle($news, 0))->toBe($thumbnail);
    });

    // ---------------------------------------------------------- single pictures

    it('cuts each single picture to its card\'s proportions, and a new one replaces the old', function (int $sizetype, string $property, array $expected, string $ending) {
        $news = adminNews();
        $user = staff('Photos Editor');
        $page = Livewire::actingAs($user)->test('admin.news-pictures', ['newsId' => $news->id]);

        $page->set($property, jpg(2000, 2000))->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');
        $first = newsSingle($news, $sizetype);
        expect($first)->toStartWith("/images/news/{$news->id}/")->toEndWith($ending)->and(size($this->media, $first))->toBe($expected);

        $page->set($property, jpg(1000, 3000));
        $second = newsSingle($news, $sizetype);
        expect($second)->not->toBe($first)->and(File::exists($this->media.$first))->toBeFalse()->and(File::exists($this->media.$second))->toBeTrue()
            ->and(DB::table('news_images')->where('news_id', $news->id)->where('sizetype', $sizetype)->count())->toBe(1)
            ->and(newsLogs($news)->where(['operation' => '1 Images stored', 'user_id' => $user->id])->count())->toBe(2);
    })->with([
        'thumbnail' => [0, 'thumbnail', [600, 600], '_thumb.jpg'],
        'important' => [1, 'important', [1200, 800], '_important.jpg'],
        'pinned' => [2, 'pinned', [1200, 600], '_pinned.jpg'],
    ]);

    it('refuses a file that only says it is a picture', function () {
        $news = adminNews();

        pictures('Admin', $news)->set('thumbnail', UploadedFile::fake()->createWithContent('t.jpg', '<?php echo 1;'))
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

        expect(newsSingle($news, 0))->toBeNull();
    });

    it('lets only the roles that manage photos change the pictures and the status', function (string $role, bool $may) {
        $news = adminNews();

        $page = pictures($role, $news)->set('thumbnail', jpg());
        $may ? $page->assertOk() : $page->assertForbidden();
        $status = pictures($role, $news)->call('setPriority', 2);
        $may ? $status->assertOk() : $status->assertForbidden();

        expect(newsSingle($news, 0) !== null)->toBe($may)->and((int) $news->refresh()->priority_status)->toBe($may ? 2 : 0);
    })->with([['Admin', true], ['Photos Editor', true], ['Publisher', true], ['English Editor', false], ['English Checker', false], ['Viewer', false]]);

    // ------------------------------------------------------------------ status

    it('removing the Important picture makes an Important item Regular again, and removing another picture does not', function () {
        $news = adminNews(['priority_status' => 1]);
        $page = pictures('Admin', $news)->set('important', jpg())->set('thumbnail', jpg());

        $page->call('remove', 0);
        expect((int) $news->refresh()->priority_status)->toBe(1);

        $page->call('remove', 1);
        expect((int) $news->refresh()->priority_status)->toBe(0)
            ->and(newsSingle($news, 1))->toBeNull()
            ->and(newsLogs($news)->where(['operation' => 'News Priority changed', 'previous_data' => 'Important', 'current_data' => 'Regular'])->exists())->toBeTrue();
    });

    it('removing the Pinned picture makes a Pinned item Regular again', function () {
        $news = adminNews(['priority_status' => 2]);

        pictures('Admin', $news)->set('pinned', jpg())->call('remove', 2);

        expect((int) $news->refresh()->priority_status)->toBe(0);
    });

    it('deletes every file of a deleted item', function () {
        $news = adminNews();
        Livewire::actingAs(staff('Admin'))->test('admin.content-photos', ['kind' => 'news', 'itemId' => $news->id])->set('uploads', [jpg()]);
        pictures('Admin', $news)->set('thumbnail', jpg())->set('important', jpg())->set('pinned', jpg());

        expect(collect(File::allFiles($this->media.'/images'))->count())->toBe(5);

        Livewire::actingAs(staff('Admin'))->test('pages::admin.news.index')->call('deleteItem', $news->id);

        expect(News::query()->whereKey($news->id)->exists())->toBeFalse()->and(collect(File::allFiles($this->media.'/images'))->count())->toBe(0);
    });
})->skip(! extension_loaded('gd'), 'The GD extension is not enabled.');

// ------------------------------------------------------------------ the status

it('changes the status and logs it with the old and the new one', function () {
    $news = adminNews();
    $user = staff('Photos Editor');

    Livewire::actingAs($user)->test('admin.news-pictures', ['newsId' => $news->id])
        ->call('setPriority', 2)->assertDispatched('admin-alert', fn (string $event, array $params): bool => str_contains($params['message'], 'now Pinned'))
        ->call('setPriority', 2)
        ->call('setPriority', 0);

    expect((int) $news->refresh()->priority_status)->toBe(0)
        ->and(newsLogs($news)->where('operation', 'News Priority changed')->orderBy('id')->get()->map(fn ($row) => $row->previous_data.'>'.$row->current_data)->all())->toBe(['Regular>Pinned', 'Pinned>Regular']);
});

it('keeps one Important item online: making an online item Important makes the old one regular', function () {
    $old = adminNews(['online' => 1, 'priority_status' => 1]);
    $new = adminNews(['online' => 1]);
    $offline = adminNews(['priority_status' => 0]);

    Livewire::actingAs(staff('Admin'))->test('admin.news-pictures', ['newsId' => $new->id])->call('setPriority', 1);

    expect((int) $new->refresh()->priority_status)->toBe(1)->and((int) $old->refresh()->priority_status)->toBe(0)
        ->and(newsLogs($old)->where('operation', "Important status replaced by News #{$new->id}")->exists())->toBeTrue();

    // An item that is not online yet does not push the Important one aside until it is published.
    Livewire::actingAs(staff('Admin'))->test('admin.news-pictures', ['newsId' => $offline->id])->call('setPriority', 1);
    expect((int) $new->refresh()->priority_status)->toBe(1)->and((int) $offline->refresh()->priority_status)->toBe(1);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.news.index')->call('moveItem', $offline->id, 'publish');
    expect((int) $new->refresh()->priority_status)->toBe(0)->and((int) $offline->refresh()->priority_status)->toBe(1);
});

it('refuses a status that is not one', function () {
    $news = adminNews();

    expect(fn () => Livewire::actingAs(staff('Admin'))->test('admin.news-pictures', ['newsId' => $news->id])->call('setPriority', 7))->toThrow(InvalidArgumentException::class);
    expect(SetNewsPriority::label(1))->toBe('Important')->and(SetNewsPriority::label(9))->toBe('Regular');
});

it('shows the panels on the item\'s page, and only the buttons the role may use', function () {
    $news = adminNews();

    $this->actingAs(staff('Admin'))->get("/admin/news/{$news->id}")->assertSee('Cover pictures and status')->assertSee('Photos')->assertSee('Add photos')->assertSee('Thumbnail');
    pictures('Viewer', $news)->assertSee('Status on the news page')->assertDontSee('Replace')->assertDontSee('Add');
});
