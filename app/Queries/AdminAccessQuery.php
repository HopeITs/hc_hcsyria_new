<?php

namespace App\Queries;

use App\Enums\AdminCapability;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPlace;
use App\Models\UserRole;

/*
 * Who may do what in the admin: the roles an account holds (users_roles) and
 * the cities it is limited to (users_places).
 *
 * Places, as in legacy projectsController::index(): an Admin sees every city; so
 * does anyone with no place rows, or with the "All" place (id 0). Everyone else
 * sees only the cities listed for them.
 */
final class AdminAccessQuery
{
    /** @var array<int, list<string>> roles by account, read once per request (and per test: flush()) */
    private static array $roles = [];

    /** @var array<int, list<int>|null> */
    private static array $places = [];

    /** Forget what was read: after roles or places change in the same request. */
    public static function flush(): void
    {
        self::$roles = [];
        self::$places = [];
    }

    /** @return list<string> */
    public static function roleNames(User $user): array
    {
        return self::$roles[$user->getKey()] ??= Role::query()
            ->whereIn('id', UserRole::query()->where('user_id', $user->getKey())->select('role_id'))
            ->pluck('name')
            ->map(fn ($name): string => (string) $name)
            ->values()
            ->all();
    }

    public static function hasRole(User $user, string ...$names): bool
    {
        return array_intersect($names, self::roleNames($user)) !== [];
    }

    public static function can(User $user, AdminCapability $capability): bool
    {
        return $user->active && self::hasRole($user, ...$capability->roles());
    }

    /** May this account open the admin at all? */
    public static function mayEnter(User $user): bool
    {
        return self::can($user, AdminCapability::View);
    }

    /** @return list<AdminCapability> */
    public static function capabilities(User $user): array
    {
        $roles = self::roleNames($user);

        return array_values(array_filter(
            AdminCapability::cases(),
            fn (AdminCapability $capability): bool => $user->active && array_intersect($capability->roles(), $roles) !== [],
        ));
    }

    /**
     * The city ids this account is limited to, or null for every city.
     *
     * @return list<int>|null
     */
    public static function placeIds(User $user): ?array
    {
        if (array_key_exists($user->getKey(), self::$places)) {
            return self::$places[$user->getKey()];
        }

        if (self::hasRole($user, 'Admin')) {
            return self::$places[$user->getKey()] = null;
        }

        $ids = UserPlace::query()->where('user_id', $user->getKey())->pluck('place_id')->map(fn ($id): int => (int) $id)->all();

        return self::$places[$user->getKey()] = ($ids === [] || in_array(0, $ids, true) ? null : array_values($ids));
    }

    public static function mayUsePlace(User $user, int $placeId): bool
    {
        $ids = self::placeIds($user);

        return $ids === null || in_array($placeId, $ids, true);
    }
}
