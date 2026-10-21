<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/*
 * The staff view of the Roots of Hope magazine editions: the list (tabs for online and
 * pending, a search box) and one edition. Its columns are named like the other lists'
 * (number, published, hidden, name) so the shared tabs, status pill and buttons work on
 * it. An edition has only two states, so it is never hidden and its English is not checked.
 */
final class AdminRootsQuery
{
    public const TABS = ['all', 'pending', 'online'];

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
        $row = $this->base(withTab: false)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(roots_of_hope.online = 0), 0) as pending, COALESCE(SUM(roots_of_hope.online = 1), 0) as online')
            ->first();

        return ['all' => (int) $row->total, 'pending' => (int) $row->pending, 'online' => (int) $row->online];
    }

    /** @return LengthAwarePaginator<int, object> */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->base()->select(self::columns())->orderByDesc('roots_of_hope.id')->paginate($perPage);
    }

    public static function find(int $id): ?object
    {
        return DB::table('roots_of_hope')->where('roots_of_hope.id', $id)->first(self::columns());
    }

    /** @return list<mixed> */
    private static function columns(): array
    {
        return [
            'roots_of_hope.id',
            'roots_of_hope.id as number',
            'roots_of_hope.online as published',
            DB::raw('0 as hidden'),
            'roots_of_hope.title as name',
            'roots_of_hope.image_path',
            'roots_of_hope.file_path',
            'roots_of_hope.url',
            'roots_of_hope.created_at',
        ];
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('roots_of_hope');

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(fn (Builder $q) => $q->where('roots_of_hope.title', 'like', $like)->orWhere('roots_of_hope.id', 'like', $like));
        }

        if ($withTab) {
            match ($this->tab) {
                'pending' => $query->where('roots_of_hope.online', 0),
                'online' => $query->where('roots_of_hope.online', 1),
                default => null,
            };
        }

        return $query;
    }
}
