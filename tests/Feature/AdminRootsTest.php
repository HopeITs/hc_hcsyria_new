<?php

use App\Models\RootsOfHope;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The Roots of Hope magazine editions in the admin: list, page, create, edit, online and
 * offline, delete. Files go to a throwaway folder standing in for MEDIA_ROOT. The cover is
 * re-encoded, so those tests need PHP's GD extension and are skipped without it.
 */

beforeEach(function () {
    $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($this->media.'/images/rootsofhope');
    File::ensureDirectoryExists($this->media.'/files/rootsofhope');
    config(['hcsyria.media_root' => $this->media]);
});

afterEach(fn () => File::deleteDirectory($this->media));

function magazinePdf(string $name = 'issue.pdf', ?string $bytes = null): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $bytes ?? "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
}

function magazinePage(string $role = 'English Editor', array $params = [])
{
    return Livewire::actingAs(staff($role))->test('pages::admin.roots.form', $params);
}

function magazineFiles(string $media): array
{
    return [...File::allFiles($media.'/images/rootsofhope'), ...File::allFiles($media.'/files/rootsofhope')];
}

it('lists the editions with their tabs and a search', function () {
    $pending = adminMagazine();
    $online = adminMagazine(['online' => 1]);

    $page = Livewire::actingAs(staff('Viewer'))->test('pages::admin.roots.index')->set('search', 'zqm edition');
    $page->assertSee("zqm edition {$pending->id}")->assertSee("zqm edition {$online->id}");

    $page->set('tab', 'pending')->assertSee("zqm edition {$pending->id}")->assertDontSee("zqm edition {$online->id}");
    $page->set('tab', 'online')->assertSee("zqm edition {$online->id}")->assertDontSee("zqm edition {$pending->id}");
});

it('shows an edition page to anyone with a role, with its links', function () {
    $edition = adminMagazine(['file_path' => '/files/rootsofhope/issue.pdf']);

    $this->actingAs(staff('Viewer'))->get("/admin/roots/{$edition->id}")->assertOk()->assertSee("zqm edition {$edition->id}")->assertSee('https://example.test/flip')->assertSee('issue.pdf');
});

it('sends a visitor to sign in and answers 403 to an account without a role', function () {
    $edition = adminMagazine();

    $this->get('/admin/roots')->assertRedirect();
    $this->actingAs(staff('Nobody'))->get("/admin/roots/{$edition->id}")->assertForbidden();
});

it('takes an edition online and offline, logs it, and refuses to publish one without a cover', function () {
    $edition = adminMagazine();
    $user = staff('Publisher');

    $page = Livewire::actingAs($user)->test('pages::admin.roots.index')->set('search', "zqm edition {$edition->id}");
    $page->call('moveItem', $edition->id, 'publish')->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success' && str_contains($params['message'], "Magazine #{$edition->id} is online"));
    expect($edition->refresh()->online)->toBe(1)->and(magazineLogs($edition)->where(['operation' => 'Magazine Status: Online', 'user_id' => $user->id])->exists())->toBeTrue();

    Livewire::actingAs(staff('Admin'))->test('pages::admin.roots.index')->call('moveItem', $edition->id, 'unpublish');
    expect($edition->refresh()->online)->toBe(0)->and(magazineLogs($edition)->where('operation', 'Magazine Status: Offline')->exists())->toBeTrue();

    $bare = adminMagazine(['image_path' => '0']);
    $page->call('moveItem', $bare->id, 'publish')->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'no cover'));
    expect($bare->refresh()->online)->toBe(0);
});

it('has no hide or English check for an edition', function () {
    $edition = adminMagazine();

    Livewire::actingAs(staff('Admin'))->test('pages::admin.roots.index')->call('moveItem', $edition->id, 'hide')->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
    expect($edition->refresh()->online)->toBe(0);
});

it('lets a role only do what it may', function (string $role, bool $publish, bool $edit, bool $delete) {
    $edition = adminMagazine();
    $user = staff($role);

    Livewire::actingAs($user)->test('pages::admin.roots.index')->call('moveItem', $edition->id, 'publish');
    expect($edition->refresh()->online === 1)->toBe($publish);

    $this->actingAs($user)->get("/admin/roots/{$edition->id}/edit")->assertStatus($edit ? 200 : 403);

    Livewire::actingAs($user)->test('pages::admin.roots.index')->call('deleteItem', $edition->id);
    expect(RootsOfHope::query()->whereKey($edition->id)->exists())->toBe(! $delete);
})->with([
    'Viewer' => ['Viewer', false, false, false],
    'English Editor' => ['English Editor', false, true, false],
    'Publisher' => ['Publisher', true, false, false],
    'Admin' => ['Admin', true, true, true],
]);

it('asks for a title and a link, within the length of the columns', function (array $values, array $errors) {
    magazinePage()->fill($values)->call('save')->assertHasErrors($errors);
})->with([
    'nothing' => [[], ['form.title', 'form.url']],
    'a title too long for the column' => [['form.title' => str_repeat('a', 51), 'form.url' => 'https://example.test/a'], ['form.title']],
    'a link too long for the column' => [['form.title' => 'T', 'form.url' => 'https://example.test/'.str_repeat('a', 90)], ['form.url']],
    'a link that is not one' => [['form.title' => 'T', 'form.url' => 'javascript:alert(1)'], ['form.url']],
]);

it('creates an offline edition without files, as the text 0, and logs it', function () {
    magazinePage()->fill(['form.title' => 'zqm created', 'form.url' => 'https://example.test/flip'])->call('save')->assertHasNoErrors()->assertRedirect();

    $edition = RootsOfHope::query()->where('title', 'zqm created')->firstOrFail();
    expect($edition)->online->toBe(0)->image_path->toBe('0')->file_path->toBe('0')
        ->and(magazineLogs($edition)->where('operation', 'Magazine Created')->exists())->toBeTrue();
});

it('does not let a viewer create or edit', function () {
    magazinePage('Viewer')->assertForbidden();
    magazinePage('Viewer', ['edition' => adminMagazine()])->assertForbidden();
});

it('edits the title and link, logs each change, and keeps an online edition online', function () {
    $edition = adminMagazine(['online' => 1]);
    $user = staff('English Editor');

    Livewire::actingAs($user)->test('pages::admin.roots.form', ['edition' => $edition])
        ->assertSet('form.url', 'https://example.test/flip')
        ->set('form.title', 'zqm renamed')->set('form.url', 'https://example.test/new')->call('save')->assertHasNoErrors();

    $logs = magazineLogs($edition)->get()->keyBy('operation');
    expect($edition->refresh())->title->toBe('zqm renamed')->url->toBe('https://example.test/new')->online->toBe(1)
        ->and($logs['Magazine Edited: Title'])->previous_data->toBe("zqm edition {$edition->id}")->current_data->toBe('zqm renamed')
        ->and($logs)->toHaveKey('Magazine Edited: Link');
});

it('stores a PDF under a safe name, replaces it for good and removes it on request', function () {
    $edition = adminMagazine();

    magazinePage('English Editor', ['edition' => $edition])->set('pdf', magazinePdf('../../Issue 12.pdf'))->call('save')->assertHasNoErrors();
    $first = $edition->refresh()->file_path;
    expect($first)->toStartWith('/files/rootsofhope/')->toEndWith('-Issue 12.pdf')->not->toContain('..')->and(strlen($first))->toBeLessThanOrEqual(100)->and(is_file($this->media.$first))->toBeTrue();

    magazinePage('English Editor', ['edition' => $edition])->set('pdf', magazinePdf('two.pdf'))->call('save')->assertHasNoErrors();
    $second = $edition->refresh()->file_path;
    expect($second)->not->toBe($first)->and(is_file($this->media.$first))->toBeFalse()->and(is_file($this->media.$second))->toBeTrue();

    magazinePage('English Editor', ['edition' => $edition])->set('removeFile', true)->call('save')->assertHasNoErrors();
    expect($edition->refresh()->file_path)->toBe('0')->and(is_file($this->media.$second))->toBeFalse()
        ->and(magazineLogs($edition)->where('operation', 'Magazine Edited: File')->count())->toBe(3);
});

it('keeps a long PDF name inside the column', function () {
    $edition = adminMagazine();

    magazinePage('English Editor', ['edition' => $edition])->set('pdf', magazinePdf(str_repeat('long name ', 30).'.pdf'))->call('save')->assertHasNoErrors();

    expect(strlen($edition->refresh()->file_path))->toBeLessThanOrEqual(100);
});

it('refuses a file that only has the name of a PDF', function () {
    $edition = adminMagazine();

    magazinePage('English Editor', ['edition' => $edition])->set('pdf', magazinePdf('x.pdf', '<?php echo 1;'))->call('save')->assertHasErrors('pdf');

    expect($edition->refresh()->file_path)->toBe('0')->and(magazineFiles($this->media))->toBe([]);
});

it('deletes the edition with its cover and its PDF', function () {
    File::put($this->media.'/images/rootsofhope/cover.jpg', 'x');
    File::put($this->media.'/files/rootsofhope/issue.pdf', 'x');
    $edition = adminMagazine(['image_path' => '/images/rootsofhope/cover.jpg', 'file_path' => '/files/rootsofhope/issue.pdf']);
    $user = staff('Admin');

    Livewire::actingAs($user)->test('pages::admin.roots.show', ['edition' => $edition])->call('deleteItem', $edition->id)->assertRedirect(route('admin.roots.index'));

    expect(RootsOfHope::query()->whereKey($edition->id)->exists())->toBeFalse()->and(magazineFiles($this->media))->toBe([])
        ->and(magazineLogs($edition)->where(['operation' => 'Magazine deleted', 'user_id' => $user->id])->exists())->toBeTrue();
});

it('offers the section in the menu and the palette to those who may use it', function () {
    $this->actingAs(staff('Admin'))->get('/admin/home')->assertSee('Roots of Hope')->assertSee('New magazine edition');
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertSee('Roots of Hope')->assertDontSee('New magazine edition');
});

describe('with GD', function () {
    beforeEach(fn () => extension_loaded('gd') || $this->markTestSkipped('The GD extension is not enabled.'));

    it('re-encodes the cover as a JPEG that fits a page, and removes the old one when it is replaced', function () {
        $edition = adminMagazine(['image_path' => '0']);

        magazinePage('English Editor', ['edition' => $edition])->set('cover', UploadedFile::fake()->image('cover.png', 3000, 4000))->call('save')->assertHasNoErrors();
        $first = $edition->refresh()->image_path;
        expect($first)->toStartWith('/images/rootsofhope/')->toEndWith('.jpg')->and(array_slice(getimagesize($this->media.$first), 0, 2))->toBe([900, 1200]);

        magazinePage('English Editor', ['edition' => $edition])->set('cover', UploadedFile::fake()->image('cover.jpg', 400, 600))->call('save')->assertHasNoErrors();
        $second = $edition->refresh()->image_path;
        expect(array_slice(getimagesize($this->media.$second), 0, 2))->toBe([400, 600])->and(is_file($this->media.$first))->toBeFalse()
            ->and(magazineLogs($edition)->where('operation', 'Magazine Edited: Cover')->count())->toBe(2);
    });

    it('refuses a cover that is not a picture and leaves nothing behind', function () {
        magazinePage()->fill(['form.title' => 'zqm bad', 'form.url' => 'https://example.test/a'])->set('cover', UploadedFile::fake()->createWithContent('cover.jpg', '<?php echo 1;'))->call('save')->assertHasErrors('cover');

        expect(RootsOfHope::query()->where('title', 'zqm bad')->exists())->toBeFalse()->and(magazineFiles($this->media))->toBe([]);
    });

    it('can be published once a cover is given', function () {
        $edition = adminMagazine(['image_path' => '0']);
        magazinePage('English Editor', ['edition' => $edition])->set('cover', UploadedFile::fake()->image('c.jpg', 600, 800))->call('save');

        Livewire::actingAs(staff('Publisher'))->test('pages::admin.roots.index')->call('moveItem', $edition->id, 'publish');

        expect($edition->refresh()->online)->toBe(1)->and(PhotoFiles::absolute($edition->image_path))->not->toBeNull();
    });
});
