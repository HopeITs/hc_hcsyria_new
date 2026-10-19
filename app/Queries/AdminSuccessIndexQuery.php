<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/*
 * The staff list of success stories: the categories the Successes section manages
 * (ContentLookups::SUCCESS_CATEGORIES), with tabs for the state, a category and a
 * who-it-serves filter and a search box. Replaces the per-programme tables of the
 * legacy SuccessController, StudyController and DiscController lists.
 *
 * Its columns are named like AdminProjectIndexQuery's (number, published,
 * english_check, name), so the shared pieces work on either: `published` is
 * `online`. A story is not hidden or held in a city, so those columns are fixed.
 * Everything is bound; the search escapes LIKE wildcards.
 */
final class AdminSuccessIndexQuery
{
    public const TABS = ['all', 'pending', 'online', 'english'];

    private string $search = '';

    private ?int $category = null;

    private ?int $serves = null;

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

    public function serving(int|string|null $id): self
    {
        $this->serves = $id === null || $id === '' ? null : (int) $id;

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
                COALESCE(SUM(successes.online = '0'), 0) as pending,
                COALESCE(SUM(successes.online = '1'), 0) as online,
                COALESCE(SUM(successes.english_check = 0), 0) as english
            SQL)
            ->first();

        return [
            'all' => (int) $row->total,
            'pending' => (int) $row->pending,
            'online' => (int) $row->online,
            'english' => (int) $row->english,
        ];
    }

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->base()
            ->select([
                'successes.id',
                'successes.id as number',
                'successes.online as published',
                DB::raw('0 as hidden'),
                'successes.english_check',
                'successes.image_path',
                'successes_english.title as name',
                'what_categories.name as category',
                'sub_what_categories.name as kind',
                'who_categories.name as serves',
                'places.name as place',
                'studyzonecenters.name as centre',
            ])
            ->orderByDesc('successes.id')
            ->paginate($perPage);
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('successes')
            ->join('successes_english', 'successes_english.success_id', '=', 'successes.id')
            ->leftJoin('what_categories', 'what_categories.id', '=', 'successes.what_category_id')
            ->leftJoin('sub_what_categories', 'sub_what_categories.id', '=', 'successes.sub_what_category_id')
            ->leftJoin('who_categories', 'who_categories.id', '=', 'successes.who_category_id')
            ->leftJoin('places', 'places.id', '=', 'successes.place_id')
            ->leftJoin('studyzonecenters', 'studyzonecenters.id', '=', 'successes.sz_center_id')
            ->whereIn('successes.what_category_id', ContentLookups::SUCCESS_CATEGORIES);

        if ($this->category !== null) {
            $query->where('successes.what_category_id', $this->category);
        }

        if ($this->serves !== null) {
            $query->where('successes.who_category_id', $this->serves);
        }

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(fn (Builder $q) => $q->where('successes_english.title', 'like', $like)->orWhere('successes.id', 'like', $like));
        }

        if ($withTab) {
            match ($this->tab) {
                'pending' => $query->where('successes.online', '0'),
                'online' => $query->where('successes.online', '1'),
                'english' => $query->where('successes.english_check', 0),
                default => null,
            };
        }

        return $query;
    }
}
