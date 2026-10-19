<?php

use App\Enums\ProjectStatus;
use App\Enums\ProjectTransition;
use App\Models\Image;
use App\Models\Log;
use App\Models\Project;
use App\Models\ProjectContent;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPlace;
use App\Models\UserRole;
use App\Queries\AdminAccessQuery;
use App\Support\Admin\ProjectReadiness;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
 * The staff dashboard's micro-projects: who may open what, the list, the state
 * changes, and the create / edit / delete forms. Like every feature test these
 * run against the real hcsyria_db inside a rolled-back transaction, so each uses
 * projects it creates itself (an unmistakable name and number) and never counts
 * on what is already there.
 */

function staff(string $role, array $cities = []): User
{
    $user = User::query()->create([
        'name' => 'Test '.$role,
        'email' => 'staff-'.uniqid().'@example.test',
        'password' => 'correct horse battery',
        'active' => true,
    ]);

    UserRole::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->firstOrCreate(['name' => $role])->id]);

    foreach ($cities as $city) {
        UserPlace::query()->create(['user_id' => $user->id, 'place_id' => $city]);
    }

    AdminAccessQuery::flush();

    return $user;
}

/** A pending project that is ready to publish: English checked, a photo, a donor. */
function adminProject(array $attributes = [], int $photos = 1): Project
{
    $number = random_int(500000, 900000);

    $project = Project::unguarded(fn () => Project::query()->create([
        'MicroProject_ID' => $number,
        'MPE_City' => 3,
        'english_check' => 1,
        'MPE_French' => 0,
        'MPE_Done' => 1,
        'MPE_Published' => 0,
        'hidden' => 0,
        'MPE_PublishDate' => now(),
        'MPE_FundedDate' => '2026-05-01',
        'MPE_AllMoneyNeeded' => 0,
        'MPE_BeneficiaryName' => 'zqa beneficiary',
        'MPE_PartnerName' => '0',
        'MPE_PartnerName2' => '0',
        'MPE_MoneyDonated' => 0,
        'MPE_Video' => '0',
        'MPE_Donor' => DB::table('donors')->where('is_micro', 1)->value('id'),
        'DOB' => 1980,
        'FamilyMembers' => 4,
        'projecttype_id' => DB::table('projecttype')->value('id'),
        'WorkLoss' => 0,
        'Mobile' => '0',
        'latitude' => '36.2',
        'longtitude' => '37.1',
        'SubCategory_ID' => DB::table('subcategory')->value('ID'),
        ...$attributes,
    ]));

    ProjectContent::query()->create([
        'project_id' => $project->id,
        'MicroProject_ID' => $number,
        'MPE_SuccessStory' => '0',
        'MPE_Name' => 'zqa workshop '.$number,
        'MPE_Suffering' => '<p>A workshop that was lost and is being built again.</p>',
        'MPE_ExpSkill' => '',
    ]);

    for ($order = 1; $order <= $photos; $order++) {
        Image::query()->create(['MPI_Path' => "/images/projects/$number/$order.jpg", 'MicroProject_ID' => $number, 'project_id' => $project->id, 'image_order' => $order]);
    }

    return $project;
}

function alertOf(string $type): Closure
{
    return fn (string $event, array $params): bool => ($params['type'] ?? null) === $type;
}

// ---------------------------------------------------------------- who gets in

it('sends a signed-out visitor to the sign-in page', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/admin/home')->assertRedirect('/login');
    $this->get('/admin/projects')->assertRedirect('/login');
});

it('refuses an account that holds no role the dashboard uses', function () {
    $user = User::query()->create(['name' => 'Nobody', 'email' => 'nobody-'.uniqid().'@example.test', 'password' => 'correct horse battery', 'active' => true]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('refuses an account that is switched off, whatever its role', function () {
    $user = staff('Admin');
    $user->update(['active' => false]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('lets every role open the dashboard and the list', function (string $role) {
    $user = staff($role);

    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/home');
    $this->actingAs($user)->get('/admin/home')->assertOk()->assertSee('Dashboard');
    $this->actingAs($user)->get('/admin/projects')->assertOk()->assertSee('Micro projects');
})->with(['Admin', 'Publisher', 'English Editor', 'English Checker', 'Photos Editor', 'Viewer']);

it('keeps the sidebar to what the account can open', function () {
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertSee('Micro projects')->assertDontSee('New project');
    $this->actingAs(staff('English Editor'))->get('/admin/home')->assertSee('New project');
});

it('gives each role only its own pages: create and edit are the English Editor and Admin\'s', function (string $role, bool $mayWrite) {
    $user = staff($role);
    $project = adminProject();

    $create = $this->actingAs($user)->get('/admin/projects/create');
    $edit = $this->actingAs($user)->get("/admin/projects/{$project->id}/edit");

    $mayWrite ? $create->assertOk() : $create->assertForbidden();
    $mayWrite ? $edit->assertOk() : $edit->assertForbidden();
    $this->actingAs($user)->get("/admin/projects/{$project->id}")->assertOk();
})->with([
    ['Admin', true], ['English Editor', true], ['Publisher', false], ['English Checker', false], ['Photos Editor', false], ['Viewer', false],
]);

it('sends staff to the dashboard after signing in, and everyone else to the home page', function () {
    $staff = staff('Viewer');
    $this->post('/login', ['email' => $staff->email, 'password' => 'correct horse battery'])->assertRedirect('/admin/home');

    auth()->logout();
    $plain = User::query()->create(['name' => 'Plain', 'email' => 'plain-'.uniqid().'@example.test', 'password' => 'correct horse battery', 'active' => true]);
    $this->post('/login', ['email' => $plain->email, 'password' => 'correct horse battery'])->assertRedirect('/home');
});

// ------------------------------------------------------------------- the list

it('shows only the cities an account is limited to', function () {
    $aleppo = adminProject(['MPE_City' => 3]);
    $homs = adminProject(['MPE_City' => 1]);
    $user = staff('Viewer', [3]);

    Livewire::actingAs($user)->test('pages::admin.projects.index')
        ->set('search', 'zqa workshop')
        ->assertSee("zqa workshop {$aleppo->MicroProject_ID}")
        ->assertDontSee("zqa workshop {$homs->MicroProject_ID}");

    Livewire::actingAs(staff('Viewer'))->test('pages::admin.projects.index')
        ->set('search', 'zqa workshop')
        ->assertSee("zqa workshop {$homs->MicroProject_ID}");
});

it('finds a project by its number, filters by tab, and counts the tabs', function () {
    $pending = adminProject();
    $online = adminProject(['MPE_Published' => 1]);
    $user = staff('Admin');

    $page = Livewire::actingAs($user)->test('pages::admin.projects.index')->set('search', 'zqa workshop');

    expect($page->viewData('counts'))->toMatchArray(['all' => 2, 'pending' => 1, 'online' => 1]);

    $page->set('tab', 'pending')->assertSee("zqa workshop {$pending->MicroProject_ID}")->assertDontSee("zqa workshop {$online->MicroProject_ID}");
    $page->set('tab', 'all')->set('search', (string) $online->MicroProject_ID)->assertSee("zqa workshop {$online->MicroProject_ID}")->assertDontSee("zqa workshop {$pending->MicroProject_ID}");
});

it('does not read a % or _ in the search as a wildcard', function () {
    adminProject();

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.index')
        ->set('search', 'zqa%workshop')
        ->assertSee('No projects match');
});

it('draws only the buttons the role may use', function () {
    $project = adminProject();

    Livewire::actingAs(staff('Viewer'))->test('pages::admin.projects.index')->set('search', "{$project->MicroProject_ID}")
        ->assertDontSee('Publish')->assertDontSee('Edit');

    Livewire::actingAs(staff('Publisher'))->test('pages::admin.projects.index')->set('search', "{$project->MicroProject_ID}")
        ->assertSee('Publish')->assertDontSee('Edit');

    Livewire::actingAs(staff('English Editor'))->test('pages::admin.projects.index')->set('search', "{$project->MicroProject_ID}")
        ->assertSee('Edit')->assertDontSee('Publish');
});

// ----------------------------------------------------------------- state moves

it('publishes a ready project, as legacy did, and writes the log row', function () {
    $project = adminProject();
    $user = staff('Publisher');

    Livewire::actingAs($user)->test('pages::admin.projects.index')
        ->call('moveProject', $project->id, 'publish')
        ->assertDispatched('admin-alert', alertOf('success'));

    $project->refresh();
    expect($project->MPE_Published)->toBe('1')
        ->and($project->hidden)->toBeFalse()
        ->and($project->MPE_Done)->toBe('1')
        ->and(Log::query()->where(['project_id' => $project->id, 'operation' => 'Made Online', 'user_id' => $user->id, 'category' => 0])->exists())->toBeTrue();
});

it('refuses to publish a project that is not ready, and says what is missing', function (array $attributes, int $photos, string $missing) {
    $project = adminProject($attributes, $photos);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.index')
        ->call('moveProject', $project->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], $missing));

    expect($project->refresh()->MPE_Published)->toBe('0');
})->with([
    'English not checked' => [['english_check' => 0], 1, 'English text is not checked'],
    'no photo' => [[], 0, 'no photos'],
    'no donor' => [['MPE_Donor' => null], 1, 'no donor'],
]);

it('refuses a move the role may not make, whatever the page showed', function (string $role, string $move) {
    $project = adminProject(['MPE_Published' => 1, 'english_check' => 0]);

    Livewire::actingAs(staff($role))->test('pages::admin.projects.index')
        ->call('moveProject', $project->id, $move)
        ->assertDispatched('admin-alert', alertOf('error'));

    $fresh = $project->refresh();
    expect($fresh->MPE_Published)->toBe('1')->and($fresh->english_check)->toBe(0)->and($fresh->hidden)->toBeFalse();
})->with([
    ['Viewer', 'unpublish'], ['Publisher', 'unpublish'], ['English Editor', 'hide'], ['Photos Editor', 'check-english'], ['English Editor', 'check-english'],
]);

it('does not let an account act outside its cities', function () {
    $project = adminProject(['MPE_City' => 1]);

    Livewire::actingAs(staff('Publisher', [3]))->test('pages::admin.projects.index')
        ->call('moveProject', $project->id, 'publish')
        ->assertDispatched('admin-alert', alertOf('error'));

    expect($project->refresh()->MPE_Published)->toBe('0');
});

it('walks a project through every move, with the legacy columns and log text', function () {
    $project = adminProject(['english_check' => 0, 'MPE_Published' => 1]);
    $admin = staff('Admin');
    $page = Livewire::actingAs($admin)->test('pages::admin.projects.index');

    $page->call('moveProject', $project->id, 'unpublish');
    expect($project->refresh()->MPE_Published)->toBe('0');

    $page->call('moveProject', $project->id, 'hide');
    expect($project->refresh())->hidden->toBeTrue()->MPE_Published->toBe('0');

    $page->call('moveProject', $project->id, 'unhide');
    expect($project->refresh()->hidden)->toBeFalse();

    $page->call('moveProject', $project->id, 'check-english');
    expect($project->refresh()->english_check)->toBe(1);

    expect(Log::query()->where(['project_id' => $project->id, 'category' => 0])->orderBy('id')->pluck('operation')->all())
        ->toBe(['Made Offline', 'Made Hidden', 'Made Pending to publish', 'English Checked']);
});

it('offers an undo that is the opposite move, and makes it only for who may', function () {
    $project = adminProject();

    Livewire::actingAs(staff('Publisher'))->test('pages::admin.projects.index')
        ->call('moveProject', $project->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => ($params['undo']['call'] ?? null) === ['moveProject', $project->id, 'unpublish'])
        ->call('moveProject', $project->id, 'unpublish')
        ->assertDispatched('admin-alert', alertOf('error'));

    expect($project->refresh()->MPE_Published)->toBe('1');
});

it('moves several projects at once and counts the ones it skipped', function () {
    $ready = adminProject();
    $notReady = adminProject(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.index')
        ->call('transitionMany', [$ready->id, $notReady->id], 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'warning' && str_contains($params['message'], '1 of 2'));

    expect($ready->refresh()->MPE_Published)->toBe('1')->and($notReady->refresh()->MPE_Published)->toBe('0');
});

it('says so when the project has gone', function () {
    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.index')
        ->call('moveProject', 2147483000, 'publish')
        ->assertDispatched('admin-alert', alertOf('error'));
});

// ------------------------------------------------------------- the project page

it('shows the project, what it needs and its history', function () {
    $project = adminProject(['english_check' => 0], 0);

    Livewire::actingAs(staff('Viewer'))->test('pages::admin.projects.show', ['project' => $project])
        ->assertSee("zqa workshop {$project->MicroProject_ID}")
        ->assertSee('English text checked')
        ->assertSee('At least one photo')
        ->assertSee('No photos yet');
});

it('cuts the story to the few tags the editor makes', function () {
    $project = adminProject();
    ProjectContent::query()->where('project_id', $project->id)->update(['MPE_Suffering' => '<p>Safe</p><script>alert(1)</script><a href="x" onclick="y">link</a>']);

    $this->actingAs(staff('Viewer'))->get("/admin/projects/{$project->id}")
        ->assertSee('<p>Safe</p>', false)
        ->assertDontSee('<script>alert(1)', false)
        ->assertDontSee('onclick', false);
});

// ----------------------------------------------------------- create, edit, delete

function validForm(array $overrides = []): array
{
    return [
        'form.number' => (string) random_int(500000, 900000),
        'form.city_id' => '3',
        'form.kind_id' => (string) DB::table('projecttype')->value('id'),
        'form.category_id' => (string) DB::table('subcategory')->value('Category_ID'),
        'form.sub_category_id' => (string) DB::table('subcategory')->value('ID'),
        'form.name' => 'zqa new workshop',
        'form.beneficiary' => 'zqa beneficiary',
        'form.birth_year' => '1985',
        'form.family_members' => '4',
        'form.story' => '<p>A story that is long enough to be a story.</p><script>x</script>',
        'form.donor_id' => (string) DB::table('donors')->where('is_micro', 1)->value('id'),
        ...$overrides,
    ];
}

function fillForm($page, array $values)
{
    foreach ($values as $key => $value) {
        $page->set($key, $value);
    }

    return $page;
}

it('creates a project as legacy did: funded, waiting, "0" for what was left empty, and a log row', function () {
    $user = staff('English Editor');
    $values = validForm();

    fillForm(Livewire::actingAs($user)->test('pages::admin.projects.form'), $values)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $project = Project::query()->where('MicroProject_ID', (int) $values['form.number'])->firstOrFail();
    expect($project)
        ->MPE_Published->toBe('0')->MPE_Done->toBe('1')->english_check->toBe(0)
        ->MPE_PartnerName->toBe('0')->Mobile->toBe('0')->MPE_Video->toBe('0')->latitude->toBe('0')
        ->and(ProjectContent::query()->where('project_id', $project->id)->value('MPE_Suffering'))->toBe('<p>A story that is long enough to be a story.</p>x')
        ->and(Log::query()->where(['project_id' => $project->id, 'operation' => 'Project has been Created', 'user_id' => $user->id])->exists())->toBeTrue();
});

it('suggests the next free project number', function () {
    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.form');

    expect((int) $page->get('form.number'))->toBe((int) DB::table('projects')->max('MicroProject_ID') + 1);
});

it('asks for what is missing and marks it', function () {
    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.form')
        ->set('form.number', '')->set('form.name', '')->set('form.story', '<p></p>')->set('form.birth_year', '1800')
        ->call('save')
        ->assertHasErrors(['form.number', 'form.name', 'form.story', 'form.beneficiary', 'form.birth_year', 'form.donor_id', 'form.city_id', 'form.sub_category_id'])
        ->assertDispatched('admin-alert', alertOf('error'));
});

it('refuses a number already used in that city, and accepts it in another', function () {
    $taken = adminProject(['MPE_City' => 3]);
    $user = staff('Admin');

    fillForm(Livewire::actingAs($user)->test('pages::admin.projects.form'), validForm(['form.number' => (string) $taken->MicroProject_ID, 'form.city_id' => '3']))
        ->call('save')
        ->assertHasErrors('form.number')
        ->assertDispatched('admin-alert', alertOf('error'));

    fillForm(Livewire::actingAs($user)->test('pages::admin.projects.form'), validForm(['form.number' => (string) $taken->MicroProject_ID, 'form.city_id' => '1']))
        ->call('save')
        ->assertHasNoErrors();
});

it('keeps a limited account to its own cities when it creates', function () {
    fillForm(Livewire::actingAs(staff('English Editor', [3]))->test('pages::admin.projects.form'), validForm(['form.city_id' => '1']))
        ->call('save')
        ->assertHasErrors('form.city_id');
});

it('edits a project and logs one row for each field that changed, with the old and new value', function () {
    $project = adminProject(['MPE_Published' => 1]);
    $user = staff('English Editor');

    Livewire::actingAs($user)->test('pages::admin.projects.form', ['project' => $project])
        ->assertSet('form.name', "zqa workshop {$project->MicroProject_ID}")
        ->set('form.family_members', '9')
        ->set('form.name', 'zqa renamed')
        ->call('save')
        ->assertHasNoErrors();

    $logs = Log::query()->where(['project_id' => $project->id, 'category' => 0])->get()->keyBy('operation');
    expect($project->refresh()->FamilyMembers)->toBe(9)
        ->and($logs->keys()->sort()->values()->all())->toBe(['Family Members Edited', 'Project Name Edited'])
        ->and($logs['Family Members Edited'])->previous_data->toBe('4')->current_data->toBe('9')
        ->and(ProjectContent::query()->where('project_id', $project->id)->value('MPE_Name'))->toBe('zqa renamed');
});

it('does not log an edit when only line endings changed', function () {
    $project = adminProject();
    ProjectContent::query()->where('project_id', $project->id)->update(['MPE_Suffering' => "<p>One</p>\r\n<p>Two</p>"]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.form', ['project' => $project])
        ->set('form.story', "<p>One</p>\n<p>Two</p>")
        ->call('save');

    expect(Log::query()->where(['project_id' => $project->id, 'category' => 0, 'operation' => 'Story Edited'])->exists())->toBeFalse();
});

it('takes a cancelled project off the public list, and puts it back when funded again', function () {
    $project = adminProject(['MPE_Published' => 1]);
    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.form', ['project' => $project]);

    $page->set('form.status', 'cancelled')->call('save');
    expect($project->refresh())->MPE_Done->toBe('2')->MPE_Published->toBe('2');

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.form', ['project' => $project])->set('form.status', 'funded')->call('save');
    expect($project->refresh())->MPE_Done->toBe('1')->MPE_Published->toBe('1');
});

it('writes "0" back for a field the editor emptied, never an empty string', function () {
    $project = adminProject(['Mobile' => '+963 944 000 000']);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.form', ['project' => $project])->set('form.mobile', '')->call('save');

    expect($project->refresh()->Mobile)->toBe('0');
});

it('deletes a project for an Admin and logs it, and for nobody else', function () {
    $project = adminProject();
    $admin = staff('Admin');

    Livewire::actingAs(staff('English Editor'))->test('pages::admin.projects.index')
        ->call('deleteProject', $project->id)->assertDispatched('admin-alert', alertOf('error'));
    expect(Project::query()->whereKey($project->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)->test('pages::admin.projects.index')
        ->call('deleteProject', $project->id)->assertDispatched('admin-alert', alertOf('success'));

    expect(Project::query()->whereKey($project->id)->exists())->toBeFalse()
        ->and(Image::query()->where('project_id', $project->id)->exists())->toBeFalse()
        ->and(Log::query()->where(['project_id' => $project->id, 'operation' => 'Project Deleted', 'user_id' => $admin->id])->exists())->toBeTrue();
});

it('will not delete a project that has success stories, and says why', function () {
    $project = adminProject();
    // A copy of a real success story, pointed at this project.
    DB::table('successes')->insert([...(array) DB::table('successes')->first(), 'id' => null, 'project_id' => $project->id]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.projects.index')
        ->call('deleteProject', $project->id)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'success stories'));

    expect(Project::query()->whereKey($project->id)->exists())->toBeTrue();
});

// ------------------------------------------------------------------ small pieces

it('reads the state of a project from the three legacy columns', function () {
    expect(ProjectStatus::of('1', '1', 0))->toBe(ProjectStatus::Online)
        ->and(ProjectStatus::of('0', '1', 0))->toBe(ProjectStatus::Pending)
        ->and(ProjectStatus::of('0', '1', 1))->toBe(ProjectStatus::Hidden)
        ->and(ProjectStatus::of('2', '2', 0))->toBe(ProjectStatus::Cancelled)
        ->and(ProjectStatus::of('1', '2', 0))->toBe(ProjectStatus::Online);
});

it('knows when a move applies', function () {
    $pending = (object) ['published' => 0, 'hidden' => false, 'english_check' => 0];
    $online = (object) ['published' => 1, 'hidden' => false, 'english_check' => 1];
    $hidden = (object) ['published' => 0, 'hidden' => true, 'english_check' => 1];

    expect(ProjectTransition::Publish->appliesTo($pending))->toBeTrue()
        ->and(ProjectTransition::Publish->appliesTo($online))->toBeFalse()
        ->and(ProjectTransition::Publish->appliesTo($hidden))->toBeFalse()
        ->and(ProjectTransition::Unpublish->appliesTo($online))->toBeTrue()
        ->and(ProjectTransition::Unhide->appliesTo($hidden))->toBeTrue()
        ->and(ProjectTransition::CheckEnglish->appliesTo($pending))->toBeTrue()
        ->and(ProjectTransition::CheckEnglish->appliesTo($online))->toBeFalse()
        ->and(ProjectTransition::Publish->inverse())->toBe(ProjectTransition::Unpublish)
        ->and(ProjectTransition::CheckEnglish->inverse())->toBeNull();
});

it('treats the text 0 as no map location', function () {
    $base = ['english_check' => 1, 'photos' => 1, 'donor_id' => 1, 'funded' => '2026-01-01'];

    expect(ProjectReadiness::checks((object) [...$base, 'latitude' => '0'])['location'])->toBeFalse()
        ->and(ProjectReadiness::checks((object) [...$base, 'latitude' => '36.2'])['location'])->toBeTrue()
        ->and(ProjectReadiness::blockers((object) [...$base, 'latitude' => '0']))->toBe([]);
});
