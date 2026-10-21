<?php

namespace App\Queries;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The statistics band on /projects/micro-projects, replacing the four raw
 * aggregate queries in PublicController::micro_project_new_test().
 *
 * These are genuine reporting aggregates, so they stay as SQL rather than being
 * forced through Eloquent — but every literal that varies is a constant here
 * instead of being spliced into a string.
 */
class MicroProjectStatsQuery
{
    /** Published states counted on the public site. */
    private const PUBLISHED = [1, 2];

    /** Cities shown in the per-city breakdown. */
    private const CITY_IDS = [1, 2, 3, 4, 6, 7, 8];

    /** Sub-categories excluded from the category breakdown. */
    private const EXCLUDED_SUBCATEGORIES = [1, 2];

    /** A project counts as sustainable once it has been funded this long. */
    private const SUSTAINABLE_AFTER_MONTHS = 9;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Project count per city id, for the map and the counters.
     *
     * @return Collection<array-key, mixed>
     */
    public function countsByCity(): Collection
    {
        return Project::query()
            ->select('MPE_City', DB::raw('COUNT(*) as total'))
            ->whereIn('MPE_City', self::CITY_IDS)
            ->whereIn('MPE_Done', [1, 2])
            ->whereIn('MPE_Published', self::PUBLISHED)
            ->groupBy('MPE_City')
            ->pluck('total', 'MPE_City');
    }

    /** Total beneficiaries across all counted projects. */
    public function familyMembers(): int
    {
        return (int) Project::query()
            ->whereIn('MPE_Done', [1, 2])
            ->whereIn('MPE_Published', self::PUBLISHED)
            ->sum('FamilyMembers');
    }

    /** How many categories the page lists. */
    private const TOP_CATEGORIES = 5;

    /**
     * The most used sub-categories, biggest first.
     * Note MPE_Done is 1 only here, unlike the city counts which allow 1 or 2 —
     * that difference is carried over from the legacy queries as-is.
     *
     * @return Collection<int, \stdClass>
     */
    public function topCategories(): Collection
    {
        return collect(
            DB::table('projects')
                ->leftJoin('project_sub_categories', 'project_sub_categories.ID', '=', 'projects.SubCategory_ID')
                ->select('project_sub_categories.Name', DB::raw('COUNT(projects.id) AS project_count'))
                ->whereIn('projects.MPE_Published', self::PUBLISHED)
                ->where('projects.MPE_Done', 1)
                ->whereNotIn('projects.SubCategory_ID', self::EXCLUDED_SUBCATEGORIES)
                ->groupBy('project_sub_categories.Name')
                ->havingRaw('project_sub_categories.Name is not null')
                ->orderByDesc('project_count')
                // Tied counts had no fixed order in legacy; name gives them one.
                ->orderBy('project_sub_categories.Name')
                ->limit(self::TOP_CATEGORIES)
                ->get()
        );
    }

    /**
     * Project count per age band. The join condition treats an age of 0 as 1,
     * so newborns fall inside the first band rather than outside every band.
     *
     * @return Collection<int, \stdClass>
     */
    public function countsByAgeRange(): Collection
    {
        return collect(
            DB::table('projects')
                ->leftJoin('ages', function ($join) {
                    $join->whereRaw(
                        '(CASE WHEN YEAR(CURDATE()) - projects.DOB = 0 THEN 1'
                        .' ELSE YEAR(CURDATE()) - projects.DOB END) BETWEEN ages.Min AND ages.Max'
                    );
                })
                ->select('ages.Name', DB::raw('COUNT(projects.id) AS project_count'))
                ->whereIn('projects.MPE_Published', self::PUBLISHED)
                ->where('projects.MPE_Done', 1)
                ->groupBy('ages.Name', 'ages.ID')
                ->havingRaw('ages.Name is not null')
                ->orderBy('ages.ID')
                ->get()
        );
    }

    /**
     * Of the published projects funded at least nine months ago, the
     * percentage still marked done, as a whole number. 0 when none are that old.
     */
    public function sustainableRate(): int
    {
        $eligible = Project::query()
            ->where('MPE_Published', 1)
            ->whereDate('MPE_FundedDate', '<=', now()->subMonths(self::SUSTAINABLE_AFTER_MONTHS));

        $total = (clone $eligible)->count();

        return $total === 0 ? 0 : (int) round((clone $eligible)->where('MPE_Done', 1)->count() * 100 / $total);
    }
}
