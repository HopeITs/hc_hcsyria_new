<?php

namespace App\Queries;

use App\Actions\Admin\WriteActivityLog;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The figures behind the dashboard's statistics and the Reports page: how the
 * micro projects are funded, who they reach, how the site is read and what the
 * team did. One class so the dashboard and the reports always show the same
 * numbers.
 *
 * Limited to the cities the account may see ($placeIds, null = every city),
 * and optionally to one city and one year of the funding date. Cancelled
 * projects (MPE_Done = '2') never count towards money or people.
 * Everything is bound; nothing user-typed is spliced into SQL.
 */
final class AdminStatisticsQuery
{
    /** A project whose date of birth is the column default (1980) is counted as "unknown age". */
    private const DEFAULT_DOB = 1980;

    /** @param list<int>|null $placeIds null means every city */
    public function __construct(
        private readonly ?array $placeIds,
        private readonly ?int $city = null,
        private readonly ?int $year = null,
    ) {}

    /**
     * Projects waiting too long in a stage, with the age of the oldest in days.
     *
     * @return array{not_done: int, unpublished: int, english: int, photos: int, hidden: int, oldest_days: ?int}
     */
    public function attention(): array
    {
        $row = $this->projects()
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(projects.MPE_Done = '0' AND projects.MPE_Published = '0'), 0) as not_done,
                COALESCE(SUM(projects.MPE_Done = '1' AND projects.MPE_Published = '0' AND projects.hidden = 0), 0) as unpublished,
                COALESCE(SUM(projects.MPE_Published = '0' AND projects.hidden = 0 AND projects.english_check = 0), 0) as english,
                COALESCE(SUM(projects.MPE_Published = '1' AND NOT EXISTS (SELECT 1 FROM images WHERE images.project_id = projects.id)), 0) as photos,
                COALESCE(SUM(projects.hidden = 1), 0) as hidden,
                MIN(CASE WHEN projects.MPE_Published = '0' AND projects.hidden = 0 THEN projects.MPE_FundedDate END) as oldest
            SQL)
            ->where('projects.MPE_Done', '!=', '2')
            ->first();

        return [
            'not_done' => (int) $row->not_done,
            'unpublished' => (int) $row->unpublished,
            'english' => (int) $row->english,
            'photos' => (int) $row->photos,
            'hidden' => (int) $row->hidden,
            'oldest_days' => $row->oldest ? (int) now()->startOfDay()->diffInDays(Carbon::parse($row->oldest)->startOfDay(), true) : null,
        ];
    }

    /** @return array{projects: int, needed: int, donated: int, missing: int, share: float, avg_days_to_publish: ?float} */
    public function funding(): array
    {
        $row = $this->counted()
            ->selectRaw(<<<'SQL'
                COUNT(*) as projects,
                COALESCE(SUM(projects.MPE_AllMoneyNeeded), 0) as needed,
                COALESCE(SUM(projects.MPE_MoneyDonated), 0) as donated,
                AVG(CASE WHEN projects.MPE_Published = '1' AND projects.MPE_PublishDate IS NOT NULL AND projects.MPE_FundedDate IS NOT NULL
                         AND projects.MPE_PublishDate >= projects.MPE_FundedDate
                    THEN DATEDIFF(projects.MPE_PublishDate, projects.MPE_FundedDate) END) as days
            SQL)
            ->first();

        $needed = (int) $row->needed;
        $donated = (int) $row->donated;

        return [
            'projects' => (int) $row->projects,
            'needed' => $needed,
            'donated' => $donated,
            'missing' => max(0, $needed - $donated),
            'share' => $needed > 0 ? min(100, $donated / $needed * 100) : 0.0,
            'avg_days_to_publish' => $row->days === null ? null : round((float) $row->days, 1),
        ];
    }

    /**
     * Projects funded and money donated per month, oldest first. Ignores the year filter
     * (a trend needs its months) and shows the last $months months.
     *
     * @return Collection<int, object{month: string, projects: int, donated: int}>
     */
    public function fundedPerMonth(int $months = 12): Collection
    {
        $from = now()->startOfMonth()->subMonths($months - 1);

        $rows = $this->projects()
            ->where('projects.MPE_Done', '!=', '2')
            ->where('projects.MPE_FundedDate', '>=', $from->toDateString())
            ->groupBy('month')
            ->get([DB::raw("DATE_FORMAT(projects.MPE_FundedDate, '%Y-%m') as month"), DB::raw('COUNT(*) as projects'), DB::raw('COALESCE(SUM(projects.MPE_MoneyDonated), 0) as donated')])
            ->keyBy('month');

        return collect(range(0, $months - 1))->map(function (int $i) use ($from, $rows): object {
            $month = $from->copy()->addMonths($i)->format('Y-m');

            return (object) ['month' => $month, 'projects' => (int) ($rows[$month]->projects ?? 0), 'donated' => (int) ($rows[$month]->donated ?? 0)];
        });
    }

    /** @return Collection<int, object{name: string, projects: int, needed: int, donated: int}> */
    public function fundingByCity(): Collection
    {
        return $this->grouped($this->counted()->join('places', 'places.id', '=', 'projects.MPE_City'), 'places.name');
    }

    /** @return Collection<int, object{name: string, projects: int, needed: int, donated: int}> */
    public function fundingByDonor(): Collection
    {
        return $this->grouped($this->counted()->leftJoin('donors', 'donors.id', '=', 'projects.MPE_Donor'), 'donors.name');
    }

    /** @return Collection<int, object{name: string, projects: int, needed: int, donated: int}> */
    public function fundingByCategory(): Collection
    {
        return $this->grouped(
            $this->counted()
                ->leftJoin('project_sub_categories', 'project_sub_categories.ID', '=', 'projects.SubCategory_ID')
                ->leftJoin('project_categories', 'project_categories.ID', '=', 'project_sub_categories.Category_ID'),
            'project_categories.Name',
        );
    }

    /** @return array{families: int, people: int, work_loss: int, average_family: float} */
    public function beneficiaries(): array
    {
        $row = $this->counted()
            ->selectRaw('COUNT(*) as families, COALESCE(SUM(projects.FamilyMembers), 0) as people, COALESCE(SUM(projects.WorkLoss > 0), 0) as work_loss')
            ->first();

        $families = (int) $row->families;

        return [
            'families' => $families,
            'people' => (int) $row->people,
            'work_loss' => (int) $row->work_loss,
            'average_family' => $families > 0 ? round((int) $row->people / $families, 1) : 0.0,
        ];
    }

    /** @return Collection<int, object{name: string, projects: int, people: int}> */
    public function byProjectType(): Collection
    {
        return $this->counted()
            ->leftJoin('project_types', 'project_types.id', '=', 'projects.projecttype_id')
            ->groupBy('project_types.name')
            ->orderByDesc('projects')
            ->get([DB::raw("COALESCE(project_types.name, 'Not set') as name"), DB::raw('COUNT(*) as projects'), DB::raw('COALESCE(SUM(projects.FamilyMembers), 0) as people')]);
    }

    /** @return Collection<int, object{name: string, projects: int, people: int}> */
    public function byCategory(): Collection
    {
        return $this->counted()
            ->leftJoin('project_sub_categories', 'project_sub_categories.ID', '=', 'projects.SubCategory_ID')
            ->leftJoin('project_categories', 'project_categories.ID', '=', 'project_sub_categories.Category_ID')
            ->groupBy('project_categories.Name')
            ->orderByDesc('projects')
            ->get([DB::raw("COALESCE(project_categories.Name, 'Not set') as name"), DB::raw('COUNT(*) as projects'), DB::raw('COALESCE(SUM(projects.FamilyMembers), 0) as people')]);
    }

    /**
     * Beneficiaries by age today, in bands. The year of birth 1980 is the column's
     * default, so it is shown as "Not recorded" rather than guessed.
     *
     * @return list<array{band: string, projects: int}>
     */
    public function ageBands(): array
    {
        $row = $this->counted()
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(projects.DOB = ?), 0) as unknown,
                COALESCE(SUM(projects.DOB != ? AND (YEAR(CURDATE()) - projects.DOB) < 18), 0) as b1,
                COALESCE(SUM(projects.DOB != ? AND (YEAR(CURDATE()) - projects.DOB) BETWEEN 18 AND 29), 0) as b2,
                COALESCE(SUM(projects.DOB != ? AND (YEAR(CURDATE()) - projects.DOB) BETWEEN 30 AND 44), 0) as b3,
                COALESCE(SUM(projects.DOB != ? AND (YEAR(CURDATE()) - projects.DOB) BETWEEN 45 AND 59), 0) as b4,
                COALESCE(SUM(projects.DOB != ? AND (YEAR(CURDATE()) - projects.DOB) >= 60), 0) as b5
            SQL, array_fill(0, 6, self::DEFAULT_DOB))
            ->first();

        return [
            ['band' => 'Under 18', 'projects' => (int) $row->b1],
            ['band' => '18 to 29', 'projects' => (int) $row->b2],
            ['band' => '30 to 44', 'projects' => (int) $row->b3],
            ['band' => '45 to 59', 'projects' => (int) $row->b4],
            ['band' => '60 and over', 'projects' => (int) $row->b5],
            ['band' => 'Not recorded', 'projects' => (int) $row->unknown],
        ];
    }

    /** @return array{total: int, active: int, left: int, new_week: int, previous_week: int} */
    public function subscribers(): array
    {
        $row = DB::table('newsletter_subscribers')->selectRaw(<<<'SQL'
            COUNT(*) as total,
            COALESCE(SUM(active = 1), 0) as active,
            COALESCE(SUM(active = 0), 0) as `left`,
            COALESCE(SUM(created_at >= ?), 0) as new_week,
            COALESCE(SUM(created_at >= ? AND created_at < ?), 0) as previous_week
        SQL, [now()->subDays(7), now()->subDays(14), now()->subDays(7)])->first();

        return [
            'total' => (int) $row->total,
            'active' => (int) $row->active,
            'left' => (int) $row->left,
            'new_week' => (int) $row->new_week,
            'previous_week' => (int) $row->previous_week,
        ];
    }

    /** Public pages by number of visits. @return Collection<int, object{page: string, views: int}> */
    public function topPages(int $limit = 8): Collection
    {
        return DB::table('page_views')->orderByDesc('views')->limit($limit)->get(['page_name as page', 'views'])
            ->map(fn ($row): object => (object) ['page' => (string) $row->page, 'views' => (int) $row->views]);
    }

    /** @return Collection<int, object{id: int, title: ?string, views: int}> */
    public function topArticles(int $limit = 5): Collection
    {
        return DB::table('news')
            ->leftJoin('news_contents', 'news_contents.news_id', '=', 'news.id')
            ->where('news.online', 1)
            ->orderByDesc('news.total_views')
            ->limit($limit)
            ->get(['news.id', 'news_contents.title', 'news.total_views as views'])
            ->map(fn ($row): object => (object) ['id' => (int) $row->id, 'title' => $row->title, 'views' => (int) $row->views]);
    }

    /** What each staff member did on the projects in the last $days days. @return Collection<int, object{name: string, actions: int, last: string}> */
    public function teamActivity(int $days = 7, int $limit = 6): Collection
    {
        return DB::table('logs')
            ->join('users', 'users.id', '=', 'logs.user_id')
            ->leftJoin('projects', 'projects.id', '=', 'logs.project_id')
            ->where('logs.category', WriteActivityLog::PROJECT)
            ->where('logs.created_at', '>=', now()->subDays($days))
            ->when($this->placeIds !== null, fn (Builder $q) => $q->whereIn('projects.MPE_City', $this->placeIds))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('actions')
            ->limit($limit)
            ->get(['users.name', DB::raw('COUNT(*) as actions'), DB::raw('MAX(logs.created_at) as last')])
            ->map(fn ($row): object => (object) ['name' => (string) $row->name, 'actions' => (int) $row->actions, 'last' => (string) $row->last]);
    }

    /** @return Collection<int, int> the years that have a funded project, newest first, for the year filter */
    public function years(): Collection
    {
        return DB::table('projects')
            ->when($this->placeIds !== null, fn (Builder $q) => $q->whereIn('projects.MPE_City', $this->placeIds))
            ->whereNotNull('MPE_FundedDate')
            ->selectRaw('DISTINCT YEAR(MPE_FundedDate) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year): int => (int) $year)
            ->filter(fn (int $year): bool => $year > 0)
            ->values();
    }

    /** Every project the account may see, with the city filter. */
    private function projects(): Builder
    {
        return DB::table('projects')
            ->when($this->placeIds !== null, fn (Builder $q) => $q->whereIn('projects.MPE_City', $this->placeIds))
            ->when($this->city !== null, fn (Builder $q) => $q->where('projects.MPE_City', $this->city));
    }

    /** The projects that count towards money and people: not cancelled, in the chosen year. */
    private function counted(): Builder
    {
        return $this->projects()
            ->where('projects.MPE_Done', '!=', '2')
            ->when($this->year !== null, fn (Builder $q) => $q->whereYear('projects.MPE_FundedDate', $this->year));
    }

    /**
     * @param  literal-string  $column
     * @return Collection<int, object{name: string, projects: int, needed: int, donated: int}>
     */
    private function grouped(Builder $query, string $column): Collection
    {
        return $query->groupBy($column)
            ->orderByDesc('donated')
            ->get([DB::raw("COALESCE({$column}, 'Not set') as name"), DB::raw('COUNT(*) as projects'), DB::raw('COALESCE(SUM(projects.MPE_AllMoneyNeeded), 0) as needed'), DB::raw('COALESCE(SUM(projects.MPE_MoneyDonated), 0) as donated')])
            ->map(fn ($row): object => (object) ['name' => (string) $row->name, 'projects' => (int) $row->projects, 'needed' => (int) $row->needed, 'donated' => (int) $row->donated]);
    }
}
