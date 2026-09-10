<?php

namespace App\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * The admin micro-project tables, replacing three near-identical raw queries in
 * projectsController::indexjson() that differed only by MPE_City.
 *
 * Two things carried over deliberately, and one fixed:
 *
 *  - The legacy method assigned $projectsAleppo, $projectsHoms and
 *    $projectsDamascus from Eloquent first and then immediately overwrote all
 *    three with the raw queries. Those three Eloquent queries ran on every
 *    request and their results were discarded. They are not reproduced.
 *  - Aleppo grouped by projects.id while Homs and Damascus grouped by
 *    MicroProject_ID. That id is not unique — 747 values are shared by more
 *    than one published project — so the second form can silently collapse two
 *    projects into one row. Within a single city the counts happen to agree
 *    today, but this groups by projects.id everywhere so it cannot start
 *    losing rows later.
 */
class AdminProjectListQuery
{
    /** City ids used by the admin tabs. */
    public const ALEPPO   = 3;
    public const HOMS     = 1;
    public const DAMASCUS = 2;

    private ?int $cityId = null;
    private ?int $published = 1;

    public static function make(): self
    {
        return new self();
    }

    public function inCity(int $cityId): self
    {
        $this->cityId = $cityId;

        return $this;
    }

    /** Pass null to include every publication state. */
    public function published(?int $state): self
    {
        $this->published = $state;

        return $this;
    }

    public function get(): Collection
    {
        $query = DB::table('projects')
            ->select([
                'projects.id as NO',
                'projects.MPE_Published',
                'projects.MicroProject_ID',
                DB::raw('MIN(projects_english.MPE_Name) as MPE_Name'),
                DB::raw('MIN(projecttype.name) as type'),
                'projects.MPE_BeneficiaryName',
                'projects.MPE_Video',
                'projects.Mobile',
                'projects.MPE_FundedDate',
                'projects.latitude',
                'projects.longtitude',
                DB::raw('MIN(donors.code) as MPE_Donor'),
                DB::raw('COUNT(images.id) as images'),
            ])
            ->leftJoin('projects_english', 'projects.id', '=', 'projects_english.project_id')
            ->leftJoin('donors', 'donors.id', '=', 'projects.MPE_Donor')
            ->leftJoin('projecttype', 'projects.projecttype_id', '=', 'projecttype.id')
            ->leftJoin('images', 'projects.id', '=', 'images.project_id')
            // Every non-aggregated column has to be listed: MariaDB does not
            // infer functional dependency on the primary key.
            ->groupBy(
                'projects.id',
                'projects.MPE_Published',
                'projects.MicroProject_ID',
                'projects.MPE_BeneficiaryName',
                'projects.MPE_Video',
                'projects.Mobile',
                'projects.MPE_FundedDate',
                'projects.latitude',
                'projects.longtitude'
            );

        if ($this->cityId !== null) {
            $query->where('projects.MPE_City', $this->cityId);
        }

        if ($this->published !== null) {
            $query->where('projects.MPE_Published', $this->published);
        }

        return collect($query->orderBy('projects.MicroProject_ID')->orderBy('projects.id')->get());
    }

    /** Cancelled projects, for the admin tab of the same name. */
    public static function cancelled(): Collection
    {
        return collect(
            DB::table('projects')->leftJoin('places', 'places.id', '=', 'projects.MPE_City')
                ->select('projects.*', 'places.name as place_name')
                ->where('projects.MPE_Done', '2')
                ->orderBy('projects.MicroProject_ID')
                ->orderBy('projects.id')
                ->get()
        );
    }

    /** Projects awaiting publication. */
    public static function pending(): Collection
    {
        return collect(
            DB::table('projects')->leftJoin('places', 'places.id', '=', 'projects.MPE_City')
                ->select('projects.*', 'places.name as place_name')
                ->where('projects.MPE_Published', '0')
                ->orderBy('projects.MicroProject_ID')
                ->orderBy('projects.id')
                ->get()
        );
    }
}
