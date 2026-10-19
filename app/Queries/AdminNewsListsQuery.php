<?php

namespace App\Queries;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * What the newsletter's own lists show: the tags and the editions with how many news
 * items use each, and the subscribers (searchable, a page at a time).
 */
final class AdminNewsListsQuery
{
    /** @return Collection<int, object{id: int, name: string, news: int}> */
    public static function tags(): Collection
    {
        return DB::table('tags')
            ->leftJoin('news_tags', 'news_tags.tag_id', '=', 'tags.id')
            ->groupBy('tags.id', 'tags.name')
            ->orderBy('tags.name')
            ->get(['tags.id', 'tags.name', DB::raw('COUNT(news_tags.id) as news')]);
    }

    /** @return Collection<int, object{id: int, name: string, name_french: string, online: int, news: int}> */
    public static function editions(): Collection
    {
        return DB::table('editions')
            ->leftJoin('news', 'news.edition_id', '=', 'editions.id')
            ->groupBy('editions.id', 'editions.name', 'editions.name_french', 'editions.online')
            ->orderBy('editions.id')
            ->get(['editions.id', 'editions.name', 'editions.name_french', 'editions.online', DB::raw('COUNT(news.id) as news')]);
    }

    /** @return LengthAwarePaginator<int, object{id: int, mail: string, active: int, created_at: string}> */
    public static function subscribers(string $search = '', int $perPage = 25): LengthAwarePaginator
    {
        return DB::table('newsletter_subscribers')
            ->when(trim($search) !== '', fn ($query) => $query->where('mail', 'like', '%'.addcslashes(trim($search), '%_\\').'%'))
            ->orderByDesc('id')
            ->paginate($perPage, ['id', 'mail', 'active', 'created_at']);
    }

    public static function subscriberCount(): int
    {
        return (int) DB::table('newsletter_subscribers')->count();
    }
}
