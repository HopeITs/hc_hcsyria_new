<?php

namespace App\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * One initiative as the staff pages show it: its columns and English text, the
 * names behind the ids, and (separately) its donors, photos and testimonies. The
 * state columns are named like AdminProjectQuery's so the shared pieces work on
 * either; an initiative's `number` is its id and it has no city.
 */
final class AdminInitiativeQuery
{
    public static function find(int $id): ?object
    {
        return DB::table('initiatives')
            ->join('initiatives_english', 'initiatives_english.initiative_id', '=', 'initiatives.id')
            ->leftJoin('what_categories', 'what_categories.id', '=', 'initiatives.what_category_id')
            ->leftJoin('who_categories', 'who_categories.id', '=', 'initiatives.who_category_id')
            ->leftJoin('sub_what_categories', 'sub_what_categories.id', '=', 'initiatives.sub_what_category_id')
            ->where('initiatives.id', $id)
            ->select([
                'initiatives.id',
                'initiatives.id as number',
                'initiatives.online as published',
                'initiatives.hidden',
                'initiatives.english_check',
                'initiatives.logo',
                'initiatives.video',
                'initiatives.created_at',
                'initiatives.what_category_id as category_id',
                'initiatives.who_category_id as serves_id',
                'initiatives.sub_what_category_id as kind_id',
                'initiatives_english.title as name',
                'initiatives_english.organization',
                'initiatives_english.date_string',
                'initiatives_english.description',
                'initiatives_english.content',
                'what_categories.name as category',
                'who_categories.name as serves',
                'sub_what_categories.name as kind',
                DB::raw('(SELECT COUNT(*) FROM initiatives_images WHERE initiatives_images.initiative_id = initiatives.id) as photos'),
                DB::raw('(SELECT COUNT(*) FROM testimonies WHERE testimonies.initiative_id = initiatives.id) as testimonies'),
            ])
            ->first();
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function donors(int $id): Collection
    {
        return DB::table('initiatives_donors')
            ->join('donors', 'donors.id', '=', 'initiatives_donors.donor_id')
            ->where('initiatives_donors.initiative_id', $id)
            ->orderBy('donors.name')
            ->get(['donors.id', 'donors.name']);
    }

    /** @return Collection<int, object{id: int, path: string}> */
    public static function photos(int $id): Collection
    {
        return DB::table('initiatives_images')->where('initiative_id', $id)->orderBy('image_order')->orderBy('id')->get(['id', 'path']);
    }
}
