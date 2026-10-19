<?php

use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Models\Success;
use App\Models\SuccessContent;
use App\Queries\ContentLookups;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The staff dashboard's success stories: who may open what, the list, the state
 * changes (the same shared code as the projects' and initiatives'), the create /
 * edit / delete forms with their picture, and the exclusion of Homework Room.
 * Like every feature test these run against hcsyria_db inside a rolled-back
 * transaction and use stories they create themselves.
 */

function successForm(array $overrides = []): array
{
    return [
        'form.what_category_id' => '5',
        'form.who_category_id' => '3',
        'form.sub_what_category_id' => '17',
        'form.place_id' => '1',
        'form.title' => 'zqs new success',
        'form.text' => '<p>A long enough story about a student.</p><script>x</script>',
        ...$overrides,
    ];
}

function successPage(string $role = 'Admin', array $params = [])
{
    return Livewire::actingAs(staff($role))->test('pages::admin.successes.form', $params);
}

// ----------------------------------------------------------- who gets in, the list

it('lets every role open the list and a story', function (string $role) {
    $success = adminSuccess();
    $user = staff($role);

    $this->actingAs($user)->get('/admin/successes')->assertOk()->assertSee('Successes');
    $this->actingAs($user)->get("/admin/successes/{$success->id}")->assertOk()->assertSee("zqs success {$success->id}");
})->with(['Admin', 'Publisher', 'English Editor', 'English Checker', 'Photos Editor', 'Viewer']);

it('gives create and edit to the English Editor and the Admin only', function (string $role, bool $mayWrite) {
    $success = adminSuccess();
    $user = staff($role);

    $create = $this->actingAs($user)->get('/admin/successes/create');
    $edit = $this->actingAs($user)->get("/admin/successes/{$success->id}/edit");

    $mayWrite ? $create->assertOk() : $create->assertForbidden();
    $mayWrite ? $edit->assertOk() : $edit->assertForbidden();
})->with([['Admin', true], ['English Editor', true], ['Publisher', false], ['English Checker', false], ['Photos Editor', false], ['Viewer', false]]);

it('shows the section in the sidebar, after Initiatives, and in the Ctrl K list', function () {
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertSeeInOrder(['Micro projects', 'Initiatives', 'Successes'])->assertDontSee('New success');
    $this->actingAs(staff('English Editor'))->get('/admin/home')->assertSee('New success');
});

it('lists the managed categories, finds by title, filters, and counts the tabs', function () {
    $pending = adminSuccess();
    $online = adminSuccess(['online' => '1', 'who_category_id' => 1]);

    $page = Livewire::actingAs(staff('Viewer'))->test('pages::admin.successes.index')->set('search', 'zqs success');

    expect($page->viewData('counts'))->toMatchArray(['all' => 2, 'pending' => 1, 'online' => 1]);
    $page->set('tab', 'online')->assertSee("zqs success {$online->id}")->assertDontSee("zqs success {$pending->id}");
    $page->set('tab', 'all')->set('serves', '1')->assertSee("zqs success {$online->id}")->assertDontSee("zqs success {$pending->id}");
});

it('has nothing to do with Homework Room: not listed, not opened, not changed', function () {
    $homework = adminSuccess(['what_category_id' => 6, 'sub_what_category_id' => null, 'who_category_id' => 4]);
    $admin = staff('Admin');

    Livewire::actingAs($admin)->test('pages::admin.successes.index')->set('search', "zqs success {$homework->id}")->assertSee('No successes match');
    expect(Livewire::actingAs($admin)->test('pages::admin.successes.index')->viewData('categories')->pluck('id')->all())->not->toContain(6);

    $this->actingAs($admin)->get("/admin/successes/{$homework->id}")->assertForbidden();
    $this->actingAs($admin)->get("/admin/successes/{$homework->id}/edit")->assertForbidden();

    Livewire::actingAs($admin)->test('pages::admin.successes.index')->call('moveItem', $homework->id, 'publish')->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
    Livewire::actingAs($admin)->test('pages::admin.successes.index')->call('deleteItem', $homework->id)->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
    expect($homework->refresh()->online)->toBe('0')->and(Success::query()->whereKey($homework->id)->exists())->toBeTrue();
});

it('draws only the buttons the role may use, and no hide button at all', function () {
    $success = adminSuccess();

    Livewire::actingAs(staff('Viewer'))->test('pages::admin.successes.index')->set('search', "zqs success {$success->id}")->assertDontSee('Publish')->assertDontSee('Edit');
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.successes.index')->set('search', "zqs success {$success->id}")->assertSee('Publish')->assertDontSee('Hide');
    Livewire::actingAs(staff('English Editor'))->test('pages::admin.successes.index')->set('search', "zqs success {$success->id}")->assertSee('Edit')->assertDontSee('Publish');
});

// -------------------------------------------------------------------- state moves

it('publishes a story whose English is checked, with legacy\'s log text', function () {
    $success = adminSuccess();
    $user = staff('Publisher');

    Livewire::actingAs($user)->test('pages::admin.successes.index')
        ->call('moveItem', $success->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success' && str_contains($params['message'], "Success #{$success->id} is online"));

    expect($success->refresh()->online)->toBe('1')
        ->and(successLogs($success)->where(['operation' => 'Made online', 'user_id' => $user->id])->exists())->toBeTrue();
});

it('will not publish a story whose English is not checked', function () {
    $success = adminSuccess(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.successes.index')
        ->call('moveItem', $success->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'English text is not checked'));

    expect($success->refresh()->online)->toBe('0');
});

it('takes a story offline and has its English checked, and refuses hiding', function () {
    $success = adminSuccess(['online' => '1', 'english_check' => 0]);
    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.successes.index');

    $page->call('moveItem', $success->id, 'unpublish');
    $page->call('moveItem', $success->id, 'check-english');
    $page->call('moveItem', $success->id, 'hide')->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

    expect($success->refresh())->online->toBe('0')->english_check->toBe(1)
        ->and(successLogs($success)->orderBy('id')->pluck('operation')->all())->toBe(['Made offline', 'English Checked']);
});

it('refuses a move the role may not make', function (string $role, string $move) {
    $success = adminSuccess(['online' => '1', 'english_check' => 0]);

    Livewire::actingAs(staff($role))->test('pages::admin.successes.index')
        ->call('moveItem', $success->id, $move)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

    expect($success->refresh())->online->toBe('1')->english_check->toBe(0);
})->with([['Viewer', 'unpublish'], ['Publisher', 'unpublish'], ['English Editor', 'check-english'], ['Photos Editor', 'check-english']]);

it('moves several at once and counts the ones it skipped', function () {
    $ready = adminSuccess();
    $notReady = adminSuccess(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.successes.index')
        ->call('transitionMany', [$ready->id, $notReady->id], 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'warning' && str_contains($params['message'], '1 of 2 successes'));

    expect($ready->refresh()->online)->toBe('1')->and($notReady->refresh()->online)->toBe('0');
});

// ------------------------------------------------------------ create, edit, delete

describe('with GD', function () {
    beforeEach(function () {
        $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
        File::ensureDirectoryExists($this->media.'/images');
        config(['hcsyria.media_root' => $this->media]);
    });

    afterEach(fn () => File::deleteDirectory($this->media));

    it('creates a DISC story as legacy did: offline, unchecked, "0" for no video, a 500 square picture, and a log row', function () {
        $user = staff('English Editor');

        Livewire::actingAs($user)->test('pages::admin.successes.form')
            ->fill(successForm())->set('picture', UploadedFile::fake()->image('p.jpg', 1200, 800))
            ->call('save')->assertHasNoErrors()->assertRedirect();

        $content = SuccessContent::query()->where('title', 'zqs new success')->firstOrFail();
        $success = Success::query()->findOrFail($content->success_id);

        expect($success)->online->toBe('0')->english_check->toBe(0)->video->toBe('0')->inner_image_path->toBe('0')->what_category_id->toBe(5)->sub_what_category_id->toBe(17)
            ->and($success->image_path)->toStartWith('/images/successes/youth/')
            ->and(array_slice(getimagesize($this->media.$success->image_path), 0, 2))->toBe([500, 500])
            ->and($content->text)->toBe('<p>A long enough story about a student.</p>x')
            ->and(successLogs($success)->where(['operation' => 'Success Created', 'user_id' => $user->id])->exists())->toBeTrue();
    });

    it('asks for what each category needs', function (array $values, array $errors) {
        successPage()->fill(successForm($values))->set('picture', UploadedFile::fake()->image('p.jpg', 600, 600))->call('save')->assertHasErrors($errors);
    })->with([
        'DISC needs its kind and place' => [['form.sub_what_category_id' => '', 'form.place_id' => ''], ['form.sub_what_category_id', 'form.place_id']],
        'Study Zone needs a place and a centre' => [['form.what_category_id' => '7', 'form.place_id' => '', 'form.sz_center_id' => ''], ['form.place_id', 'form.sz_center_id']],
        'micro projects need a project' => [['form.what_category_id' => '10', 'form.project_id' => ''], ['form.project_id']],
        'Homework Room is not a category' => [['form.what_category_id' => '6'], ['form.what_category_id']],
        'a title and text' => [['form.title' => '', 'form.text' => '<p></p>'], ['form.title', 'form.text']],
    ]);

    it('needs a picture for a new story, and refuses one that is not a picture', function () {
        successPage()->fill(successForm())->call('save')->assertHasErrors('picture');
        successPage()->fill(successForm())->set('picture', UploadedFile::fake()->createWithContent('p.jpg', '<?php echo 1;'))->call('save')
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

        expect(SuccessContent::query()->where('title', 'zqs new success')->exists())->toBeFalse();
    });

    it('links a micro-project story to a project, and only an existing one', function () {
        $project = adminProject(['MPE_Published' => 1]);

        successPage()->fill(successForm(['form.what_category_id' => '10', 'form.project_id' => '99999999']))->set('picture', UploadedFile::fake()->image('p.jpg', 600, 600))->call('save')->assertHasErrors('form.project_id');

        successPage()->fill(successForm(['form.what_category_id' => '10', 'form.project_id' => (string) $project->id]))->set('picture', UploadedFile::fake()->image('p.jpg', 600, 600))->call('save')->assertHasNoErrors();

        $success = Success::query()->findOrFail(SuccessContent::query()->where('title', 'zqs new success')->value('success_id'));
        expect($success)->project_id->toBe($project->id)->sub_what_category_id->toBeNull()->and($success->place_id)->toBe(0);
    });

    it('finds linkable projects by number or name, online ones only', function () {
        $online = adminProject(['MPE_Published' => 1]);
        $pending = adminProject();

        $found = ContentLookups::linkableProjects('zqa workshop');

        expect($found->pluck('id')->all())->toContain($online->id)->not->toContain($pending->id);
        expect(ContentLookups::linkableProjects((string) $online->MicroProject_ID)->pluck('id')->all())->toContain($online->id);
    });

    it('edits a story and logs one row for each change, worded as legacy did', function () {
        $success = adminSuccess();
        $user = staff('English Editor');

        successPage('English Editor', ['success' => $success])
            ->assertSet('form.title', "zqs success {$success->id}")
            ->set('form.title', 'zqs renamed')->set('form.place_id', '2')->set('form.video', 'https://www.youtube.com/embed/abc')
            ->call('save')->assertHasNoErrors();

        $logs = successLogs($success)->get()->keyBy('operation');
        expect($logs->keys()->all())->toEqualCanonicalizing(['Title edited', 'Place changed', 'Video Edited'])
            ->and($logs['Title edited'])->previous_data->toBe("zqs success {$success->id}")->current_data->toBe('zqs renamed')
            ->and($logs['Place changed'])->previous_data->toBe('Homs')->current_data->toBe('Damascus');
    });

    it('does not log an edit when only line endings changed', function () {
        $success = adminSuccess([], ['text' => "<p>One</p>\r\n<p>Two</p>"]);

        successPage('Admin', ['success' => $success])->set('form.text', "<p>One</p>\n<p>Two</p>")->call('save');

        expect(successLogs($success)->where('operation', 'Text edited')->exists())->toBeFalse();
    });

    it('replaces a story\'s picture and deletes the old file', function () {
        $success = adminSuccess();
        $page = successPage('Admin', ['success' => $success]);
        $page->set('picture', UploadedFile::fake()->image('one.jpg', 800, 800))->call('save');
        $first = $success->refresh()->image_path;

        successPage('Admin', ['success' => $success])->set('picture', UploadedFile::fake()->image('two.jpg', 800, 800))->call('save');
        $second = $success->refresh()->image_path;

        expect($second)->not->toBe($first)->and(File::exists($this->media.$first))->toBeFalse()->and(File::exists($this->media.$second))->toBeTrue()
            ->and(successLogs($success)->where('operation', 'Image changed')->count())->toBe(2);
    });

    it('deletes a story for an Admin only, with its picture, and logs it', function () {
        $success = adminSuccess();
        successPage('Admin', ['success' => $success])->set('picture', UploadedFile::fake()->image('one.jpg', 800, 800))->call('save');
        $path = $success->refresh()->image_path;
        $admin = staff('Admin');

        Livewire::actingAs(staff('English Editor'))->test('pages::admin.successes.index')->call('deleteItem', $success->id)
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
        expect(Success::query()->whereKey($success->id)->exists())->toBeTrue();

        Livewire::actingAs($admin)->test('pages::admin.successes.index')->call('deleteItem', $success->id)
            ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');

        expect(Success::query()->whereKey($success->id)->exists())->toBeFalse()
            ->and(SuccessContent::query()->where('success_id', $success->id)->exists())->toBeFalse()
            ->and(File::exists($this->media.$path))->toBeFalse()
            ->and(successLogs($success)->where(['operation' => 'Success deleted', 'user_id' => $admin->id])->exists())->toBeTrue();
    });
})->skip(! extension_loaded('gd'), 'The GD extension is not enabled.');

// ------------------------------------------------------------------ small pieces

it('has no Homework Room among the categories anything here offers', function () {
    expect(ContentLookups::SUCCESS_CATEGORIES)->not->toContain(6)
        ->and(ContentLookups::TESTIMONY_CATEGORIES)->not->toContain(6)
        ->and(ContentLookups::initiativeCategories()->pluck('id')->all())->not->toContain(6)
        ->and(ContentLookups::EXCLUDED_CATEGORIES)->toBe([6]);

    $initiative = adminInitiative(['what_category_id' => 6]);
    test()->actingAs(staff('Admin'))->get("/admin/initiatives/{$initiative->id}")->assertForbidden();
});

it('tells the three kinds of content apart', function () {
    expect(ContentKind::of(adminSuccess()))->toBe(ContentKind::Success)
        ->and(ContentKind::Success->route('edit'))->toBe('admin.successes.edit')
        ->and(ContentKind::Success->supports(ContentTransition::Hide))->toBeFalse()
        ->and(ContentKind::Initiative->supports(ContentTransition::Hide))->toBeTrue()
        ->and(PhotoFiles::bigPath('/images/a/1.jpg'))->toBe('/images/a/1b.jpg');
});
