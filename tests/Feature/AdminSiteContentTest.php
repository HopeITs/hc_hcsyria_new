<?php

use App\Support\Site\SiteContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * Site content in the admin: the pictures, film and wording of the public pages that
 * staff change instead of the code. A slot nobody touched shows its original; a change
 * shows on the public page; Reset brings the original back and deletes the file.
 */

beforeEach(function () {
    $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($this->media.'/images');
    config(['hcsyria.media_root' => $this->media]);
});

afterEach(fn () => File::deleteDirectory($this->media));

function siteEditor(string $role = 'Admin')
{
    return Livewire::actingAs(staff($role))->test('pages::admin.site');
}

it('lets Admin, Publisher and Photos Editor open it, and nobody else', function (string $role, bool $may) {
    $this->actingAs(staff($role))->get('/admin/site')->assertStatus($may ? 200 : 403);
})->with([['Admin', true], ['Publisher', true], ['Photos Editor', true], ['English Editor', false], ['Viewer', false]]);

it('shows the original until a slot is changed', function () {
    expect(SiteContent::image('home.cover_desktop'))->toBe('/images/home/roots-cover-desktop.jpg')
        ->and(SiteContent::text('home.leap_text'))->toStartWith('The LEAP is designed');
});

it('changes a paragraph, shows it on the public page, and goes back', function () {
    siteEditor()->set('texts.home__leap_text', 'A new line about LEAP.')->call('saveText', 'home__leap_text')->assertDispatched('admin-alert');

    expect(SiteContent::text('home.leap_text'))->toBe('A new line about LEAP.');
    $this->get('/home')->assertSee('A new line about LEAP.');

    siteEditor()->call('reset_slot', 'home__leap_text');

    expect(SiteContent::text('home.leap_text'))->toStartWith('The LEAP is designed');
    expect(DB::table('site_contents')->where('slot', 'home.leap_text')->exists())->toBeFalse();
});

it('strips markup from a paragraph and refuses an empty or huge one', function () {
    siteEditor()->set('texts.home__leap_text', '<script>alert(1)</script>Safe words')->call('saveText', 'home__leap_text');
    expect(SiteContent::text('home.leap_text'))->toBe('alert(1)Safe words');

    siteEditor()->set('texts.home__disc_text', '   ')->call('saveText', 'home__disc_text')->assertDispatched('admin-alert', type: 'error');
    expect(DB::table('site_contents')->where('slot', 'home.disc_text')->exists())->toBeFalse();
});

it('treats writing the original words as no change', function () {
    $original = config('site_content.pages.home.slots.disc_text.default');
    siteEditor()->set('texts.home__disc_text', $original)->call('saveText', 'home__disc_text');

    expect(DB::table('site_contents')->count())->toBe(0);
});

it('replaces a picture with a re-encoded file and removes it on reset', function () {
    $component = siteEditor()->set('uploads.home__leap_image', UploadedFile::fake()->image('new.jpg', 1600, 2000));
    $path = SiteContent::image('home.leap_image');

    expect($path)->toStartWith('/images/site/')->and(is_file($this->media.$path))->toBeTrue();
    $this->get('/home')->assertSee($path, false);

    $component->call('reset_slot', 'home__leap_image');

    expect(is_file($this->media.$path))->toBeFalse()
        ->and(SiteContent::image('home.leap_image'))->toBe('/images/home/LEAP.jpg');
})->skip(! extension_loaded('gd'), 'needs the GD extension');

it('refuses a file that is not a picture', function () {
    siteEditor()->set('uploads.home__leap_image', UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1;'));

    expect(DB::table('site_contents')->count())->toBe(0);
});

it('takes an MP4 film by its bytes and refuses anything else', function () {
    siteEditor()->set('uploads.home__matters_video', UploadedFile::fake()->createWithContent('f.mp4', "\0\0\0\x18ftypmp42\0\0\0\0mp42isom"));
    expect(SiteContent::video('home.matters_video'))->toStartWith('/video/site/');

    siteEditor()->set('uploads.home__matters_video', UploadedFile::fake()->createWithContent('g.mp4', 'not a film at all'));
    expect(SiteContent::video('home.matters_video'))->toStartWith('/video/site/');
});

it('answers 404 for a slot of another page or one that does not exist', function () {
    siteEditor()->call('saveText', 'disc__english_image')->assertNotFound();
    siteEditor()->call('reset_slot', 'home__nope')->assertNotFound();
    expect(DB::table('site_contents')->count())->toBe(0);
});

it('refuses a role without the capability, even for a direct call', function () {
    siteEditor('Viewer')->assertForbidden();
});
