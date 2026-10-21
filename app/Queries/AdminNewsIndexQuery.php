<?php

namespace App\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/*
 * The staff list of newsletter items: tabs for the state (pending, online, hidden,
 * English not checked), a kind, a place and a tag filter and a search box. Replaces
 * the per-kind tables of NewsController::index().
 *
 * Its columns are named like AdminProjectIndexQuery's (number, published, hidden,
 * english_check, name), so the shared pieces work on either: `published` is
 * `online`, and an item has no city of its own to limit by. Everything is bound;
 * the search escapes LIKE wildcards.
 */
final class AdminNewsIndexQuery
{
    public const TABS = ['all', 'pending', 'online', 'hidden', 'english'];

    private string $search = '';

    private ?int $type = null;

    private ?int $place = null;

    private ?int $tag = null;

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

    public function ofType(int|string|null $type): self
    {
        $this->type = $type === null || $type === '' ? null : (int) $type;

        return $this;
    }

    public function inPlace(int|string|null $id): self
    {
        $this->place = $id === null || $id === '' ? null : (int) $id;

        return $this;
    }

    public function taggedWith(int|string|null $id): self
    {
        $this->tag = $id === null || $id === '' ? null : (int) $id;

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
                COALESCE(SUM(news.online = 0 AND news.hidden = 0), 0) as pending,
                COALESCE(SUM(news.online = 1), 0) as online,
                COALESCE(SUM(news.hidden = 1), 0) as hidden,
                COALESCE(SUM(news.english_check = 0), 0) as english
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
                'news.id',
                'news.id as number',
                'news.online as published',
                'news.hidden',
                'news.english_check',
                'news.type',
                'news.date',
                'news.priority_status as priority',
                'news.file',
                'news_contents.title as name',
                'places.name as place',
                'editions.name as edition',
                DB::raw('(SELECT GROUP_CONCAT(tags.name ORDER BY tags.name SEPARATOR ", ") FROM news_tags JOIN tags ON tags.id = news_tags.tag_id WHERE news_tags.news_id = news.id) as tags'),
            ])
            ->orderByDesc('news.date')
            ->orderByDesc('news.id')
            ->paginate($perPage);
    }

    private function base(bool $withTab = true): Builder
    {
        $query = DB::table('news')
            ->join('news_contents', 'news_contents.news_id', '=', 'news.id')
            ->leftJoin('places', 'places.id', '=', 'news.place_id')
            ->leftJoin('editions', 'editions.id', '=', 'news.edition_id');

        if ($this->type !== null) {
            $query->where('news.type', $this->type);
        }

        if ($this->place !== null) {
            $query->where('news.place_id', $this->place);
        }

        if ($this->tag !== null) {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from('news_tags')->whereColumn('news_tags.news_id', 'news.id')->where('news_tags.tag_id', $this->tag));
        }

        if ($this->search !== '') {
            $like = '%'.addcslashes($this->search, '%_\\').'%';

            $query->where(fn (Builder $q) => $q->where('news_contents.title', 'like', $like)->orWhere('news.id', 'like', $like));
        }

        if ($withTab) {
            match ($this->tab) {
                'pending' => $query->where('news.online', 0)->where('news.hidden', 0),
                'online' => $query->where('news.online', 1),
                'hidden' => $query->where('news.hidden', 1),
                'english' => $query->where('news.english_check', 0),
                default => null,
            };
        }

        return $query;
    }
}
