<?php

use App\Support\Site\PageText;
use App\Support\Site\SiteContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
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

it('lets Admin, Publisher, Photos Editor and English Checker open it, and nobody else', function (string $role, bool $may) {
    $this->actingAs(staff($role))->get('/admin/site')->assertStatus($may ? 200 : 403);
})->with([['Admin', true], ['Publisher', true], ['Photos Editor', true], ['English Checker', true], ['English Editor', false], ['Viewer', false]]);

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

/*
 * Words: every line of text a visitor reads, found by reading the page, and rewritten one line at a time.
 */
function wordsPage(string $page = 'christian-youth')
{
    Artisan::call('site:scan', ['path' => config("site_content.pages.$page.url")]);
    $lines = json_decode(trim(Artisan::output()), true);
    Cache::put('site-scan:'.config("site_content.pages.$page.url"), $lines, 60);

    return Livewire::actingAs(staff('Admin'))->withQueryParams(['mode' => 'words', 'page' => $page])->test('pages::admin.site');
}

it('reads the words of a page, own lines apart from the menu and footer', function () {
    $own = collect(PageText::lines($this->get('/christianyouth')->getContent()))->where('wide', false)->pluck('text');

    expect($own)->toContain('Participants')->toContain('The Main Objectives:')
        ->and(collect(PageText::lines($this->get('/christianyouth')->getContent()))->where('wide', true)->pluck('text'))->toContain('Who We Are');
});

it('leaves markup, scripts and attributes alone and keeps the spacing around a rewritten line', function () {
    $html = '<nav><a href="/x" @click="a > b">Home page</a></nav><p>Hello <b>big</b> world</p><script>var a="<p>Hello</p>";</script>';
    $out = PageText::rewrite($html, [PageText::key('/p', 'Hello') => 'Hi <there>'], '/p');

    expect($out)->toBe('<nav><a href="/x" @click="a > b">Home page</a></nav><p>Hi &lt;there&gt; <b>big</b> world</p><script>var a="<p>Hello</p>";</script>');
});

it('rewrites a line of a page, shows it on that page only, and puts it back', function () {
    $page = wordsPage()->assertSee('The Main Objectives:')
        ->set('edits.'.substr(sha1('The Main Objectives:'), 0, 16), 'Our Aims:')
        ->call('saveLine', substr(sha1('The Main Objectives:'), 0, 16))
        ->assertDispatched('admin-alert');

    $this->get('/christianyouth')->assertSee('Our Aims:')->assertDontSee('The Main Objectives:');
    $this->get('/home')->assertDontSee('Our Aims:');

    $page->call('resetLine', substr(sha1('The Main Objectives:'), 0, 16));
    $this->get('/christianyouth')->assertSee('The Main Objectives:');
    expect(DB::table('site_contents')->where('slot', 'like', 'text:%')->count())->toBe(0);
});

it('rewrites a menu line for every page', function () {
    $hash = substr(sha1('Who We Are'), 0, 16);
    wordsPage('christian-youth')->set('page', 'sitewide')->set("edits.$hash", 'About us')->call('saveLine', $hash);

    expect(DB::table('site_contents')->where('slot', "text:*:$hash")->value('value'))->toBe('About us');
    $this->get('/christianyouth')->assertSee('About us');
    $this->get('/who-we-are')->assertSee('About us');
});

it('refuses to rewrite a line the page does not have, or with nothing', function () {
    wordsPage()->set('edits.0123456789abcdef', 'x')->call('saveLine', '0123456789abcdef')->assertDispatched('admin-alert', type: 'error');

    $hash = substr(sha1('Participants'), 0, 16);
    wordsPage()->set("edits.$hash", '   ')->call('saveLine', $hash)->assertDispatched('admin-alert', type: 'error');
    expect(DB::table('site_contents')->count())->toBe(0);
});

it('lists a rewrite whose original left the page, so it can be removed', function () {
    DB::table('site_contents')->insert(['slot' => 'text:/christianyouth:0123456789abcdef', 'value' => 'New', 'original' => 'Old words', 'created_at' => now(), 'updated_at' => now()]);

    wordsPage()->assertSee('No longer on the page')->assertSee('Old words')->call('resetLine', '0123456789abcdef');
    expect(DB::table('site_contents')->count())->toBe(0);
});

/*
 * The visual editor: the public page opened with ?sc-edit=1 by staff is marked up so each line and
 * picture can be changed in place; the dashboard saves what the page reports.
 */
it('marks the page up for staff who may change it, and for nobody else', function () {
    $marked = $this->actingAs(staff('Admin'))->get('/who-we-are?sc-edit=1');
    $marked->assertOk()->assertSee('data-sc-t="text:*:', false)->assertSee('id="sc-script"', false)->assertSee('data-sc-i="img:/who-we-are:', false);
    expect($marked->headers->get('Cache-Control'))->toContain('no-store');

    foreach ([null, 'Viewer', 'English Editor'] as $role) {
        $this->app['auth']->forgetGuards();
        $guest = $role === null ? $this : $this->actingAs(staff($role));
        $guest->get('/who-we-are?sc-edit=1')->assertOk()->assertDontSee('data-sc-t', false)->assertDontSee('sc-script', false);
    }
});

it('tells a picture of a slot from one of the page', function () {
    $html = '<img src="/images/mission/Responsibility.jpg"><img src="/images/who-we-serve/1.jpg"><img src="/images/ico/eye-icon.png"><img src="/images/news/3/a.jpg"><img src="/images/home/image-mask.png"><img src="/images/home/quotation.svg">';
    $out = PageText::decorate($html, [], '/who-we-are', ['/images/mission/Responsibility.jpg' => 'who-we-are.mission_photo']);

    expect($out)->toContain('<img data-sc-slot="who-we-are.mission_photo" src="/images/mission/Responsibility.jpg">')
        ->toContain('data-sc-i="img:/who-we-are:')
        ->and(substr_count($out, 'data-sc-i='))->toBe(1)
        ->and(substr_count($out, 'data-sc-slot='))->toBe(1);
});

it('saves a line typed over on the page, and the page then shows it', function () {
    $result = Livewire::actingAs(staff('Admin'))->test('pages::admin.site')->call('saveEdit', '/christianyouth', 'Participants', 'Who is coming')->assertReturned(['ok' => true, 'message' => '']);

    $this->get('/christianyouth')->assertSee('Who is coming')->assertDontSee('>Participants<', false);
    $this->actingAs(staff('Admin'))->get('/christianyouth?sc-edit=1')->assertSee('data-sc-c="1"', false)->assertSee('data-sc-o="Participants"', false);
});

it('answers a refusal for a line of a page that is not listed', function () {
    Livewire::actingAs(staff('Admin'))->test('pages::admin.site')->call('saveEdit', '/not-a-page', 'Participants', 'x')->assertReturned(['ok' => false, 'message' => 'That part of the site cannot be changed from here.']);
});

it('swaps a picture of a page for that page alone, and a slot picture everywhere it is drawn', function () {
    $key = null;
    preg_match('~data-sc-i="(img:/who-we-are:[a-f0-9]{16})" data-sc-s="([^"]+)"~', $this->actingAs(staff('Admin'))->get('/who-we-are?sc-edit=1')->getContent(), $m);
    [, $key, $src] = $m;

    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.site')
        ->set('drop', UploadedFile::fake()->image('a.jpg', 800, 600))
        ->call('applyImage', '', $key, $src)->assertReturned(['ok' => true, 'message' => '']);

    $stored = DB::table('site_contents')->where('slot', $key)->value('value');
    expect($stored)->toStartWith('/images/site/');
    $this->get('/who-we-are')->assertSee($stored, false);
    $this->get('/home')->assertDontSee($stored, false);

    $page->call('resetEdit', $key);
    expect(DB::table('site_contents')->where('slot', $key)->exists())->toBeFalse()->and(is_file($this->media.$stored))->toBeFalse();

    $page->set('drop', UploadedFile::fake()->image('b.jpg', 800, 600))->call('applyImage', 'who-we-are.mission_photo', '', '');
    $this->get('/who-we-are')->assertSee('/images/site/', false);
})->skip(! extension_loaded('gd'), 'needs the GD extension');

it('will not swap a picture under a key that does not match its address', function () {
    Livewire::actingAs(staff('Admin'))->test('pages::admin.site')
        ->set('drop', UploadedFile::fake()->image('a.jpg'))
        ->call('applyImage', '', 'img:/who-we-are:0123456789abcdef', '/images/who-we-serve/1.jpg')->assertNotFound();
})->skip(! extension_loaded('gd'), 'needs the GD extension');

/*
 * The same editor on the public site itself, for a signed-in account that may change it.
 */
it('offers the editor on a public page only to an account that may change the site', function () {
    $this->actingAs(staff('Admin'))->get('/who-we-are')->assertOk()->assertSee('id="sc-pill"', false)->assertSee('/who-we-are?sc-edit=1', false);
    $this->actingAs(staff('Publisher'))->get('/who-we-are')->assertSee('id="sc-pill"', false);

    foreach ([null, 'Viewer', 'English Editor'] as $role) {
        $this->app['auth']->forgetGuards();
        $visitor = $role === null ? $this : $this->actingAs(staff($role));
        $visitor->get('/who-we-are')->assertOk()->assertDontSee('sc-pill', false);
    }

    // A page the editor does not cover has no button, even for Admin.
    $this->actingAs(staff('Admin'))->get('/projects/rehabilitation')->assertDontSee('sc-pill', false);
});

it('runs the editor on the public page with its own way of saving', function () {
    $page = $this->actingAs(staff('Admin'))->get('/christianyouth?sc-edit=1')->assertOk();
    $page->assertSee('window.__scConfig', false)->assertSee(url('/site-edit'), false)->assertDontSee('id="sc-pill"', false);

    $this->app['auth']->forgetGuards();
    $this->get('/christianyouth?sc-edit=1')->assertDontSee('__scConfig', false);
});

it('saves, swaps and puts back from the public page', function () {
    $admin = staff('Admin');
    $this->actingAs($admin)->postJson('/site-edit/text', ['where' => '/christianyouth', 'original' => 'Participants', 'text' => 'Who is coming'])->assertOk()->assertJson(['ok' => true]);
    $this->get('/christianyouth')->assertSee('Who is coming');

    $key = PageText::textKey('/christianyouth', 'Participants');
    $this->postJson('/site-edit/reset', ['key' => $key])->assertOk()->assertJson(['ok' => true]);
    $this->get('/christianyouth')->assertSee('Participants');

    $this->postJson('/site-edit/text', ['where' => '/nope', 'original' => 'Participants', 'text' => 'x'])->assertJson(['ok' => false]);
    $this->postJson('/site-edit/reset', ['key' => 'text:/christianyouth:nothex'])->assertNotFound();
});

it('swaps a picture from the public page', function () {
    $this->actingAs(staff('Admin'));
    preg_match('~data-sc-i="(img:/who-we-are:[a-f0-9]{16})" data-sc-s="([^"]+)"~', $this->get('/who-we-are?sc-edit=1')->getContent(), $m);

    $this->post('/site-edit/file', ['key' => $m[1], 'src' => $m[2], 'slot' => '', 'video' => '0', 'file' => UploadedFile::fake()->image('a.jpg', 600, 400)], ['Accept' => 'application/json'])
        ->assertOk()->assertJson(['ok' => true]);

    expect(DB::table('site_contents')->where('slot', $m[1])->value('value'))->toStartWith('/images/site/');

    $this->post('/site-edit/file', ['key' => $m[1], 'src' => $m[2], 'slot' => '', 'file' => UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1;')], ['Accept' => 'application/json'])
        ->assertJson(['ok' => false]);
})->skip(! extension_loaded('gd'), 'needs the GD extension');

it('keeps the saves for an account that may change the site', function () {
    $this->postJson('/site-edit/text', ['where' => '/christianyouth', 'original' => 'Participants', 'text' => 'x'])->assertUnauthorized();

    foreach (['Viewer', 'English Editor'] as $role) {
        $this->actingAs(staff($role))->postJson('/site-edit/text', ['where' => '/christianyouth', 'original' => 'Participants', 'text' => 'x'])->assertForbidden();
    }

    expect(DB::table('site_contents')->count())->toBe(0);
});

/*
 * Who may change what: the words are Admin's and English Checker's; pictures, logos and films are Admin's,
 * Publisher's and Photos Editor's. Each sees only what they may change, and every save asks again.
 */
it('gives each role only what it may change on the public page', function (string $role, bool $words, bool $pictures) {
    $this->actingAs(staff($role));
    $page = $this->get('/who-we-are?sc-edit=1')->assertOk();

    expect(str_contains($page->getContent(), 'data-sc-t="'))->toBe($words)
        ->and(str_contains($page->getContent(), 'data-sc-i="'))->toBe($pictures);
    $this->get('/who-we-are')->assertSee('id="sc-pill"', false);
})->with([['Admin', true, true], ['English Checker', true, false], ['Publisher', false, true], ['Photos Editor', false, true]]);

it('keeps a publisher to pictures and an English checker to words, on the server too', function () {
    $words = ['where' => '/christianyouth', 'original' => 'Participants', 'text' => 'Who is coming'];

    $this->actingAs(staff('Publisher'))->postJson('/site-edit/text', $words)->assertForbidden();
    $this->actingAs(staff('Photos Editor'))->postJson('/site-edit/text', $words)->assertForbidden();
    $this->actingAs(staff('English Checker'))->postJson('/site-edit/text', $words)->assertOk()->assertJson(['ok' => true]);

    preg_match('~data-sc-i="(img:/who-we-are:[a-f0-9]{16})" data-sc-s="([^"]+)"~', $this->actingAs(staff('Admin'))->get('/who-we-are?sc-edit=1')->getContent(), $m);
    $picture = ['key' => $m[1], 'src' => $m[2], 'slot' => '', 'video' => '0'];

    if (extension_loaded('gd')) {
        $this->actingAs(staff('English Checker'))->post('/site-edit/file', $picture + ['file' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs(staff('Publisher'))->post('/site-edit/file', $picture + ['file' => UploadedFile::fake()->image('a.jpg')], ['Accept' => 'application/json'])->assertOk()->assertJson(['ok' => true]);
    }

    // Putting back follows the same rule: a line is words, a picture is pictures.
    $this->actingAs(staff('Publisher'))->postJson('/site-edit/reset', ['key' => PageText::textKey('/christianyouth', 'Participants')])->assertForbidden();
    $this->actingAs(staff('English Checker'))->postJson('/site-edit/reset', ['key' => $m[1]])->assertForbidden();
    $this->actingAs(staff('English Checker'))->postJson('/site-edit/reset', ['key' => PageText::textKey('/christianyouth', 'Participants')])->assertOk();
});

it('shows the dashboard to each role only the part it may change', function () {
    $checker = Livewire::actingAs(staff('English Checker'))->withQueryParams(['mode' => 'media'])->test('pages::admin.site');
    $checker->assertSet('mode', 'visual')->assertSee('Words list')->assertDontSee('Pictures &amp; logos', false);

    $publisher = Livewire::actingAs(staff('Publisher'))->withQueryParams(['mode' => 'words'])->test('pages::admin.site');
    $publisher->assertSet('mode', 'visual')->assertSee('Pictures &amp; logos', false)->assertDontSee('Words list');

    // Direct calls are refused whatever the page shows.
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.site')->call('saveEdit', '/christianyouth', 'Participants', 'x')->assertForbidden();
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.site')->set('texts.home__leap_text', 'x')->call('saveText', 'home__leap_text')->assertForbidden();
    Livewire::actingAs(staff('English Checker'))->test('pages::admin.site')->call('applyImage', 'who-we-are.mission_photo', '', '')->assertForbidden();

    expect(DB::table('site_contents')->count())->toBe(0);
});

it('lets the English checker change words and the publisher pictures from the dashboard', function () {
    Livewire::actingAs(staff('English Checker'))->test('pages::admin.site')->call('saveEdit', '/christianyouth', 'Participants', 'Who is coming')->assertReturned(['ok' => true, 'message' => '']);
    expect(SiteContent::textFor('/christianyouth'))->toHaveCount(1);

    if (extension_loaded('gd')) {
        Livewire::actingAs(staff('Publisher'))->test('pages::admin.site')->set('page', 'who-we-are')->set('uploads.who-we-are__mission_photo', UploadedFile::fake()->image('a.jpg', 600, 400));
        expect(SiteContent::image('who-we-are.mission_photo'))->toStartWith('/images/site/');
    }
});

/*
 * Logos: a partner's, a funder's, the organisation's own are swapped for the whole site at once, and a vector
 * original may be replaced by an uploaded picture.
 */
it('swaps a logo for every page at once, even when the original is a vector', function () {
    $logo = '/images/logos/partner-x.svg';
    $html = '<main><p>Our partners</p><img src="'.$logo.'" alt="Partner"></main>';

    expect(PageText::swappable($logo))->toBeTrue()->and(PageText::swappable('/images/home/quotation.svg'))->toBeFalse();

    $marked = PageText::decorate($html, [], '/who-we-are', []);
    expect($marked)->toContain('data-sc-i="'.PageText::imageKey('*', $logo).'"');

    $changes = [PageText::imageKey('*', $logo) => '/images/site/new-partner.png'];
    foreach (['/who-we-are', '/projects/family-to-family', '/christianyouth'] as $page) {
        expect(PageText::rewrite($html, $changes, $page))->toContain('src="/images/site/new-partner.png"')->not->toContain($logo);
    }
});

it('takes a logo from the public page and puts the original back', function () {
    $logo = '/images/logos/partner-y.svg';
    $key = PageText::imageKey('*', $logo);

    $this->actingAs(staff('Publisher'));
    $this->post('/site-edit/file', ['key' => $key, 'src' => $logo, 'slot' => '', 'video' => '0', 'file' => UploadedFile::fake()->image('logo.png', 300, 300)], ['Accept' => 'application/json'])->assertOk()->assertJson(['ok' => true]);

    expect(DB::table('site_contents')->where('slot', $key)->value('value'))->toStartWith('/images/site/');
    $this->postJson('/site-edit/reset', ['key' => $key])->assertOk();
    expect(DB::table('site_contents')->where('slot', $key)->exists())->toBeFalse();
})->skip(! extension_loaded('gd'), 'needs the GD extension');

it('lets a file be dropped on a picture in the editor', function () {
    $script = $this->actingAs(staff('Publisher'))->get('/who-we-are?sc-edit=1')->getContent();

    expect($script)->toContain("addEventListener('drop'")->toContain('sc-drop')->toContain('Click a picture or logo to replace it, or drop a file onto it');
});

/*
 * Logos in a new order: the partners strip and a programme's funders, dragged into place on the page.
 */
function logos(): Collection
{
    return collect([3, 7, 9, 12])->map(fn (int $id): object => (object) ['id' => $id, 'name' => "Partner $id", 'logo' => "/images/logos/p$id.png"]);
}

it('lays a group of logos out in the order staff gave it, the others after', function () {
    expect(SiteContent::ordered('/who-we-are:partners', logos())->pluck('id')->all())->toBe([3, 7, 9, 12]);

    DB::table('site_contents')->insert(['slot' => 'order:/who-we-are:partners', 'value' => '[12,3]', 'created_at' => now(), 'updated_at' => now()]);
    app(SiteContent::class)->refresh();

    expect(SiteContent::order('/who-we-are:partners'))->toBe([12, 3])
        ->and(SiteContent::ordered('/who-we-are:partners', logos())->pluck('id')->all())->toBe([12, 3, 7, 9])
        ->and(SiteContent::ordered('/projects/disc:ongoing', logos())->pluck('id')->all())->toBe([3, 7, 9, 12]);
});

it('shows the partners in that order on the page, and marks them for dragging only while editing', function () {
    $this->actingAs(staff('Publisher'))->postJson('/site-edit/order', ['group' => '/who-we-are:partners', 'ids' => [12, 9, 7, 3]])->assertOk()->assertJson(['ok' => true]);

    $this->get('/who-we-are');
    $plain = Blade::render('<x-site.partners :donors="$donors"/>', ['donors' => logos()]);
    expect($plain)->not->toContain('data-sc-item');
    preg_match_all('~/images/logos/p(\d+)\.png~', $plain, $found);
    expect(array_map('intval', $found[1]))->toBe([12, 9, 7, 3]);

    $this->get('/who-we-are?sc-edit=1');
    $marked = Blade::render('<x-site.partners :donors="$donors"/>', ['donors' => logos()]);
    expect($marked)->toContain('data-sc-group="/who-we-are:partners"')->toContain('data-sc-item="12"')->toContain('data-sc-c="1"');
});

it('keeps the order to whoever may change pictures, and puts the original back', function () {
    $body = ['group' => '/who-we-are:partners', 'ids' => [3, 7]];

    $this->actingAs(staff('English Checker'))->postJson('/site-edit/order', $body)->assertForbidden();
    $this->actingAs(staff('Publisher'))->postJson('/site-edit/order', $body)->assertOk()->assertJson(['ok' => true]);
    expect(DB::table('site_contents')->where('slot', 'order:/who-we-are:partners')->value('value'))->toBe('[3,7]');

    $this->actingAs(staff('English Checker'))->postJson('/site-edit/reset', ['key' => 'order:/who-we-are:partners'])->assertForbidden();
    $this->actingAs(staff('Photos Editor'))->postJson('/site-edit/reset', ['key' => 'order:/who-we-are:partners'])->assertOk();
    expect(DB::table('site_contents')->count())->toBe(0);

    // A group that is not one of the strips, or a page that cannot be edited, is refused.
    $this->actingAs(staff('Admin'))->postJson('/site-edit/order', ['group' => '/nowhere:partners', 'ids' => [1]])->assertJson(['ok' => false]);
    $this->postJson('/site-edit/order', ['group' => '/who-we-are:everything', 'ids' => [1]])->assertJson(['ok' => false]);
    $this->postJson('/site-edit/reset', ['key' => 'order:/who-we-are:oops'])->assertNotFound();
});

it('saves a new order from the dashboard too', function () {
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.site')->call('saveOrder', '/projects/disc:ongoing', [5, 2])->assertReturned(['ok' => true, 'message' => '']);
    expect(SiteContent::order('/projects/disc:ongoing'))->toBe([5, 2]);

    Livewire::actingAs(staff('English Checker'))->test('pages::admin.site')->call('saveOrder', '/projects/disc:ongoing', [1])->assertForbidden();
});

it('lets the editor drag logos into a new order', function () {
    $page = $this->actingAs(staff('Publisher'))->get('/who-we-are?sc-edit=1')->getContent();

    expect($page)->toContain("addEventListener('dragstart'")->toContain("type: 'order'")->toContain('Drag to reorder');
});
