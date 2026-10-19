<?php

namespace App\Support\Admin;

use App\Enums\AdminCapability;
use App\Models\Initiative;
use App\Models\Project;
use App\Models\User;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminInitiativeIndexQuery;
use App\Queries\AdminProjectIndexQuery;
use Illuminate\Support\Facades\Gate;

/*
 * The sidebar and the Ctrl K list: the sections a given account may open. A
 * section appears here once its pages are built; each says which policy
 * ability shows it, so the menu never offers a page that would answer 403.
 */
final class Navigation
{
    /**
     * @return list<array{heading: string, items: list<array{label: string, icon: string, route: string, active: string, badge: ?int}>}>
     */
    public static function groups(User $user): array
    {
        $groups = [
            [
                'heading' => 'Overview',
                'items' => [
                    ['label' => 'Dashboard', 'icon' => 'home', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'badge' => null, 'can' => true],
                ],
            ],
            [
                'heading' => 'Content',
                'items' => [
                    ['label' => 'Micro projects', 'icon' => 'folder', 'route' => 'admin.projects.index', 'active' => 'admin.projects.*', 'badge' => self::pending($user, fn (?array $places): int => AdminProjectIndexQuery::make()->limitedTo($places)->counts()['pending']), 'can' => Gate::forUser($user)->allows('viewAny', Project::class)],
                    ['label' => 'Initiatives', 'icon' => 'spark', 'route' => 'admin.initiatives.index', 'active' => 'admin.initiatives.*', 'badge' => self::pending($user, fn (): int => AdminInitiativeIndexQuery::make()->counts()['pending']), 'can' => Gate::forUser($user)->allows('viewAny', Initiative::class)],
                ],
            ],
        ];

        return array_values(array_filter(array_map(function (array $group): array {
            $group['items'] = array_values(array_filter($group['items'], fn (array $item): bool => $item['can']));

            return $group;
        }, $groups), fn (array $group): bool => $group['items'] !== []));
    }

    /**
     * Items waiting to be published, for those who publish or check them; nobody else sees the number.
     *
     * @param  callable(list<int>|null): int  $count
     */
    private static function pending(User $user, callable $count): ?int
    {
        if (! AdminAccessQuery::can($user, AdminCapability::Publish) && ! AdminAccessQuery::can($user, AdminCapability::CheckEnglish)) {
            return null;
        }

        return $count(AdminAccessQuery::placeIds($user));
    }

    /** The pages for the Ctrl K window. @return list<array{label: string, hint: string, url: string}> */
    public static function pages(User $user): array
    {
        $pages = [];

        foreach (self::groups($user) as $group) {
            foreach ($group['items'] as $item) {
                $pages[] = ['label' => $item['label'], 'hint' => $group['heading'], 'url' => route($item['route'])];
            }
        }

        foreach ([[Project::class, 'New project', 'admin.projects.create'], [Initiative::class, 'New initiative', 'admin.initiatives.create']] as [$model, $label, $route]) {
            if (Gate::forUser($user)->allows('create', $model)) {
                $pages[] = ['label' => $label, 'hint' => 'Create', 'url' => route($route)];
            }
        }

        return $pages;
    }
}
