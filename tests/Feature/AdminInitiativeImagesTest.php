<?php

use App\Models\Initiative;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The pictures of an initiative in the admin: gallery photos (the shared
 * component, with initiatives' sizes), the logo, the before-and-after pairs of a
 * rehabilitation, and the testimonies of a programme initiative. Files go to a
 * throwaway folder standing in for MEDIA_ROOT. Needs PHP's GD extension, as the
 * feature does; without it these are skipped.
 */

beforeEach(function () {
    $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($this->media.'/images');
    config(['hcsyria.media_root' => $this->media]);
});

afterEach(fn () => File::deleteDirectory($this->media));

function iphotos(Initiative $initiative): array
{
    return DB::table('initiative_images')->where('initiative_id', $initiative->id)->orderBy('image_order')->pluck('id')->all();
}

function ipick(int $count = 1, int $width = 3000, int $height = 2000): array
{
    return array_map(fn (int $i) => UploadedFile::fake()->image("photo-$i.jpg", $width, $height), range(1, $count));
}

/** A PNG with a see-through corner, as a logo often is. */
function seeThroughPng(int $size = 300): UploadedFile
{
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
    imagefilledrectangle($image, 100, 100, 200, 200, (int) imagecolorallocate($image, 190, 31, 36));
    ob_start();
    imagepng($image);

    return UploadedFile::fake()->createWithContent('logo.png', (string) ob_get_clean());
}

function component(string $name, string $role, array $params)
{
    return Livewire::actingAs(staff($role))->test($name, $params);
}

describe('with GD', function () {
    // ----------------------------------------------------------------- gallery

    it('cuts a gallery upload to the initiatives\' two sizes, numbers it, and leaves the initiative online', function () {
        $initiative = adminInitiative(['online' => 1]);
        $user = staff('Photos Editor');

        Livewire::actingAs($user)->test('admin.content-photos', ['kind' => 'initiative', 'itemId' => $initiative->id])
            ->set('uploads', ipick(2))
            ->assertDispatched('photos-changed')
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success' && str_contains($params['message'], "initiative #{$initiative->id}"));

        $rows = DB::table('initiative_images')->where('initiative_id', $initiative->id)->orderBy('image_order')->get();
        expect($rows)->toHaveCount(2)->and($rows->pluck('image_order')->all())->toBe([1, 2]);

        foreach ($rows as $row) {
            expect($row->path)->toStartWith('/images/initiatives/Social activities/'.$initiative->id.'/');
            expect(array_slice(getimagesize($this->media.$row->path), 0, 2))->toBe([720, 480])
                ->and(array_slice(getimagesize($this->media.PhotoFiles::bigPath($row->path)), 0, 2))->toBe([2000, 1333]);
        }

        expect($initiative->refresh()->online)->toBe(1)
            ->and(initiativeLogs($initiative)->where(['operation' => '2 Images stored', 'user_id' => $user->id])->exists())->toBeTrue();
    });

    it('lets only the roles that manage photos change a gallery', function (string $role, bool $may) {
        $initiative = adminInitiative();

        $page = component('admin.content-photos', $role, ['kind' => 'initiative', 'itemId' => $initiative->id])->set('uploads', ipick(1));
        $may ? $page->assertOk() : $page->assertForbidden();

        expect(count(iphotos($initiative)))->toBe($may ? 1 : 0);
    })->with([['Admin', true], ['Photos Editor', true], ['Publisher', true], ['English Editor', false], ['English Checker', false], ['Viewer', false]]);

    it('removes both files, closes the gap, and reorders', function () {
        $initiative = adminInitiative();
        $page = component('admin.content-photos', 'Admin', ['kind' => 'initiative', 'itemId' => $initiative->id])->set('uploads', ipick(3));
        [$a, $b, $c] = iphotos($initiative);
        $removed = DB::table('initiative_images')->where('id', $b)->value('path');

        $page->call('remove', $b);
        expect(iphotos($initiative))->toBe([$a, $c])
            ->and(File::exists($this->media.$removed))->toBeFalse()
            ->and(File::exists($this->media.PhotoFiles::bigPath($removed)))->toBeFalse()
            ->and(initiativeLogs($initiative)->where('operation', 'Image Deleted')->exists())->toBeTrue();

        $page->call('makeCover', $c);
        expect(iphotos($initiative))->toBe([$c, $a]);
        $page->call('move', $c, 1);
        expect(iphotos($initiative))->toBe([$a, $c]);
    });

    // -------------------------------------------------------------------- logo

    it('stores a logo, keeps its see-through parts, and replaces it by deleting the old file', function () {
        $initiative = adminInitiative();
        $user = staff('English Editor');
        $page = Livewire::actingAs($user)->test('admin.initiative-logo', ['initiativeId' => $initiative->id]);

        $page->set('logo', seeThroughPng())->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');

        $first = $initiative->refresh()->logo;
        expect($first)->toStartWith('/images/initiatives/Social activities/')->toEndWith('.png')
            ->and(imagecolorsforindex($img = imagecreatefrompng($this->media.$first), imagecolorat($img, 5, 5))['alpha'])->toBe(127)
            ->and(initiativeLogs($initiative)->where(['operation' => 'Logo changed', 'user_id' => $user->id])->exists())->toBeTrue();

        $page->set('logo', ipick(1, 900, 600)[0]);
        $second = $initiative->refresh()->logo;
        expect($second)->not->toBe($first)->toEndWith('.jpg')
            ->and(File::exists($this->media.$first))->toBeFalse()
            ->and(File::exists($this->media.$second))->toBeTrue();
    });

    it('removes a logo and its file', function () {
        $initiative = adminInitiative();
        $page = component('admin.initiative-logo', 'Admin', ['initiativeId' => $initiative->id])->set('logo', seeThroughPng());
        $path = $initiative->refresh()->logo;

        $page->call('remove');

        expect($initiative->refresh()->logo)->toBe('0')->and(File::exists($this->media.$path))->toBeFalse()
            ->and(initiativeLogs($initiative)->where('operation', 'Logo removed')->exists())->toBeTrue();
    });

    it('lets the roles that edit change the logo, and nobody else', function (string $role, bool $may) {
        $initiative = adminInitiative();

        $page = component('admin.initiative-logo', $role, ['initiativeId' => $initiative->id])->set('logo', seeThroughPng());
        $may ? $page->assertOk() : $page->assertForbidden();

        expect($initiative->refresh()->logo)->toBe($may ? $initiative->logo : '0');
        expect($may)->toBe($initiative->logo !== '0');
    })->with([['Admin', true], ['English Editor', true], ['Photos Editor', false], ['Publisher', false], ['Viewer', false]]);

    it('refuses a logo that is not a picture', function () {
        $initiative = adminInitiative();

        component('admin.initiative-logo', 'Admin', ['initiativeId' => $initiative->id])
            ->set('logo', UploadedFile::fake()->createWithContent('logo.png', '<?php echo 1;'))
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

        expect($initiative->refresh()->logo)->toBe('0');
    });

    // ---------------------------------------------------------- before and after

    it('adds a before-and-after pair cut to one size, and removes it with both files', function () {
        $initiative = adminInitiative(['what_category_id' => 3]);
        $page = component('admin.before-after', 'Photos Editor', ['initiativeId' => $initiative->id]);

        $page->set('before', ipick(1, 2000, 1000)[0])->set('after', ipick(1, 800, 900)[0])->call('add')
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');

        $pair = DB::table('initiative_rehabilitation_images')->where('initiative_id', $initiative->id)->first();
        expect($pair->path_1)->toEndWith('before.jpg')->and($pair->path_2)->toEndWith('after.jpg')
            ->and(array_slice(getimagesize($this->media.$pair->path_1), 0, 2))->toBe([1200, 800])
            ->and(array_slice(getimagesize($this->media.$pair->path_2), 0, 2))->toBe([1200, 800])
            ->and(initiativeLogs($initiative)->where('operation', 'Before & After Images stored')->exists())->toBeTrue();

        $page->call('remove', $pair->id);
        expect(DB::table('initiative_rehabilitation_images')->where('initiative_id', $initiative->id)->exists())->toBeFalse()
            ->and(File::exists($this->media.$pair->path_1))->toBeFalse()->and(File::exists($this->media.$pair->path_2))->toBeFalse();
    });

    it('needs both pictures of a pair, and only lets photo roles add one', function () {
        $initiative = adminInitiative(['what_category_id' => 3]);

        component('admin.before-after', 'Admin', ['initiativeId' => $initiative->id])->set('before', ipick(1)[0])->call('add');
        component('admin.before-after', 'Viewer', ['initiativeId' => $initiative->id])->set('before', ipick(1)[0])->set('after', ipick(1)[0])->call('add')->assertForbidden();

        expect(DB::table('initiative_rehabilitation_images')->where('initiative_id', $initiative->id)->exists())->toBeFalse();
    });

    // -------------------------------------------------------------- testimonies

    it('adds, edits and removes a testimony of a programme initiative, with legacy\'s log text', function () {
        $initiative = adminInitiative(['what_category_id' => 7]);
        $user = staff('English Editor');
        $page = Livewire::actingAs($user)->test('admin.initiative-testimonies', ['initiativeId' => $initiative->id]);

        $page->call('add')->set('name', 'Lina')->set('text', 'It changed my year.')->set('picture', ipick(1, 800, 800)[0])->call('save')
            ->assertHasNoErrors()->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');

        $row = DB::table('testimonies')->where('initiative_id', $initiative->id)->first();
        expect($row->image_path)->toStartWith('/images/initiatives/Study Zone/')->and(strlen($row->image_path))->toBeLessThanOrEqual(80)
            ->and(array_slice(getimagesize($this->media.$row->image_path), 0, 2))->toBe([400, 400])
            ->and(DB::table('testimony_contents')->where('testimony_id', $row->id)->value('name'))->toBe('Lina');

        $page->call('edit', $row->id)->assertSet('name', 'Lina')->set('text', 'It changed my whole year.')->call('save');
        expect(initiativeLogs($initiative)->orderBy('id')->pluck('operation')->all())->toBe(['Testimony Created', 'Testimony text edited']);

        $page->call('remove', $row->id);
        expect(DB::table('testimonies')->where('initiative_id', $initiative->id)->exists())->toBeFalse()
            ->and(DB::table('testimony_contents')->where('testimony_id', $row->id)->exists())->toBeFalse()
            ->and(File::exists($this->media.$row->image_path))->toBeFalse()
            ->and(initiativeLogs($initiative)->where('operation', 'Testimony deleted')->exists())->toBeTrue();
    });

    it('needs a name, words and a picture for a new testimony', function () {
        $initiative = adminInitiative(['what_category_id' => 5]);

        component('admin.initiative-testimonies', 'Admin', ['initiativeId' => $initiative->id])
            ->call('add')->set('name', '')->set('text', '')->call('save')->assertHasErrors(['name', 'text'])
            ->set('name', 'Lina')->set('text', 'Words')->call('save')->assertHasErrors('picture');

        expect(DB::table('testimonies')->where('initiative_id', $initiative->id)->exists())->toBeFalse();
    });

    it('only lets the roles that edit change testimonies', function () {
        $initiative = adminInitiative(['what_category_id' => 7]);

        component('admin.initiative-testimonies', 'Viewer', ['initiativeId' => $initiative->id])->call('add')->assertForbidden();
        component('admin.initiative-testimonies', 'Photos Editor', ['initiativeId' => $initiative->id])->call('add')->assertForbidden();
    });

    it('has no testimonies on an initiative of another category', function () {
        $initiative = adminInitiative();

        component('admin.initiative-testimonies', 'Admin', ['initiativeId' => $initiative->id])->assertNotFound();
    });

    // -------------------------------------------------------------- the pages

    it('shows each panel where it applies', function () {
        $social = adminInitiative();
        $rehab = adminInitiative(['what_category_id' => 3]);
        $study = adminInitiative(['what_category_id' => 7]);
        $admin = staff('Admin');

        $this->actingAs($admin)->get("/admin/initiatives/{$social->id}")->assertSee('Logo')->assertSee('Photos')->assertDontSee('Before and after')->assertDontSee('Add a testimony');
        $this->actingAs($admin)->get("/admin/initiatives/{$rehab->id}")->assertSee('Before and after');
        $this->actingAs($admin)->get("/admin/initiatives/{$study->id}")->assertSee('Add a testimony');
    });

    it('deletes every file of a deleted initiative', function () {
        $initiative = adminInitiative(['what_category_id' => 3]);
        $admin = staff('Admin');
        component('admin.content-photos', 'Admin', ['kind' => 'initiative', 'itemId' => $initiative->id])->set('uploads', ipick(1));
        component('admin.initiative-logo', 'Admin', ['initiativeId' => $initiative->id])->set('logo', seeThroughPng());
        component('admin.before-after', 'Admin', ['initiativeId' => $initiative->id])->set('before', ipick(1)[0])->set('after', ipick(1)[0])->call('add');

        expect(collect(File::allFiles($this->media.'/images'))->count())->toBeGreaterThanOrEqual(5);

        Livewire::actingAs($admin)->test('pages::admin.initiatives.index')->call('deleteItem', $initiative->id);

        expect(Initiative::query()->whereKey($initiative->id)->exists())->toBeFalse()
            ->and(collect(File::allFiles($this->media.'/images'))->count())->toBe(0);
    });
})->skip(! extension_loaded('gd'), 'The GD extension is not enabled.');
