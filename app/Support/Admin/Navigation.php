<?php

namespace App\Support\Admin;

use App\Enums\AdminCapability;
use App\Models\Initiative;
use App\Models\News;
use App\Models\Project;
use App\Models\RootsOfHope;
use App\Models\Success;
use App\Models\User;
use App\Queries\AdminAccessQuery;
use App\Queries\AdminInitiativeIndexQuery;
use App\Queries\AdminNewsIndexQuery;
use App\Queries\AdminProjectIndexQuery;
use App\Queries\AdminRootsQuery;
use App\Queries\AdminSuccessIndexQuery;
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
                    ['label' => 'Successes', 'icon' => 'trophy', 'route' => 'admin.successes.index', 'active' => 'admin.successes.*', 'badge' => self::pending($user, fn (): int => AdminSuccessIndexQuery::make()->counts()['pending']), 'can' => Gate::forUser($user)->allows('viewAny', Success::class)],
                    ['label' => 'Newsletter', 'icon' => 'news', 'route' => 'admin.news.index', 'active' => 'admin.news.*', 'badge' => self::pending($user, fn (): int => AdminNewsIndexQuery::make()->counts()['pending']), 'can' => Gate::forUser($user)->allows('viewAny', News::class)],
                    ['label' => 'Roots of Hope', 'icon' => 'book', 'route' => 'admin.roots.index', 'active' => 'admin.roots.*', 'badge' => self::pending($user, fn (): int => AdminRootsQuery::make()->counts()['pending']), 'can' => Gate::forUser($user)->allows('viewAny', RootsOfHope::class)],
                ],
            ],
            [
                'heading' => 'Administration',
                'items' => [
                    ['label' => 'Reports', 'icon' => 'doc', 'route' => 'admin.reports', 'active' => 'admin.reports*', 'badge' => null, 'can' => Gate::forUser($user)->allows('admin.'.AdminCapability::ViewReports->value)],
                    ['label' => 'Users', 'icon' => 'users', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'badge' => null, 'can' => Gate::forUser($user)->allows('admin.'.AdminCapability::ManageUsers->value)],
                    ['label' => 'Activity log', 'icon' => 'log', 'route' => 'admin.log', 'active' => 'admin.log', 'badge' => null, 'can' => Gate::forUser($user)->allows('admin.'.AdminCapability::ViewLog->value)],
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

        foreach ([[Project::class, 'New project', 'admin.projects.create'], [Initiative::class, 'New initiative', 'admin.initiatives.create'], [Success::class, 'New success', 'admin.successes.create'], [News::class, 'New newsletter item', 'admin.news.create'], [RootsOfHope::class, 'New magazine edition', 'admin.roots.create']] as [$model, $label, $route]) {
            if (Gate::forUser($user)->allows('create', $model)) {
                $pages[] = ['label' => $label, 'hint' => 'Create', 'url' => route($route)];
            }
        }

        return $pages;
    }
}
