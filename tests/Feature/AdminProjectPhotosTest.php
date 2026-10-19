<?php

use App\Models\Image;
use App\Models\Log;
use App\Models\Project;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The photos of a micro-project in the admin: adding (checked by SecureUpload,
 * re-encoded to both file sizes), ordering, removing, and who may. Files go to a
 * throwaway folder standing in for MEDIA_ROOT. Needs PHP's GD extension, as the
 * feature does; without it these are skipped.
 */

beforeEach(function () {
    $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($this->media.'/images');
    config(['hcsyria.media_root' => $this->media]);
});

afterEach(fn () => File::deleteDirectory($this->media));

function photos(Project $project): array
{
    return Image::query()->where('project_id', $project->id)->orderBy('image_order')->pluck('id')->all();
}

function pick(int $count = 1, int $width = 2400, int $height = 1800): array
{
    return array_map(fn (int $i) => UploadedFile::fake()->image("photo-$i.jpg", $width, $height), range(1, $count));
}

function panel(string $role, Project $project, array $cities = [])
{
    return Livewire::actingAs(staff($role, $cities))->test('admin.content-photos', ['kind' => 'project', 'itemId' => $project->id]);
}

describe('with GD', function () {
    it('cuts one upload to both sizes, writes both files and numbers the photos', function () {
        $project = adminProject(photos: 0);
        $user = staff('Photos Editor');

        Livewire::actingAs($user)->test('admin.content-photos', ['kind' => 'project', 'itemId' => $project->id])
            ->set('uploads', pick(2))
            ->assertDispatched('photos-changed')
            ->assertDispatched('admin-alert', alertOf('success'));

        $images = Image::query()->where('project_id', $project->id)->orderBy('image_order')->get();
        expect($images)->toHaveCount(2)
            ->and($images->pluck('image_order')->all())->toBe([1, 2]);

        foreach ($images as $image) {
            expect($image->MPI_Path)->toStartWith('/images/projects/'.$project->MicroProject_ID.'/')->toEndWith('.jpg');
            [$smallWidth, $smallHeight] = getimagesize($this->media.$image->MPI_Path);
            [$bigWidth, $bigHeight] = getimagesize($this->media.PhotoFiles::bigPath($image->MPI_Path));
            expect([$smallWidth, $smallHeight])->toBe([630, 420])
                ->and([$bigWidth, $bigHeight])->toBe([1440, 1080]);
        }

        expect(Log::query()->where(['project_id' => $project->id, 'category' => 0, 'operation' => '2 Images stored', 'user_id' => $user->id])->exists())->toBeTrue();
    });

    it('never makes a picture bigger than it was', function () {
        $project = adminProject(photos: 0);

        panel('Photos Editor', $project)->set('uploads', pick(1, 400, 300));

        $image = Image::query()->where('project_id', $project->id)->firstOrFail();
        expect(getimagesize($this->media.PhotoFiles::bigPath($image->MPI_Path))[0])->toBe(400);
    });

    it('sends an online project back to the pending list when its photos change, as legacy did', function () {
        $project = adminProject(['MPE_Published' => 1], 1);

        panel('Photos Editor', $project)->set('uploads', pick(1))
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => str_contains($params['message'], 'pending list'));

        expect($project->refresh()->MPE_Published)->toBe('0')
            ->and(Log::query()->where(['project_id' => $project->id, 'category' => 0, 'operation' => 'Made Offline'])->exists())->toBeTrue();
    });

    it('refuses a file that only says it is a picture, and stores nothing', function () {
        $project = adminProject(photos: 0);
        $fake = UploadedFile::fake()->createWithContent('holiday.jpg', '<?php echo "not a picture";');

        panel('Photos Editor', $project)->set('uploads', [$fake])
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'not a JPEG, PNG or WebP'));

        expect(photos($project))->toBe([])->and(File::exists($this->media.'/images/projects'))->toBeFalse();
    });

    it('refuses a picture larger than the limit', function () {
        $project = adminProject(photos: 0);

        panel('Photos Editor', $project)->set('uploads', [UploadedFile::fake()->image('big.jpg', 600, 400)->size(9000)])
            ->assertDispatched('admin-alert', alertOf('error'));

        expect(photos($project))->toBe([]);
    });

    it('lets only the roles that manage photos change them', function (string $role, bool $may) {
        $project = adminProject(photos: 1);

        $page = panel($role, $project)->set('uploads', pick(1));
        $may ? $page->assertOk() : $page->assertForbidden();

        expect(count(photos($project)))->toBe($may ? 2 : 1);
    })->with([['Admin', true], ['Photos Editor', true], ['Publisher', true], ['English Editor', false], ['English Checker', false], ['Viewer', false]]);

    it('keeps an account to its own cities', function () {
        $homs = adminProject(['MPE_City' => 1], 1);

        $this->actingAs(staff('Photos Editor', [3]))->get("/admin/projects/{$homs->id}")->assertForbidden();
        expect(count(photos($homs)))->toBe(1);
    });

    it('puts a project\'s photos in its city\'s folder', function () {
        $project = adminProject(['MPE_City' => 1], 0);

        panel('Admin', $project)->set('uploads', pick(1));

        expect(Image::query()->where('project_id', $project->id)->value('MPI_Path'))->toStartWith('/images/projects_homs/'.$project->MicroProject_ID.'/');
    });

    it('removes both files and closes the gap in the order', function () {
        $project = adminProject(photos: 0);
        $page = panel('Admin', $project)->set('uploads', pick(3));
        [$first, $second, $third] = photos($project);
        $removed = Image::query()->find($second)->MPI_Path;

        $page->call('remove', $second)->assertDispatched('admin-alert', alertOf('success'));

        expect(photos($project))->toBe([$first, $third])
            ->and(Image::query()->whereIn('id', [$first, $third])->orderBy('image_order')->pluck('image_order')->all())->toBe([1, 2])
            ->and(File::exists($this->media.$removed))->toBeFalse()
            ->and(File::exists($this->media.PhotoFiles::bigPath($removed)))->toBeFalse()
            ->and(Log::query()->where(['project_id' => $project->id, 'category' => 0, 'operation' => 'Image Deleted'])->exists())->toBeTrue();
    });

    it('will not remove a photo that belongs to another project', function () {
        $mine = adminProject(photos: 0);
        $other = adminProject(photos: 1);
        $foreign = photos($other)[0];

        panel('Admin', $mine)->call('remove', $foreign);

        expect(photos($other))->toBe([$foreign]);
    });

    it('moves a photo earlier or later, and makes any photo the cover', function () {
        $project = adminProject(photos: 0);
        $page = panel('Admin', $project)->set('uploads', pick(3));
        [$a, $b, $c] = photos($project);

        $page->call('move', $c, -1);
        expect(photos($project))->toBe([$a, $c, $b]);

        $page->call('move', $a, -1)->call('move', $b, 1);
        expect(photos($project))->toBe([$a, $c, $b]);

        $page->call('makeCover', $b);
        expect(photos($project))->toBe([$b, $a, $c]);
    });

    it('removes the files of a deleted project from MEDIA_ROOT', function () {
        $project = adminProject(photos: 0);
        panel('Admin', $project)->set('uploads', pick(1));
        $path = Image::query()->where('project_id', $project->id)->value('MPI_Path');
        expect(File::exists($this->media.$path))->toBeTrue();

        Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.index')->call('deleteItem', $project->id);

        expect(File::exists($this->media.$path))->toBeFalse()->and(File::exists($this->media.PhotoFiles::bigPath($path)))->toBeFalse();
    });
})->skip(! extension_loaded('gd'), 'The GD extension is not enabled.');

it('leaves alone any file outside the images folder, whatever a stored path says', function () {
    file_put_contents($this->media.'/secret.txt', 'keep');
    file_put_contents($this->media.'/images/inside.jpg', 'x');

    expect(PhotoFiles::delete('/images/../secret.txt'))->toBe(0)
        ->and(PhotoFiles::delete('/etc/passwd'))->toBe(0)
        ->and(File::exists($this->media.'/secret.txt'))->toBeTrue()
        ->and(PhotoFiles::delete('/images/inside.jpg'))->toBe(1)
        ->and(File::exists($this->media.'/images/inside.jpg'))->toBeFalse();
});

it('says why it cannot take a picture when the image extension is missing', function () {
    $project = adminProject(photos: 0);

    Livewire::actingAs(staff('Admin'))->test('admin.content-photos', ['kind' => 'project', 'itemId' => $project->id])
        ->set('uploads', [UploadedFile::fake()->create('a.jpg', 10)])
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => str_contains($params['message'], 'GD'));
})->skip(extension_loaded('gd'), 'GD is enabled here.');

it('shows the photos to someone who can only look, without the buttons', function () {
    $project = adminProject(photos: 2);

    panel('Viewer', $project)->assertSee('Photos')->assertDontSee('Add photos')->assertDontSee('Remove this photo');
    panel('Photos Editor', $project)->assertSee('Add photos')->assertSee('Remove this photo');
});
