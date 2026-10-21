<?php

namespace App\Queries;

use App\Models\Donor;
use App\Models\HopeProject;
use Illuminate\Database\Eloquent\Collection;

/*
 * What a programme page shows besides its own text: the brief (name, logo,
 * description and the photos or video beside it) and the funders. Both are
 * keyed by the programme's what_category_id, the same id the cover photos
 * use. Replaces the HopeProjects::with('media')->first() and
 * ProjectDonor::...->with('donors') lines each programme method repeated.
 */
final class ProjectPageQuery
{
    public static function brief(int $categoryId): ?HopeProject
    {
        return HopeProject::query()
            ->where('what_category_id', $categoryId)
            ->orderBy('id')
            ->with(['hopeProjectMedia' => fn ($media) => $media->orderBy('mediaOrder')->orderBy('id')])
            ->first();
    }

    /**
     * The programme's funders, in the order the admin ranked them.
     *
     * @param  bool  $active  true for those still funding, false for past ones
     * @return Collection<int, Donor>
     */
    public static function funders(int $categoryId, bool $active): Collection
    {
        return Donor::query()
            ->join('project_donors', 'project_donors.donor_id', '=', 'donors.id')
            ->where('project_donors.what_category_id', $categoryId)
            ->where('project_donors.isActive', $active ? 1 : 0)
            ->orderBy('project_donors.rank')
            ->orderBy('project_donors.id')
            ->get(['donors.*']);
    }
}
