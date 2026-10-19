<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;
use Illuminate\Support\Facades\RateLimiter;

function member(array $attributes = []): User
{
    return User::query()->create([
        'name' => 'Test Member',
        'email' => 'member-'.uniqid().'@example.test',
        'password' => 'correct horse battery',
        'active' => true,
        ...$attributes,
    ]);
}

function signIn(string $email, string $password, array $extra = [])
{
    return test()->from('/projects/micro-projects')->post('/login', ['email' => $email, 'password' => $password, ...$extra]);
}

beforeEach(fn () => RateLimiter::clear('login'));

it('signs a member in and sends them back to the page they were on', function () {
    $user = member();

    signIn($user->email, 'correct horse battery', ['redirect' => '/projects/micro-projects'])
        ->assertRedirect('/projects/micro-projects');

    $this->assertAuthenticatedAs($user);
});

it('never sends anyone to another site after signing in', function (string $target) {
    $user = member();

    signIn($user->email, 'correct horse battery', ['redirect' => $target])->assertRedirect('/home');
})->with(['full address' => 'https://evil.example/', 'protocol-relative' => '//evil.example', 'backslash' => '/\evil.example']);

it('turns a wrong password back with the error on the form', function () {
    $user = member();

    signIn($user->email, 'not it')->assertRedirect('/projects/micro-projects')->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('refuses an account that has been switched off, with legacy\'s wording', function () {
    $user = member(['active' => false]);

    signIn($user->email, 'correct horse battery')
        ->assertSessionHasErrors(['password' => 'Your account is not activated, Please contact the website administrator']);

    $this->assertGuest();
});

it('stops a run of guesses at the sixth', function () {
    $user = member();

    foreach (range(1, 5) as $i) {
        signIn($user->email, 'guess '.$i)->assertSessionHasErrors('email');
    }

    signIn($user->email, 'correct horse battery')->assertStatus(429);
    $this->assertGuest();
});

it('shows the sign-in card to a visitor and the notice to a member the page has no use for', function () {
    $this->get('/projects/micro-projects')->assertSee('name="email"', false)->assertDontSee('not authorized to view');

    $this->actingAs(member())->get('/projects/micro-projects')
        ->assertSee('Your account is not authorized to view this section.')
        ->assertDontSee('name="email"', false);
});

it('lifts the gate for the permission and for the Admin and Publisher roles', function (string $kind, string $name) {
    $user = member();

    if ($kind === 'permission') {
        $permission = Permission::query()->firstOrCreate(['name' => $name]);
        UserPermission::query()->create(['user_id' => $user->id, 'permission_id' => $permission->id]);
    } else {
        $role = Role::query()->firstOrCreate(['name' => $name]);
        UserRole::query()->create(['user_id' => $user->id, 'role_id' => $role->id]);
    }

    $this->actingAs($user)->get('/projects/micro-projects')
        ->assertOk()
        ->assertDontSee('This section contains private information');
})->with([
    'permission' => ['permission', 'Micro Public Page'],
    'admin' => ['role', 'Admin'],
    'publisher' => ['role', 'Publisher'],
]);

it('keeps the gate for a role that is neither', function () {
    $user = member();
    $role = Role::query()->firstOrCreate(['name' => 'English Editor']);
    UserRole::query()->create(['user_id' => $user->id, 'role_id' => $role->id]);

    $this->actingAs($user)->get('/projects/micro-projects')->assertSee('not authorized to view this section');
});

it('signs out and returns to the page it was on', function () {
    $this->actingAs(member())
        ->post('/logout', ['redirect' => '/projects/micro-projects'])
        ->assertRedirect('/projects/micro-projects');

    $this->assertGuest();
});

it('shows the sign-in page to a visitor, in the legacy words', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('<title>Login | Hope Center</title>', false)
        ->assertSeeInOrder(['HC Syria', 'Dashboard', 'Log in to continue', 'Email', 'Password', 'Remember Me', 'Login'])
        ->assertSee('action="'.route('login').'"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('Copyright © Christian Hope Center '.date('Y'), false);
});

it('sends a signed-in account away from the sign-in page', function () {
    $this->actingAs(member())->get('/login')->assertRedirect();
});

it('shows a failed sign-in on the page itself, with the address kept', function () {
    $user = member();

    $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'not it'])->assertRedirect('/login');

    $this->get('/login')->assertSee('value="'.$user->email.'"', false)->assertSee('invalid-feedback', false);
});
