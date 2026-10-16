<?php

namespace App\Queries;

use App\Models\Edition;
use App\Models\News;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/*
 * What the news page (/our-news) reads, from legacy App\Livewire\NewsPage.
 * Every story is a news row with its English text, its place and its photos
 * (one per use: the sizetype of a photo says whether it is the tile, the
 * pinned strip or the lead).
 *
 * A story's priority_status is 0 for an ordinary one, 1 for the single
 * "important" one that leads the page and 2 for a pinned one. With no
 * important story online, the latest ordinary one leads.
 *
 * Ties on the date keep the order the database returns them in, as in
 * legacy, which sorted by date alone.
 */
final class NewsListQuery
{
    public const ORDINARY = 0;

    public const IMPORTANT = 1;

    public const PINNED = 2;

    /** The story that leads the page, or null when nothing is online. */
    public static function lead(): ?News
    {
        return self::online(withContent: true)
            ->where('priority_status', self::IMPORTANT)
            ->orderByDesc('date')
            ->first()
            ?? self::online(withContent: true)
                ->where('priority_status', self::ORDINARY)
                ->orderByDesc('date')
                ->first();
    }

    /**
     * The ordinary stories, newest first, without the lead.
     *
     * @return Collection<int, News>
     */
    public static function ordinary(int $limit, ?News $lead): Collection
    {
        return self::ordinaryQuery($lead)->limit($limit)->get();
    }

    public static function countOrdinary(?News $lead): int
    {
        return self::ordinaryQuery($lead)->count();
    }

    /** @return Collection<int, News> */
    public static function pinned(int $limit): Collection
    {
        return self::pinnedQuery()->limit($limit)->get();
    }

    public static function countPinned(): int
    {
        return self::pinnedQuery()->count();
    }

    /**
     * The headlines that scroll under the cover photo: the newest pinned
     * story, the lead, then the five latest of anything else that is online.
     *
     * @return Collection<int, News>
     */
    public static function ticker(?News $lead): Collection
    {
        $first = self::pinnedQuery(withContent: false)->first();

        $headlines = (new Collection([$first, $lead]))->filter()->unique('id')->values();

        $latest = self::online(withContent: false)
            ->whereNotIn('id', $headlines->modelKeys())
            ->orderByDesc('date')
            ->limit(5)
            ->get();

        /** @var list<News> $stories */
        $stories = [...$headlines->all(), ...$latest->all()];

        return new Collection($stories);
    }

    /** Whether any edition of the magazine is online (the news page then links to it). */
    public static function hasEdition(): bool
    {
        return Edition::query()->where('online', 1)->exists();
    }

    /** @return Builder<News> */
    private static function ordinaryQuery(?News $lead): Builder
    {
        return self::online(withContent: false)
            ->where('priority_status', self::ORDINARY)
            ->when($lead, fn (Builder $query, News $lead) => $query->where('id', '!=', $lead->id))
            ->orderByDesc('date');
    }

    /** @return Builder<News> */
    private static function pinnedQuery(bool $withContent = true): Builder
    {
        return self::online($withContent)
            ->where('priority_status', self::PINNED)
            ->orderByDesc('date');
    }

    /**
     * The stories that are online, with what a card shows. The story's text
     * is only read where a card prints it (the lead and the pinned strips).
     *
     * @return Builder<News>
     */
    private static function online(bool $withContent): Builder
    {
        return News::query()
            ->where('online', 1)
            ->with([
                'content' => fn ($query) => $query->select($withContent ? ['news_id', 'title', 'content'] : ['news_id', 'title']),
                'place:id,name',
                'newsImages' => fn ($query) => $query->select('news_id', 'path', 'sizetype', 'image_order')->orderBy('image_order')->orderBy('id'),
            ]);
    }
}
