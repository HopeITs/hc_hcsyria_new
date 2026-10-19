<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/*
 * The staff list of initiatives: the categories the Initiatives section manages
 * (social activities, rehabilitation, relief, Family to Family, earthquake
 * response), with tabs for the state, a category and a donor filter and a search
 * box. Replaces the per-category tables of InitiativesController::index().
 *
 * Its columns are named like AdminProjectIndexQuery's (number, published,
 * hidden, english_check, name), so the shared pieces work on either: `published`
 * is `online`, and an initiative has no city. Everything is bound; the search
 * escapes LIKE wildcards.
 */
final class AdminInitiativeIndexQuery
{
    public const TABS = ['all', 'pending', 'online', 'hidden', 'english'];

    private string $search = '';

    private ?int $category = null;

    private ?int $donor = null;

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
                COALESCE(SUM(initiatives.online = 0 AND initiatives.hidden = 0), 0) as pending,
                COALESCE(SUM(initiatives.online = 1), 0) as online,
                COALESCE(SUM(initiatives.hidden = 1), 0) as hidden,
                COALESCE(SUM(initiatives.english_check = 0), 0) as english
            SQL)
            ->first();

        return [
            'all' => (int) $row->total,
            'pending' => (int) $row->pending,
            'online' => (int) $row->online,
            'hidden' => (int) $row->hidden,
            'english' => (int) $row->english,
        ];
    }

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->base()
            ->select([
                'initiatives.id',
                'initiatives.id as number',
                'initiatives.online as published',
                'initiatives.hidden',
                'initiatives.english_check',
                'initiatives.logo',
                'initiatives_english.title as name',
                'initiatives_english.organization',
                'initiatives_english.date_string',
                'what_categories.name as category',
                'who_categories.name as serves',
                'sub_what_categories.name as kind',
                DB::raw('(SELECT GROUP_CONCAT(donors.name ORDER BY donors.name SEPARATOR ", ") FROM initiatives_donors JOIN donors ON donors.id = initiatives_donors.donor_id WHERE initiatives_donors.initiative_id = initiatives.id) as donors'),
                DB::raw('(SELECT COUNT(*) FROM initiatives_images WHERE initiatives_images.initiative_id = initiatives.id) as photos'),
            ])
            ->orderByDesc('initiatives.id')
            ->paginate($perPage);
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('initiatives')
            ->join('initiatives_english', 'initiatives_english.initiative_id', '=', 'initiatives.id')
            ->leftJoin('what_categories', 'what_categories.id', '=', 'initiatives.what_category_id')
            ->leftJoin('who_categories', 'who_categories.id', '=', 'initiatives.who_category_id')
            ->leftJoin('sub_what_categories', 'sub_what_categories.id', '=', 'initiatives.sub_what_category_id')
            ->whereIn('initiatives.what_category_id', ContentLookups::INITIATIVE_CATEGORIES);

        if ($this->category !== null) {
            $query->where('initiatives.what_category_id', $this->category);
        }

        if ($this->donor !== null) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('initiatives_donors')->whereColumn('initiatives_donors.initiative_id', 'initiatives.id')->where('initiatives_donors.donor_id', $this->donor));
        }

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(function (Builder $q) use ($like): void {
                $q->where('initiatives_english.title', 'like', $like)
                    ->orWhere('initiatives_english.organization', 'like', $like)
                    ->orWhere('initiatives.id', 'like', $like);
            });
        }

        if ($withTab) {
            match ($this->tab) {
                'pending' => $query->where('initiatives.online', 0)->where('initiatives.hidden', 0),
                'online' => $query->where('initiatives.online', 1),
                'hidden' => $query->where('initiatives.hidden', 1),
                'english' => $query->where('initiatives.english_check', 0),
                default => null,
            };
        }

        return $query;
    }
}
