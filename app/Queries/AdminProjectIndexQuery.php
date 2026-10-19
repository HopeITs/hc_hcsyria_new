<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/*
 * The staff list of micro-projects: one row per project, the tabs of the legacy
 * admin (online, pending, cancelled) plus hidden, English not checked and
 * without photos, and filters for city, category, donor and a search box.
 *
 * Replaces the three per-city queries behind projectsController::index() and
 * indexjson() (AdminProjectListQuery keeps their exact shape for the parity
 * checks). Limited to the cities an account may see (AdminAccessQuery::placeIds).
 * Everything is bound; the search escapes LIKE wildcards.
 */
final class AdminProjectIndexQuery
{
    public const TABS = ['all', 'pending', 'online', 'hidden', 'cancelled', 'english', 'nophoto'];

    /** @var list<int>|null */
    private ?array $places = null;

    private string $search = '';

    private ?int $city = null;

    private ?int $category = null;

    private ?int $donor = null;

    private string $tab = 'all';

    public static function make(): self
    {
        return new self;
    }

    /** @param list<int>|null $placeIds null means every city */
    public function limitedTo(?array $placeIds): self
    {
        $this->places = $placeIds;

        return $this;
    }

    public function search(?string $term): self
    {
        $this->search = trim((string) $term);

        return $this;
    }

    public function inCity(int|string|null $id): self
    {
        $this->city = $id === null || $id === '' ? null : (int) $id;

        return $this;
    }

    public function inCategory(int|string|null $id): self
    {
        $this->category = $id === null || $id === '' ? null : (int) $id;

        return $this;
    }

    public function forDonor(int|string|null $id): self
    {
        $this->donor = $id === null || $id === '' ? null : (int) $id;

        return $this;
    }

    public function tab(?string $tab): self
    {
        $this->tab = in_array($tab, self::TABS, true) ? (string) $tab : 'all';

        return $this;
    }

    /** One count per tab, with the filters other than the tab applied. @return array<string, int> */
    public function counts(): array
    {
        $row = $this->base(withTab: false)
            ->selectRaw(<<<'SQL'
                COUNT(*) as total,
                COALESCE(SUM(projects.MPE_Published = '0' AND projects.hidden = 0), 0) as pending,
                COALESCE(SUM(projects.MPE_Published = '1'), 0) as online,
                COALESCE(SUM(projects.hidden = 1), 0) as hidden,
                COALESCE(SUM(projects.MPE_Done = '2'), 0) as cancelled,
                COALESCE(SUM(projects.english_check = 0), 0) as english,
                COALESCE(SUM(NOT EXISTS (SELECT 1 FROM images WHERE images.project_id = projects.id)), 0) as nophoto
            SQL)
            ->first();

        return [
            'all' => (int) $row->total,
            'pending' => (int) $row->pending,
            'online' => (int) $row->online,
            'hidden' => (int) $row->hidden,
            'cancelled' => (int) $row->cancelled,
            'english' => (int) $row->english,
            'nophoto' => (int) $row->nophoto,
        ];
    }

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->base()
            ->select([
                'projects.id',
                'projects.MicroProject_ID as number',
                'projects.MPE_City as city_id',
                'projects.MPE_Published as published',
                'projects.MPE_Done as done',
                'projects.hidden',
                'projects.english_check',
                'projects.MPE_FundedDate as funded',
                'projects.MPE_Donor as donor_id',
                'projects.MPE_BeneficiaryName as beneficiary',
                'projects.latitude',
                'projects.Mobile as mobile',
                'projects.MPE_Video as video',
                'projects_english.MPE_Name as name',
                'places.name as city',
                'projecttype.name as kind',
                'category.Name as category',
                'subcategory.Name as sub_category',
                'donors.name as donor',
                DB::raw('(SELECT COUNT(*) FROM images WHERE images.project_id = projects.id) as photos'),
            ])
            ->orderByDesc('projects.MicroProject_ID')
            ->orderByDesc('projects.id')
            ->paginate($perPage);
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('projects')
            ->leftJoin('projects_english', 'projects_english.project_id', '=', 'projects.id')
            ->leftJoin('places', 'places.id', '=', 'projects.MPE_City')
            ->leftJoin('projecttype', 'projecttype.id', '=', 'projects.projecttype_id')
            ->leftJoin('subcategory', 'subcategory.ID', '=', 'projects.SubCategory_ID')
            ->leftJoin('category', 'category.ID', '=', 'subcategory.Category_ID')
            ->leftJoin('donors', 'donors.id', '=', 'projects.MPE_Donor');

        if ($this->places !== null) {
            $query->whereIn('projects.MPE_City', $this->places);
        }

        if ($this->city !== null) {
            $query->where('projects.MPE_City', $this->city);
        }

        if ($this->category !== null) {
            $query->where('subcategory.Category_ID', $this->category);
        }

        if ($this->donor !== null) {
            $query->where('projects.MPE_Donor', $this->donor);
        }

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(function (Builder $q) use ($like): void {
                $q->where('projects_english.MPE_Name', 'like', $like)
                    ->orWhere('projects.MPE_BeneficiaryName', 'like', $like)
                    ->orWhere('projects.MicroProject_ID', 'like', $like);
            });
        }

        if ($withTab) {
            match ($this->tab) {
                'pending' => $query->where('projects.MPE_Published', '0')->where('projects.hidden', 0),
                'online' => $query->where('projects.MPE_Published', '1'),
                'hidden' => $query->where('projects.hidden', 1),
                'cancelled' => $query->where('projects.MPE_Done', '2'),
                'english' => $query->where('projects.english_check', 0),
                'nophoto' => $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from('images')->whereColumn('images.project_id', 'projects.id')),
                default => null,
            };
        }

        return $query;
    }
}
