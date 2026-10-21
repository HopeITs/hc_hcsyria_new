<?php

use App\Enums\ContentKind;
use App\Models\Initiative;
use App\Models\InitiativeContent;
use App\Support\Admin\PhotoFiles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The staff dashboard's initiatives: who may open what, the list, the state
 * changes (the same shared code as the micro projects'), and the create / edit /
 * delete forms. Like every feature test these run against hcsyria_db inside a
 * rolled-back transaction and use initiatives they create themselves.
 */

// ----------------------------------------------------------- who gets in, the list

it('lets every role open the list and an initiative', function (string $role) {
    $initiative = adminInitiative();
    $user = staff($role);

    $this->actingAs($user)->get('/admin/initiatives')->assertOk()->assertSee('Initiatives');
    $this->actingAs($user)->get("/admin/initiatives/{$initiative->id}")->assertOk()->assertSee("zqi initiative {$initiative->id}");
})->with(['Admin', 'Publisher', 'English Editor', 'English Checker', 'Photos Editor', 'Viewer']);

it('gives create and edit to the English Editor and the Admin only', function (string $role, bool $mayWrite) {
    $initiative = adminInitiative();
    $user = staff($role);

    $create = $this->actingAs($user)->get('/admin/initiatives/create');
    $edit = $this->actingAs($user)->get("/admin/initiatives/{$initiative->id}/edit");

    $mayWrite ? $create->assertOk() : $create->assertForbidden();
    $mayWrite ? $edit->assertOk() : $edit->assertForbidden();
})->with([['Admin', true], ['English Editor', true], ['Publisher', false], ['English Checker', false], ['Photos Editor', false], ['Viewer', false]]);

it('shows the section in the sidebar and the Ctrl K list', function () {
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertSee('Initiatives')->assertDontSee('New initiative');
    $this->actingAs(staff('English Editor'))->get('/admin/home')->assertSee('New initiative');
});

it('lists the managed categories (not Homework Room), finds by title, and counts the tabs', function () {
    $pending = adminInitiative();
    $online = adminInitiative(['online' => 1]);
    $programme = adminInitiative(['what_category_id' => 6]);

    $page = Livewire::actingAs(staff('Viewer'))->test('pages::admin.initiatives.index')->set('search', 'zqi initiative');

    expect($page->viewData('counts'))->toMatchArray(['all' => 2, 'pending' => 1, 'online' => 1]);
    $page->assertSee("zqi initiative {$pending->id}")->assertSee("zqi initiative {$online->id}")->assertDontSee("zqi initiative {$programme->id}");

    $page->set('tab', 'online')->assertSee("zqi initiative {$online->id}")->assertDontSee("zqi initiative {$pending->id}");
});

it('filters by category and by donor', function () {
    $social = adminInitiative();
    $relief = adminInitiative(['what_category_id' => 4]);
    $other = DB::table('donors')->orderByDesc('id')->value('id');
    DB::table('initiative_donors')->where('initiative_id', $relief->id)->update(['donor_id' => $other]);

    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.index')->set('search', 'zqi initiative');

    $page->set('category', '4')->assertSee("zqi initiative {$relief->id}")->assertDontSee("zqi initiative {$social->id}");
    $page->set('category', '')->set('donor', (string) $other)->assertSee("zqi initiative {$relief->id}")->assertDontSee("zqi initiative {$social->id}");
});

it('draws only the buttons the role may use', function () {
    $initiative = adminInitiative();

    Livewire::actingAs(staff('Viewer'))->test('pages::admin.initiatives.index')->set('search', "zqi initiative {$initiative->id}")->assertDontSee('Publish')->assertDontSee('Edit');
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.initiatives.index')->set('search', "zqi initiative {$initiative->id}")->assertSee('Publish')->assertDontSee('Edit');
    Livewire::actingAs(staff('English Editor'))->test('pages::admin.initiatives.index')->set('search', "zqi initiative {$initiative->id}")->assertSee('Edit')->assertDontSee('Publish');
});

// -------------------------------------------------------------------- state moves

it('publishes an initiative whose English is checked, and writes legacy\'s log text', function () {
    $initiative = adminInitiative();
    $user = staff('Publisher');

    Livewire::actingAs($user)->test('pages::admin.initiatives.index')
        ->call('moveItem', $initiative->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success' && str_contains($params['message'], "Initiative #{$initiative->id} is online"));

    expect($initiative->refresh())->online->toBe(1)->hidden->toBeFalse()
        ->and(initiativeLogs($initiative)->where(['operation' => 'Initiative Status: Online', 'user_id' => $user->id])->exists())->toBeTrue();
});

it('will not publish an initiative whose English is not checked', function () {
    $initiative = adminInitiative(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.index')
        ->call('moveItem', $initiative->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'English text is not checked'));

    expect($initiative->refresh()->online)->toBe(0);
});

it('walks an initiative through every move', function () {
    $initiative = adminInitiative(['english_check' => 0, 'online' => 1]);
    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.index');

    $page->call('moveItem', $initiative->id, 'unpublish');
    expect($initiative->refresh()->online)->toBe(0);

    $page->call('moveItem', $initiative->id, 'hide');
    expect($initiative->refresh())->hidden->toBeTrue()->online->toBe(0);

    $page->call('moveItem', $initiative->id, 'unhide');
    expect($initiative->refresh()->hidden)->toBeFalse();

    $page->call('moveItem', $initiative->id, 'check-english');
    expect($initiative->refresh()->english_check)->toBe(1)
        ->and(initiativeLogs($initiative)->orderBy('id')->pluck('operation')->all())
        ->toBe(['Initiative Status: Offline', 'Initiative Status: Hidden', 'Initiative Status: Pending to publish', 'Initiative English Checked']);
});

it('refuses a move the role may not make', function (string $role, string $move) {
    $initiative = adminInitiative(['online' => 1, 'english_check' => 0]);

    Livewire::actingAs(staff($role))->test('pages::admin.initiatives.index')
        ->call('moveItem', $initiative->id, $move)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

    expect($initiative->refresh())->online->toBe(1)->english_check->toBe(0)->hidden->toBeFalse();
})->with([['Viewer', 'unpublish'], ['Publisher', 'unpublish'], ['English Editor', 'hide'], ['Photos Editor', 'check-english'], ['English Editor', 'check-english']]);

it('moves several at once and counts the ones it skipped', function () {
    $ready = adminInitiative();
    $notReady = adminInitiative(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.index')
        ->call('transitionMany', [$ready->id, $notReady->id], 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'warning' && str_contains($params['message'], '1 of 2 initiatives'));

    expect($ready->refresh()->online)->toBe(1)->and($notReady->refresh()->online)->toBe(0);
});

it('does not let a project\'s id be moved from the initiatives page', function () {
    $project = adminProject();

    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.index')->call('moveItem', $project->id, 'publish');

    expect($project->refresh()->MPE_Published)->toBe('0');
});

// ------------------------------------------------------------ create, edit, delete

it('creates an initiative as legacy did: offline, unchecked, "0" for what was left empty, with its donors and a log row', function () {
    $user = staff('English Editor');
    $values = initiativeForm(['form.organization' => '', 'form.date_string' => '', 'form.description' => '']);

    Livewire::actingAs($user)->test('pages::admin.initiatives.form')->fill($values)->call('save')->assertHasNoErrors()->assertRedirect();

    $content = InitiativeContent::query()->where('title', 'zqi new initiative')->firstOrFail();
    $initiative = Initiative::query()->findOrFail($content->initiative_id);

    expect($initiative)->online->toBe(0)->english_check->toBe(0)->hidden->toBeFalse()->logo->toBe('0')->video->toBe('0')->what_category_id->toBe(1)
        ->and($content)->organization->toBe('0')->date_string->toBe('0')->description->toBe('0')
        ->and($content->content)->toBe('<p>A long enough text for an initiative.</p>x')
        ->and(DB::table('initiative_donors')->where('initiative_id', $initiative->id)->count())->toBe(1)
        ->and(initiativeLogs($initiative)->where(['operation' => 'Initiative Created', 'user_id' => $user->id])->exists())->toBeTrue();
});

it('asks for what is missing', function () {
    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.form')
        ->fill(['form.what_category_id' => '8', 'form.title' => str_repeat('a', 71), 'form.content' => '<p></p>', 'form.donors' => []])
        ->call('save')
        ->assertHasErrors(['form.title', 'form.content', 'form.donors', 'form.who_category_id', 'form.sub_what_category_id'])
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
});

it('takes only the categories the section manages, and the donors that exist', function () {
    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.form');

    $page->fill(initiativeForm(['form.what_category_id' => '6']))->call('save')->assertHasErrors('form.what_category_id');
    $page->fill(initiativeForm(['form.donors' => ['99999']]))->call('save')->assertHasErrors('form.donors.0');
});

it('makes a Family to Family initiative a partner or a story', function () {
    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.form')
        ->fill(initiativeForm(['form.what_category_id' => '8', 'form.sub_what_category_id' => '2']))
        ->call('save')->assertHasNoErrors();

    $initiative = Initiative::query()->findOrFail(InitiativeContent::query()->where('title', 'zqi new initiative')->value('initiative_id'));
    expect($initiative->sub_what_category_id)->toBe(2);
});

it('edits an initiative and logs one row for each change, worded as legacy did', function () {
    $initiative = adminInitiative();
    $user = staff('English Editor');
    $firstDonor = DB::table('initiative_donors')->where('initiative_id', $initiative->id)->value('donor_id');
    $newDonor = DB::table('donors')->where('id', '!=', $firstDonor)->orderBy('id')->value('id');

    Livewire::actingAs($user)->test('pages::admin.initiatives.form', ['initiative' => $initiative])
        ->assertSet('form.title', "zqi initiative {$initiative->id}")
        ->set('form.title', 'zqi renamed')
        ->set('form.who_category_id', '2')
        ->set('form.donors', [(string) $newDonor])
        ->call('save')->assertHasNoErrors();

    $logs = initiativeLogs($initiative)->get()->keyBy('operation');
    expect($logs->keys()->all())->toEqualCanonicalizing(['Donor '.DB::table('donors')->where('id', $newDonor)->value('name').' attached', 'Donor '.DB::table('donors')->where('id', $firstDonor)->value('name').' detached', 'Serve category changed', 'Title edited'])
        ->and($logs['Title edited'])->previous_data->toBe("zqi initiative {$initiative->id}")->current_data->toBe('zqi renamed')
        ->and($logs['Serve category changed'])->previous_data->toBe('men')->current_data->toBe('women')
        ->and(DB::table('initiative_donors')->where('initiative_id', $initiative->id)->pluck('donor_id')->all())->toBe([$newDonor]);
});

it('does not log an edit when only line endings changed, and never changes the category', function () {
    $initiative = adminInitiative([], ['content' => "<p>One</p>\r\n<p>Two</p>"]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.form', ['initiative' => $initiative])
        ->set('form.content', "<p>One</p>\n<p>Two</p>")
        ->set('form.what_category_id', '4')
        ->call('save');

    expect(initiativeLogs($initiative)->where('operation', 'Content edited')->exists())->toBeFalse()
        ->and($initiative->refresh()->what_category_id)->toBe(1);
});

it('writes "0" back for a line the editor emptied', function () {
    $initiative = adminInitiative();

    Livewire::actingAs(staff('Admin'))->test('pages::admin.initiatives.form', ['initiative' => $initiative])
        ->set('form.organization', '')->call('save');

    expect(InitiativeContent::query()->where('initiative_id', $initiative->id)->value('organization'))->toBe('0');
});

it('deletes an initiative for an Admin only, with its rows and its logo, and logs it', function () {
    $media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($media.'/images/initiatives');
    file_put_contents($media.'/images/initiatives/logo.png', 'x');
    config(['hcsyria.media_root' => $media]);

    $initiative = adminInitiative(['logo' => '/images/initiatives/logo.png']);
    $admin = staff('Admin');

    Livewire::actingAs(staff('English Editor'))->test('pages::admin.initiatives.index')->call('deleteItem', $initiative->id)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
    expect(Initiative::query()->whereKey($initiative->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)->test('pages::admin.initiatives.index')->call('deleteItem', $initiative->id)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');

    expect(Initiative::query()->whereKey($initiative->id)->exists())->toBeFalse()
        ->and(InitiativeContent::query()->where('initiative_id', $initiative->id)->exists())->toBeFalse()
        ->and(DB::table('initiative_donors')->where('initiative_id', $initiative->id)->exists())->toBeFalse()
        ->and(File::exists($media.'/images/initiatives/logo.png'))->toBeFalse()
        ->and(initiativeLogs($initiative)->where(['operation' => 'Initiative deleted', 'user_id' => $admin->id])->exists())->toBeTrue();

    File::deleteDirectory($media);
});

// ------------------------------------------------------------------ small pieces

it('tells the two kinds of content apart', function () {
    expect(ContentKind::of(adminInitiative()))->toBe(ContentKind::Initiative)
        ->and(ContentKind::of(adminProject()))->toBe(ContentKind::Project)
        ->and(ContentKind::Initiative->route('edit'))->toBe('admin.initiatives.edit')
        ->and(PhotoFiles::bigPath('/images/a/1.jpg'))->toBe('/images/a/1b.jpg');
});
