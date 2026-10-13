<?php

namespace App\Queries;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Models\UserRole;

/*
 * Who may see the members' section of /projects/micro-projects (the project
 * list and map): an account that holds the "Micro Public Page" permission, or
 * the Admin or Publisher role. Legacy Gate 'view-micro-projects'
 * (AuthServiceProvider), unchanged.
 */
final class MemberAccessQuery
{
    public const PERMISSION = 'Micro Public Page';

    public const ROLES = ['Admin', 'Publisher'];

    public static function mayViewMicroProjects(User $user): bool
    {
        return UserPermission::query()
            ->where('user_id', $user->getKey())
            ->whereIn('permission_id', Permission::query()->where('name', self::PERMISSION)->select('id'))
            ->exists()
            || UserRole::query()
                ->where('user_id', $user->getKey())
                ->whereIn('role_id', Role::query()->whereIn('name', self::ROLES)->select('id'))
                ->exists();
    }
}
