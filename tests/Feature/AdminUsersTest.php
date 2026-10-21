<?php

use App\Actions\Admin\ManageUsers;
use App\Exceptions\AdminActionRefused;
use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/*
 * The staff accounts in the admin: list, create, edit (roles and cities), activate,
 * deactivate and delete, for the Admin only. Like every feature test these run against
 * hcsyria_db inside a rolled-back transaction, with accounts they create themselves.
 */

function userForm(array $overrides = []): array
{
    return [
        'form.name' => 'zqu New Person',
        'form.email' => 'zqu-'.uniqid().'@example.test',
        'form.password' => 'a long enough password 1',
        'form.roles' => [(string) Role::query()->where('name', 'Viewer')->value('id')],
        'form.places' => [],
        ...$overrides,
    ];
}

function usersPage(array $params = [], ?User $as = null)
{
    return Livewire::actingAs($as ?? staff('Admin'))->test('pages::admin.users.form', $params);
}

function onlyAdmin(): User
{
    DB::table('users')->update(['active' => 0]);

    return staff('Admin');
}

function userLogs(User $user)
{
    return Log::query()->where(['project_id' => $user->id, 'category' => 12]);
}

it('is closed to every role but the Admin', function (string $role) {
    $this->actingAs(staff($role))->get('/admin/users')->assertForbidden();
    $this->actingAs(staff($role))->get('/admin/users/create')->assertForbidden();
    Livewire::actingAs(staff($role))->test('pages::admin.users.index')->assertForbidden();
})->with(['Viewer', 'Publisher', 'English Editor', 'Photos Editor', 'English Checker']);

it('shows the menu entry to the Admin only', function () {
    $this->actingAs(staff('Admin'))->get('/admin/home')->assertSee('Users')->assertSee('Administration');
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertDontSee('Administration');
});

it('lists accounts with their roles, with tabs, a role filter and a search, and never the password', function () {
    $active = staff('Publisher', [1, 2]);
    $active->update(['name' => 'zqu active person']);
    $off = staff('Viewer');
    $off->update(['name' => 'zqu off person', 'active' => false]);

    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.users.index')->set('search', 'zqu');
    $page->assertSee('zqu active person')->assertSee('zqu off person')->assertSee('Publisher')->assertSee('Damascus, Homs')->assertDontSee($active->password);

    $page->set('tab', 'inactive')->assertSee('zqu off person')->assertDontSee('zqu active person');
    $page->set('tab', 'all')->set('role', (string) Role::query()->where('name', 'Publisher')->value('id'))->assertSee('zqu active person')->assertDontSee('zqu off person');
});

it('asks for a name, a unique email and a strong password', function (array $values, array $errors) {
    usersPage()->fill(userForm($values))->call('save')->assertHasErrors($errors);
})->with([
    'nothing' => [['form.name' => '', 'form.email' => '', 'form.password' => ''], ['form.name', 'form.email', 'form.password']],
    'a short password' => [['form.password' => 'short1'], ['form.password']],
    'a password without numbers' => [['form.password' => 'only letters in this one'], ['form.password']],
    'an email that is not one' => [['form.email' => 'nope'], ['form.email']],
    'no role' => [['form.roles' => []], ['roles']],
    'a role that does not exist' => [['form.roles' => ['999999']], ['form.roles.0']],
    'a city that does not exist' => [['form.places' => ['999999']], ['form.places.0']],
]);

it('refuses an email that another account has', function () {
    $other = staff('Viewer');

    usersPage()->fill(userForm(['form.email' => strtoupper($other->email)]))->call('save')->assertHasErrors('form.email');
});

it('creates an active account with its roles and cities, hashes the password and logs it without the password', function () {
    $admin = staff('Admin');
    $values = userForm(['form.places' => ['1', '3']]);

    usersPage(as: $admin)->fill($values)->call('save')->assertHasNoErrors()->assertRedirect(route('admin.users.index'));

    $user = User::query()->where('email', $values['form.email'])->firstOrFail();
    expect($user)->active->toBeTrue()->name->toBe('zqu New Person')
        ->and(Hash::check('a long enough password 1', $user->password))->toBeTrue()
        ->and(DB::table('role_user')->where('user_id', $user->id)->pluck('role_id')->all())->toBe([(int) $values['form.roles'][0]])
        ->and(DB::table('place_user')->where('user_id', $user->id)->orderBy('place_id')->pluck('place_id')->all())->toBe([1, 3]);

    $log = userLogs($user)->where('operation', 'User Created')->firstOrFail();
    expect($log->user_id)->toBe($admin->id)->and(json_encode($log->getAttributes()))->not->toContain('long enough');
});

it('edits the name and email, keeps the password when left empty, and changes it when given', function () {
    $user = staff('Viewer');
    $hash = $user->password;

    usersPage(['user' => $user])->assertSet('form.email', $user->email)
        ->set('form.name', 'zqu renamed')->set('form.email', 'zqu-renamed@example.test')->call('save')->assertHasNoErrors();
    expect($user->refresh())->name->toBe('zqu renamed')->email->toBe('zqu-renamed@example.test')->and($user->password)->toBe($hash);

    usersPage(['user' => $user])->set('form.password', 'another long password 2')->call('save')->assertHasNoErrors();
    expect(Hash::check('another long password 2', $user->refresh()->password))->toBeTrue()
        ->and(userLogs($user)->where('operation', 'User Edited: Password changed')->exists())->toBeTrue()
        ->and(userLogs($user)->where('operation', 'User Edited: Name')->first())->previous_data->toBe('Test Viewer')->current_data->toBe('zqu renamed');
});

it('changes roles and cities as chosen, including clearing the cities, and logs them by name', function () {
    $user = staff('Viewer', [1]);
    $publisher = (string) Role::query()->where('name', 'Publisher')->value('id');

    usersPage(['user' => $user])->set('form.roles', [$publisher])->set('form.places', [])->call('save')->assertHasNoErrors();

    expect(DB::table('role_user')->where('user_id', $user->id)->pluck('role_id')->all())->toBe([(int) $publisher])
        ->and(DB::table('place_user')->where('user_id', $user->id)->count())->toBe(0)
        ->and(userLogs($user)->where('operation', 'User Edited: Roles')->first())->previous_data->toBe('Viewer')->current_data->toBe('Publisher')
        ->and(userLogs($user)->where('operation', 'User Edited: Cities')->first())->previous_data->toBe('Homs')->current_data->toBe('none');
});

it('leaves an account\'s page permissions and Study Zone centres alone', function () {
    $user = staff('Viewer');
    DB::table('permission_user')->insert(['user_id' => $user->id, 'permission_id' => 1]);

    usersPage(['user' => $user])->set('form.name', 'zqu kept')->call('save')->assertHasNoErrors();

    expect(DB::table('permission_user')->where('user_id', $user->id)->count())->toBe(1);
});

it('deactivates and activates an account, which cannot then sign in', function () {
    $admin = staff('Admin');
    $user = staff('Viewer');
    $page = Livewire::actingAs($admin)->test('pages::admin.users.index');

    $page->call('setActive', $user->id, false);
    expect($user->refresh()->active)->toBeFalse()->and(userLogs($user)->where('operation', 'User Deactivated')->exists())->toBeTrue();
    $this->actingAs($user)->get('/admin/home')->assertForbidden();

    Livewire::actingAs($admin)->test('pages::admin.users.index')->call('setActive', $user->id, true);
    expect($user->refresh()->active)->toBeTrue()->and(userLogs($user)->where('operation', 'User Activated')->exists())->toBeTrue();
});

it('does not let an account deactivate or delete itself', function () {
    $admin = staff('Admin');
    $page = Livewire::actingAs($admin)->test('pages::admin.users.index');

    $page->call('setActive', $admin->id, false)->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
    $page->call('remove', $admin->id)->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

    expect($admin->refresh()->active)->toBeTrue()->and(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

it('never removes the last active Admin: not by deactivating, deleting or taking the role away', function () {
    $last = onlyAdmin();
    $actor = staff('Admin');
    $actor->update(['active' => false]);
    $viewer = (int) Role::query()->where('name', 'Viewer')->value('id');
    $users = app(ManageUsers::class);

    $refused = fn (Closure $act): bool => rescue(fn () => $act() ?? false, fn ($e) => $e instanceof AdminActionRefused && $e->key === 'last_admin', false);

    expect($refused(fn () => $users->setActive($last, false, $actor)))->toBeTrue()
        ->and($refused(fn () => $users->delete($last, $actor)))->toBeTrue()
        ->and($refused(fn () => $users->save(['name' => $last->name, 'email' => $last->email, 'role_ids' => [$viewer], 'place_ids' => []], $actor, $last)))->toBeTrue()
        ->and($last->refresh()->active)->toBeTrue()
        ->and(DB::table('role_user')->where('user_id', $last->id)->where('role_id', Role::query()->where('name', 'Admin')->value('id'))->exists())->toBeTrue();
});

it('allows demoting an Admin when another active one remains', function () {
    onlyAdmin();
    $one = staff('Admin');
    $two = staff('Admin');

    Livewire::actingAs($one)->test('pages::admin.users.index')->call('setActive', $two->id, false);

    expect($two->refresh()->active)->toBeFalse();
});

it('deletes an account that never made a change, with what it held, and keeps one that did', function () {
    $admin = staff('Admin');
    $idle = staff('Viewer', [1]);
    $busy = staff('Viewer');
    Log::query()->create(['category' => 0, 'project_id' => 1, 'operation' => 'x', 'user_id' => $busy->id, 'seen' => 1]);
    $page = Livewire::actingAs($admin)->test('pages::admin.users.index');

    $page->call('remove', $idle->id);
    expect(User::query()->whereKey($idle->id)->exists())->toBeFalse()->and(DB::table('role_user')->where('user_id', $idle->id)->count())->toBe(0)
        ->and(DB::table('place_user')->where('user_id', $idle->id)->count())->toBe(0)
        ->and(userLogs($idle)->where('operation', 'User Deleted')->exists())->toBeTrue();

    $page->call('remove', $busy->id)->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'Deactivate it instead'));
    expect(User::query()->whereKey($busy->id)->exists())->toBeTrue();
});

it('lets only the Admin act on the list, whatever the buttons show', function () {
    $user = staff('Viewer');

    foreach (['Publisher', 'English Editor'] as $role) {
        Livewire::actingAs(staff($role))->test('pages::admin.users.index')->assertForbidden();
    }

    expect($user->refresh()->active)->toBeTrue();
});
