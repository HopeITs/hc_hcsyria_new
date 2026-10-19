<?php

namespace App\Actions\Admin;

use App\Exceptions\AdminActionRefused;
use App\Models\Role;
use App\Models\User;
use App\Queries\AdminAccessQuery;
use Illuminate\Support\Facades\DB;

/*
 * The staff accounts: make one, change one, activate, deactivate, delete. Replaces
 * UserController::store(), update(), activate(), deactivate() and delete(). The caller
 * has already checked the capability (ManageUsers: the Admin).
 *
 * Kept from legacy: an account has a name, a unique email, a password and any number of
 * roles and cities; "no city" or the city "All" means every city. Changed:
 *  - legacy's update ignored an empty choice (a person's last role could never be taken
 *    away and the form could not clear their cities); the choice saved is the choice made;
 *  - the page permissions and Study Zone centres of an account are not touched (the new
 *    dashboard decides by role);
 *  - an account cannot deactivate or delete itself, and the last active Admin cannot be
 *    deactivated, deleted or stripped of the role, so nobody can lock everyone out;
 *  - an account that has made changes is never deleted (the activity log keeps its name
 *    and the database refuses it): deactivate it instead;
 *  - every change is in the activity log (legacy logged none), never with the password.
 */
class ManageUsers
{
    private const ADMIN = 'Admin';

    public function __construct(private readonly WriteActivityLog $log) {}

    /**
     * @param  array{name: string, email: string, password?: string|null, role_ids: list<int>, place_ids: list<int>}  $data
     *
     * @throws AdminActionRefused
     */
    public function save(array $data, User $by, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $by, $user): User {
            $user === null ? $this->create($data, $by) : $this->update($user, $data, $by);

            return $user ?? User::query()->where('email', $data['email'])->firstOrFail();
        });
    }

    /** @throws AdminActionRefused */
    public function setActive(User $user, bool $active, User $by): void
    {
        if ((bool) $user->active === $active) {
            return;
        }

        if (! $active) {
            $this->requireOther($user, $by);
            $this->requireAnotherAdmin($user);
        }

        $user->forceFill(['active' => $active])->save();
        $this->log->record($by, WriteActivityLog::USERS, $user->getKey(), $active ? 'User Activated' : 'User Deactivated', null, $user->email);
        AdminAccessQuery::flush();
    }

    /** @throws AdminActionRefused */
    public function delete(User $user, User $by): void
    {
        $this->requireOther($user, $by);
        $this->requireAnotherAdmin($user);

        $id = $user->getKey();

        if (DB::table('logs')->where('user_id', $id)->exists() || DB::table('student_entries')->where('user_id', $id)->exists()) {
            throw new AdminActionRefused('user_has_activity', ['name' => $user->name]);
        }

        $email = $user->email;

        DB::transaction(function () use ($id): void {
            foreach (['users_roles', 'users_places', 'users_permissions', 'users_centers'] as $table) {
                DB::table($table)->where('user_id', $id)->delete();
            }

            DB::table('users')->where('id', $id)->delete();
        });

        $this->log->record($by, WriteActivityLog::USERS, $id, 'User Deleted', $email);
        AdminAccessQuery::flush();
    }

    /** @param array{name: string, email: string, password?: string|null, role_ids: list<int>, place_ids: list<int>} $data */
    private function create(array $data, User $by): void
    {
        $user = new User(['name' => $data['name'], 'email' => $data['email'], 'password' => (string) ($data['password'] ?? ''), 'active' => true]);
        $user->save();

        $this->sync('users_roles', 'role_id', $user->getKey(), $data['role_ids']);
        $this->sync('users_places', 'place_id', $user->getKey(), $data['place_ids']);

        $this->log->record($by, WriteActivityLog::USERS, $user->getKey(), 'User Created', null, $user->email);
        AdminAccessQuery::flush();
    }

    /**
     * @param  array{name: string, email: string, password?: string|null, role_ids: list<int>, place_ids: list<int>}  $data
     *
     * @throws AdminActionRefused
     */
    private function update(User $user, array $data, User $by): void
    {
        $id = $user->getKey();
        $adminRole = (int) Role::query()->where('name', self::ADMIN)->value('id');
        $hadAdmin = DB::table('users_roles')->where('user_id', $id)->where('role_id', $adminRole)->exists();

        if ($hadAdmin && ! in_array($adminRole, $data['role_ids'], true)) {
            $this->requireAnotherAdmin($user);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];

        foreach (['name' => 'Name', 'email' => 'Email'] as $column => $field) {
            if ($user->isDirty($column)) {
                $this->log->record($by, WriteActivityLog::USERS, $id, 'User Edited: '.$field, $user->getOriginal($column), $user->{$column});
            }
        }

        if (filled($data['password'] ?? null)) {
            $user->password = (string) $data['password'];
            $this->log->record($by, WriteActivityLog::USERS, $id, 'User Edited: Password changed');
        }

        $user->save();

        $this->logChoice($by, $id, 'Roles', 'users_roles', 'role_id', 'roles', $data['role_ids']);
        $this->logChoice($by, $id, 'Cities', 'users_places', 'place_id', 'places', $data['place_ids']);
        AdminAccessQuery::flush();
    }

    /**
     * Replace what an account holds in a link table, and log it by name when it changed.
     *
     * @param  list<int>  $ids
     */
    private function logChoice(User $by, int $userId, string $field, string $link, string $column, string $names, array $ids): void
    {
        $before = DB::table($link)->where('user_id', $userId)->pluck($column)->map(fn ($value): int => (int) $value)->sort()->values()->all();
        $after = collect($ids)->unique()->sort()->values()->all();

        if ($before === $after) {
            return;
        }

        $this->sync($link, $column, $userId, $ids);
        $name = fn (array $list): string => $list === [] ? 'none' : DB::table($names)->whereIn('id', $list)->orderBy('name')->pluck('name')->implode(', ');

        $this->log->record($by, WriteActivityLog::USERS, $userId, 'User Edited: '.$field, $name($before), $name($after));
    }

    /** @param list<int> $ids */
    private function sync(string $link, string $column, int $userId, array $ids): void
    {
        DB::table($link)->where('user_id', $userId)->delete();

        foreach (array_unique($ids) as $id) {
            DB::table($link)->insert(['user_id' => $userId, $column => $id]);
        }
    }

    /** @throws AdminActionRefused */
    private function requireOther(User $user, User $by): void
    {
        if ($user->is($by)) {
            throw new AdminActionRefused('own_account');
        }
    }

    /**
     * Refuse when $user is an active Admin and the only one left.
     *
     * @throws AdminActionRefused
     */
    private function requireAnotherAdmin(User $user): void
    {
        $adminRole = (int) Role::query()->where('name', self::ADMIN)->value('id');

        $isActiveAdmin = (bool) $user->active && DB::table('users_roles')->where('user_id', $user->getKey())->where('role_id', $adminRole)->exists();

        if (! $isActiveAdmin) {
            return;
        }

        $others = DB::table('users')
            ->join('users_roles', 'users_roles.user_id', '=', 'users.id')
            ->where('users_roles.role_id', $adminRole)
            ->where('users.active', 1)
            ->where('users.id', '!=', $user->getKey())
            ->exists();

        if (! $others) {
            throw new AdminActionRefused('last_admin');
        }
    }
}
