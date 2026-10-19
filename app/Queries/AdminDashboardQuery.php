<?php

namespace App\Queries;

use App\Actions\Admin\WriteActivityLog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The figures on the dashboard, limited to the cities the account may see.
 * The tab counts come from AdminProjectIndexQuery (the same numbers the list
 * shows); this adds what only the dashboard needs: online projects per city,
 * the date of the last publication, and recent activity.
 */
final class AdminDashboardQuery
{
    /** @param list<int>|null $placeIds null means every city */
    public function __construct(private readonly ?array $placeIds) {}

    /** @return array{without_location: int, last_published: ?string, oldest_pending: ?string} */
    public function facts(): array
    {
        $row = $this->projects()
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(projects.MPE_Published = '1' AND (projects.latitude IS NULL OR projects.latitude IN ('', '0'))), 0) as without_location,
                MAX(CASE WHEN projects.MPE_Published = '1' THEN projects.MPE_PublishDate END) as last_published,
                MIN(CASE WHEN projects.MPE_Published = '0' AND projects.hidden = 0 THEN projects.MPE_FundedDate END) as oldest_pending
            SQL)
            ->first();

        return [
            'without_location' => (int) $row->without_location,
            'last_published' => $row->last_published,
            'oldest_pending' => $row->oldest_pending,
        ];
    }

    /** Online projects per city, the biggest first. @return Collection<int, object{city: string, total: int}> */
    public function onlineByCity(): Collection
    {
        return $this->projects()
            ->join('places', 'places.id', '=', 'projects.MPE_City')
            ->where('projects.MPE_Published', '1')
            ->groupBy('places.id', 'places.name')
            ->orderByDesc('total')
            ->get(['places.name as city', DB::raw('COUNT(*) as total')]);
    }

    /** @return Collection<int, object{operation: string, project_id: int, number: ?int, by: ?string, at: string}> */
    public function recentActivity(int $limit = 6): Collection
    {
        return DB::table('logs')
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->leftJoin('projects', 'projects.id', '=', 'logs.project_id')
            ->where('logs.category', WriteActivityLog::PROJECT)
            ->when($this->placeIds !== null, fn ($query) => $query->whereIn('projects.MPE_City', $this->placeIds))
            ->orderByDesc('logs.id')
            ->limit($limit)
            ->get(['logs.operation', 'logs.project_id', 'projects.MicroProject_ID as number', 'users.name as by', 'logs.created_at as at']);
    }

    private function projects(): Builder
    {
        return DB::table('projects')->when($this->placeIds !== null, fn ($query) => $query->whereIn('projects.MPE_City', $this->placeIds));
    }
}
