<?php

namespace App\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * One newsletter item as the staff pages show it: its columns and English text,
 * the names behind the ids, its tags and its pictures' count. The state columns
 * are named like AdminProjectQuery's so the shared pieces work on either; an item's
 * `number` is its id and it has no city.
 */
final class AdminNewsQuery
{
    public static function find(int $id): ?object
    {
        return DB::table('news')
            ->join('news_english', 'news_english.news_id', '=', 'news.id')
            ->leftJoin('places', 'places.id', '=', 'news.place_id')
            ->leftJoin('editions', 'editions.id', '=', 'news.edition_id')
            ->where('news.id', $id)
            ->select([
                'news.id',
                'news.id as number',
                'news.online as published',
                'news.hidden',
                'news.english_check',
                'news.type',
                'news.date',
                'news.rank',
                'news.video',
                'news.file',
                'news.place_id',
                'news.edition_id',
                'news.priority_status as priority',
                'news.view_count',
                'news.total_views',
                'news.created_at',
                'news_english.title as name',
                'news_english.description',
                'news_english.content',
                'places.name as place',
                'editions.name as edition',
                DB::raw('(SELECT COUNT(*) FROM news_images WHERE news_images.news_id = news.id) as photos'),
            ])
            ->first();
    }

    /**
     * The paths of its thumbnail (0), Important picture (1) and Pinned picture (2), by kind; a kind it has none of is absent.
     *
     * @return array<int, string>
     */
    public static function singles(int $id): array
    {
        return DB::table('news_images')->where('news_id', $id)->whereIn('sizetype', [0, 1, 2])->orderBy('id')->pluck('path', 'sizetype')->all();
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function tags(int $id): Collection
    {
        return DB::table('news_tags')
            ->join('tags', 'tags.id', '=', 'news_tags.tag_id')
            ->where('news_tags.news_id', $id)
            ->orderBy('tags.name')
            ->get(['tags.id', 'tags.name']);
    }
}
