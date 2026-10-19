<?php

use App\Models\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/*
 * The newsletter's own lists in the admin: tags, editions and subscribers. Who may
 * open each and do what, the legacy wording of the log rows, and what each refuses.
 * Like every feature test these run against hcsyria_db inside a rolled-back
 * transaction and use rows they create themselves.
 */

function listLogs(int $category, string $like)
{
    return Log::query()->where('category', $category)->where('operation', 'like', $like);
}

function makeTag(string $name = 'zqt tag'): int
{
    return (int) DB::table('tags')->insertGetId(['name' => $name.' '.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
}

function makeEdition(string $name = 'zqe edition', int $online = 0): int
{
    return (int) DB::table('editions')->insertGetId(['name' => $name.' '.substr(uniqid(), -6), 'name_french' => '', 'online' => $online, 'created_at' => now(), 'updated_at' => now()]);
}

function makeSubscriber(?string $mail = null): int
{
    return (int) DB::table('newsletter_subscribers')->insertGetId(['mail' => $mail ?? 'zqs-'.uniqid().'@example.test', 'active' => 1, 'token' => substr(str_repeat(uniqid(), 5), 0, 50)]);
}

function onPage(string $page, string $role)
{
    return Livewire::actingAs(staff($role))->test('pages::admin.news.'.$page);
}

function alerted(string $type, ?string $contains = null): Closure
{
    return fn (string $event, array $params): bool => $params['type'] === $type && ($contains === null || str_contains($params['message'], $contains));
}

// ------------------------------------------------------------------------- tags

it('lets every role see the tags with how many items use each, and only the right ones change them', function () {
    $tag = makeTag();
    $news = adminNews();
    DB::table('news_tags')->insert(['news_id' => $news->id, 'tag_id' => $tag]);

    onPage('tags', 'Viewer')->assertSee(DB::table('tags')->where('id', $tag)->value('name'))->assertSee('1 item')->assertDontSee('Add tag')->assertDontSee('Rename')->assertDontSee('Delete tag');
    onPage('tags', 'English Editor')->assertSee('Add tag')->assertSee('Rename')->assertDontSee('Delete tag');
    onPage('tags', 'Admin')->assertSee('Delete tag');
});

it('makes a tag with legacy\'s log text, and refuses a twin whatever its capitals', function () {
    $user = staff('English Editor');
    $page = Livewire::actingAs($user)->test('pages::admin.news.tags');
    $name = 'zqt fresh '.uniqid();

    $page->set('newName', $name)->call('add')->assertHasNoErrors()->assertSet('newName', '')->assertDispatched('admin-alert', alerted('success', $name));
    expect(DB::table('tags')->where('name', $name)->exists())->toBeTrue()
        ->and(listLogs(35, "Tag Created: $name")->where('user_id', $user->id)->exists())->toBeTrue();

    $page->set('newName', mb_strtoupper($name))->call('add')->assertDispatched('admin-alert', alerted('error', 'already a tag'));
    expect(DB::table('tags')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->count())->toBe(1);
});

it('asks for a tag of 1 to 50 characters', function (string $value) {
    onPage('tags', 'Admin')->set('newName', $value)->call('add')->assertHasErrors('newName');
})->with(['empty' => '', 'blank' => '   ', 'too long' => str_repeat('t', 51)]);

it('renames a tag, logging old and new, and refuses a name another tag has', function () {
    $tag = makeTag('zqt old');
    $other = DB::table('tags')->where('id', makeTag('zqt other'))->value('name');
    $old = DB::table('tags')->where('id', $tag)->value('name');
    $page = onPage('tags', 'English Editor');

    $page->call('startEdit', $tag, $old)->assertSet('editingId', $tag)->set('editName', $other)->call('saveEdit')->assertDispatched('admin-alert', alerted('error'));
    expect(DB::table('tags')->where('id', $tag)->value('name'))->toBe($old);

    $page->set('editName', 'zqt renamed '.$tag)->call('saveEdit')->assertSet('editingId', null);
    expect(DB::table('tags')->where('id', $tag)->value('name'))->toBe('zqt renamed '.$tag)
        ->and(listLogs(35, 'Tag Updated: %')->where(['previous_data' => $old, 'current_data' => 'zqt renamed '.$tag])->exists())->toBeTrue();
});

it('deletes a tag for an Admin only, taking it off its news items', function () {
    $tag = makeTag();
    $news = adminNews();
    DB::table('news_tags')->insert(['news_id' => $news->id, 'tag_id' => $tag]);
    $name = DB::table('tags')->where('id', $tag)->value('name');

    onPage('tags', 'English Editor')->call('remove', $tag, $name)->assertForbidden();
    expect(DB::table('tags')->where('id', $tag)->exists())->toBeTrue();

    onPage('tags', 'Admin')->call('remove', $tag, $name)->assertDispatched('admin-alert', alerted('success', 'deleted'));
    expect(DB::table('tags')->where('id', $tag)->exists())->toBeFalse()
        ->and(DB::table('news_tags')->where('tag_id', $tag)->exists())->toBeFalse()
        ->and(DB::table('news_tags')->where('news_id', $news->id)->count())->toBe(1)
        ->and(listLogs(35, "Tag Deleted: $name")->exists())->toBeTrue();
});

it('refuses a tag change from a role that cannot edit', function () {
    onPage('tags', 'Viewer')->set('newName', 'zqt nope')->call('add')->assertForbidden();
    expect(DB::table('tags')->where('name', 'zqt nope')->exists())->toBeFalse();
});

// ---------------------------------------------------------------------- editions

it('lets every role see the editions with how many items belong to each, and only the right ones change them', function () {
    $edition = makeEdition();
    adminNews(['edition_id' => $edition]);

    onPage('editions', 'Viewer')->assertSee(DB::table('editions')->where('id', $edition)->value('name'))->assertSee('1 item')->assertDontSee('Add edition')->assertDontSee('Put online');
    onPage('editions', 'English Editor')->assertSee('Add edition')->assertDontSee('Put online')->assertDontSee('Delete edition');
    onPage('editions', 'Publisher')->assertSee('Put online')->assertDontSee('Add edition');
    onPage('editions', 'Admin')->assertSee('Add edition')->assertSee('Put online')->assertSee('Delete edition');
});

it('makes an edition offline, with its French name, and refuses a twin', function () {
    $user = staff('English Editor');
    $page = Livewire::actingAs($user)->test('pages::admin.news.editions');
    $name = 'zqe '.substr(uniqid(), -6);

    $page->set('name', $name)->set('nameFrench', 'zqe fr')->call('add')->assertHasNoErrors()->assertDispatched('admin-alert', alerted('success', $name));
    $row = DB::table('editions')->where('name', $name)->first();
    expect($row)->online->toBe(0)->name_french->toBe('zqe fr')
        ->and(listLogs(4, 'Edition created')->where(['project_id' => $row->id, 'user_id' => $user->id])->exists())->toBeTrue();

    $page->set('name', mb_strtoupper($name))->call('add')->assertDispatched('admin-alert', alerted('error', 'already an edition'));
    expect(DB::table('editions')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->count())->toBe(1);
});

it('asks for an edition name of 1 to 50 characters', function (string $value) {
    onPage('editions', 'Admin')->set('name', $value)->call('add')->assertHasErrors('name');
})->with(['empty' => '', 'too long' => str_repeat('e', 51)]);

it('puts an edition online and offline for those who publish, and logs it', function () {
    $edition = makeEdition();
    $user = staff('Publisher');
    $page = Livewire::actingAs($user)->test('pages::admin.news.editions');

    $page->call('setOnline', $edition, true)->assertDispatched('admin-alert', alerted('success', 'is online'));
    expect(DB::table('editions')->where('id', $edition)->value('online'))->toBe(1);

    $page->call('setOnline', $edition, true);
    $page->call('setOnline', $edition, false)->assertDispatched('admin-alert', alerted('success', 'is offline'));
    expect(DB::table('editions')->where('id', $edition)->value('online'))->toBe(0)
        ->and(Log::query()->where(['category' => 4, 'project_id' => $edition])->orderBy('id')->pluck('operation')->all())->toBe(['Edition made online', 'Edition made offline']);

    onPage('editions', 'English Editor')->call('setOnline', $edition, true)->assertForbidden();
    expect(DB::table('editions')->where('id', $edition)->value('online'))->toBe(0);
});

it('refuses to delete an edition items belong to, and deletes an unused one for an Admin only', function () {
    $used = makeEdition();
    $unused = makeEdition();
    adminNews(['edition_id' => $used]);
    adminNews(['edition_id' => $used]);

    onPage('editions', 'Admin')->call('remove', $used)->assertDispatched('admin-alert', alerted('error', 'belongs to 2 news items'));
    expect(DB::table('editions')->where('id', $used)->exists())->toBeTrue();

    onPage('editions', 'English Editor')->call('remove', $unused)->assertForbidden();
    expect(DB::table('editions')->where('id', $unused)->exists())->toBeTrue();

    onPage('editions', 'Admin')->call('remove', $unused)->assertDispatched('admin-alert', alerted('success', 'deleted'));
    expect(DB::table('editions')->where('id', $unused)->exists())->toBeFalse()
        ->and(listLogs(4, 'Edition deleted')->where('project_id', $unused)->exists())->toBeTrue();
});

// ------------------------------------------------------------------- subscribers

it('lets only the Admin and the Publisher open the subscribers', function (string $role, bool $may) {
    $this->actingAs(staff($role))->get('/admin/news/subscribers')->assertStatus($may ? 200 : 403);
})->with([['Admin', true], ['Publisher', true], ['English Editor', false], ['English Checker', false], ['Photos Editor', false], ['Viewer', false]]);

it('lists and searches the subscribers a page at a time', function () {
    $mine = 'zqs-find-'.uniqid().'@example.test';
    makeSubscriber($mine);
    makeSubscriber();

    $page = onPage('subscribers', 'Publisher')->set('search', 'zqs-find-')->assertSee($mine);
    expect($page->viewData('subscribers')->total())->toBe(1);

    $page->set('search', '100%_')->assertSee('No subscribers match');
    expect(onPage('subscribers', 'Publisher')->viewData('subscribers')->perPage())->toBe(25);
});

it('removes a subscriber for an Admin only, logging it without the address', function () {
    $mail = 'zqs-gone-'.uniqid().'@example.test';
    $id = makeSubscriber($mail);
    $admin = staff('Admin');

    onPage('subscribers', 'Publisher')->call('remove', $id)->assertForbidden()->assertDontSee('Remove '.$mail);
    expect(DB::table('newsletter_subscribers')->where('id', $id)->exists())->toBeTrue();

    $page = Livewire::actingAs($admin)->test('pages::admin.news.subscribers');
    $page->call('remove', $id)->assertDispatched('admin-alert', alerted('success', 'removed'));
    expect(DB::table('newsletter_subscribers')->where('id', $id)->exists())->toBeFalse()
        ->and(Log::query()->where(['category' => 6, 'project_id' => $id, 'operation' => 'Subscriber deleted', 'user_id' => $admin->id])->value('current_data'))->toBeNull()
        ->and(Log::query()->where(['category' => 6, 'project_id' => $id])->get()->contains(fn ($row) => str_contains((string) $row->operation.$row->previous_data.$row->current_data, $mail)))->toBeFalse();

    $page->call('remove', $id)->assertDispatched('admin-alert', alerted('error', 'no longer on the list'));
});

// ---------------------------------------------------------------------- the tabs

it('shows the newsletter tabs, and the subscribers tab only to those who may open it', function () {
    $this->actingAs(staff('Viewer'))->get('/admin/news')->assertSee('Tags')->assertSee('Editions')->assertDontSee('Subscribers');
    $this->actingAs(staff('Publisher'))->get('/admin/news/tags')->assertSee('Subscribers')->assertSee('Newsletter sections');
});

it('knows the capabilities the lists ask for', function () {
    $gate = fn (string $role, string $ability): bool => Gate::forUser(staff($role))->allows($ability);

    expect($gate('Admin', 'admin.delete-subscribers'))->toBeTrue()
        ->and($gate('Publisher', 'admin.view-subscribers'))->toBeTrue()
        ->and($gate('Publisher', 'admin.delete-subscribers'))->toBeFalse()
        ->and($gate('Viewer', 'admin.view'))->toBeTrue()
        ->and($gate('Viewer', 'admin.edit'))->toBeFalse();
});
