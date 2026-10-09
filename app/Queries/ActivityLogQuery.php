<?php

namespace App\Queries;

use App\Models\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/*
 * The admin activity log, replacing six identical raw queries in
 * LogController::indexjson() that differed only by category.
 *
 * Two fixes carried in:
 *
 *  - The projects query (category 0) had no ORDER BY while the other five
 *    ordered by created_at DESC, so that one tab came back in arbitrary order.
 *    All categories are ordered consistently now.
 *  - `year(logs.created_at) > '2021'` wraps the column in a function, so MySQL
 *    cannot use an index for it and has to read all 524,383 rows. The
 *    equivalent `created_at >= '2022-01-01'` can use one.
 */
class ActivityLogQuery
{
    /** Category ids as used by the legacy admin screen. */
    public const PROJECTS = 0;

    public const INITIATIVES = 1;

    public const SUCCESSES = 2;

    public const NEWS = 3;

    public const EDITIONS = 4;

    public const TEAM = 5;

    public const STUDENTS = 7;

    /** The categories the admin screen actually displays, in tab order. */
    public const DISPLAYED = [
        'projectsLog' => self::PROJECTS,
        'initiativesLog' => self::INITIATIVES,
        'successesLog' => self::SUCCESSES,
        'newsLog' => self::NEWS,
        'teamLog' => self::TEAM,
        'studentLog' => self::STUDENTS,
    ];

    /** Entries before this are not shown. */
    private const SINCE = '2022-01-01 00:00:00';

    private ?int $category = null;

    public static function make(): self
    {
        return new self;
    }

    public function forCategory(int $category): self
    {
        $this->category = $category;

        return $this;
    }

    /** @return Builder<Log> */
    public function builder(): Builder
    {
        $query = Log::query()
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->select([
                'logs.id',
                'logs.operation',
                'logs.project_id',
                'users.name as username',
                'logs.created_at',
                'logs.previous_data',
                'logs.current_data',
            ])
            ->where('logs.created_at', '>=', self::SINCE);

        if ($this->category !== null) {
            $query->where('logs.category', $this->category);
        }

        return $query
            ->orderByDesc('logs.created_at')
            // created_at is not unique across 524k rows; without this, entries
            // sharing a timestamp come back in arbitrary order.
            ->orderByDesc('logs.id');
    }

    /** @return Collection<int, Log> */
    public function get(): Collection
    {
        return collect($this->builder()->get());
    }

    /**
     * Every displayed category in one pass, keyed as the admin screen expects.
     * The legacy code ran six separate queries for this.
     *
     * @return array<string, Collection<int, Log>>
     */
    public static function forAdminScreen(): array
    {
        $byCategory = self::make()->builder()
            ->whereIn('logs.category', array_values(self::DISPLAYED))
            ->addSelect('logs.category')
            ->get()
            ->groupBy('category');

        $out = [];
        foreach (self::DISPLAYED as $key => $category) {
            $out[$key] = $byCategory->get($category, collect())->values();
        }

        return $out;
    }
}
