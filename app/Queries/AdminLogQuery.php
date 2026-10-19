<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The activity log as the Log page and the notification bell read it. Replaces
 * LogController::indexjson() and get_notifications(), which ran one query per category
 * and counted every row to show a number.
 *
 * The tabs group legacy's categories: the newsletter's own lists (tags, editions,
 * subscribers) are with the news. Study Zone's visit entries (category 8, over 440,000
 * rows of student data) are not shown here: they belong to the students module.
 * Entries before 2022 are not shown, as in legacy; the date is compared as it is, so an
 * index can serve it (legacy wrapped the column in year()).
 */
final class AdminLogQuery
{
    /** @var array<string, list<int>> tab => the log categories it shows */
    public const GROUPS = [
        'projects' => [0],
        'initiatives' => [1],
        'successes' => [2],
        'newsletter' => [3, 4, 6, 35],
        'magazine' => [11],
        'team' => [5],
        'covers' => [9],
        'content' => [10],
        'users' => [12],
        'students' => [7],
    ];

    public const TABS = ['all', 'projects', 'initiatives', 'successes', 'newsletter', 'magazine', 'team', 'covers', 'content', 'users', 'students'];

    private const SINCE = '2022-01-01 00:00:00';

    private string $search = '';

    private string $tab = 'all';

    public static function make(): self
    {
        return new self;
    }

    public function search(?string $term): self
    {
        $this->search = trim((string) $term);

        return $this;
    }

    public function tab(?string $tab): self
    {
        $this->tab = in_array($tab, self::TABS, true) ? (string) $tab : 'all';

        return $this;
    }

    /** One count per tab, with the search applied. @return array<string, int> */
    public function counts(): array
    {
        $rows = $this->base(withTab: false)->selectRaw('logs.category, COUNT(*) as c')->groupBy('logs.category')->pluck('c', 'category');
        $counts = ['all' => 0];

        foreach (self::GROUPS as $tab => $categories) {
            $counts[$tab] = (int) collect($categories)->sum(fn (int $category): int => (int) ($rows[$category] ?? 0));
            $counts['all'] += $counts[$tab];
        }

        return $counts;
    }

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->base()
            ->select(['logs.id', 'logs.operation', 'logs.project_id', 'logs.category', 'logs.previous_data', 'logs.current_data', 'logs.created_at', 'users.name as username'])
            ->orderByDesc('logs.created_at')
            // created_at is not unique; without this entries sharing a second come back in any order.
            ->orderByDesc('logs.id')
            ->paginate($perPage);
    }

    /** The tab a category is shown under, or null. */
    public static function tabOf(int $category): ?string
    {
        foreach (self::GROUPS as $tab => $categories) {
            if (in_array($category, $categories, true)) {
                return $tab;
            }
        }

        return null;
    }

    /** Entries nobody has marked as seen, not made by $exceptUser: the number on the bell. */
    public static function unseenCount(int $exceptUser): int
    {
        return self::unseen($exceptUser)->count();
    }

    /** The latest unseen entries, newest first. @return Collection<int, object> */
    public static function unseenLatest(int $exceptUser, int $limit = 8): Collection
    {
        return self::unseen($exceptUser)
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->orderByDesc('logs.id')
            ->limit($limit)
            ->get(['logs.id', 'logs.operation', 'logs.project_id', 'logs.category', 'logs.created_at', 'users.name as username']);
    }

    /** Mark every entry as seen. */
    public static function markAllSeen(): int
    {
        return DB::table('logs')->where('seen', 0)->update(['seen' => 1]);
    }

    private static function unseen(int $exceptUser): Builder
    {
        return DB::table('logs')
            ->where('logs.seen', 0)
            ->where('logs.created_at', '>=', self::SINCE)
            ->whereIn('logs.category', array_merge(...array_values(self::GROUPS)))
            ->where(fn (Builder $q) => $q->whereNull('logs.user_id')->orWhere('logs.user_id', '!=', $exceptUser));
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('logs')
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->where('logs.created_at', '>=', self::SINCE);

        $categories = $withTab && $this->tab !== 'all' ? self::GROUPS[$this->tab] : array_merge(...array_values(self::GROUPS));
        $query->whereIn('logs.category', $categories);

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(fn (Builder $q) => $q->where('logs.operation', 'like', $like)->orWhere('logs.project_id', 'like', $like)->orWhere('users.name', 'like', $like));
        }

        return $query;
    }
}
