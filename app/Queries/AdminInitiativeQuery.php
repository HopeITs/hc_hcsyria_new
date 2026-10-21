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
            ->join('initiative_contents', 'initiative_contents.initiative_id', '=', 'initiatives.id')
            ->leftJoin('what_categories', 'what_categories.id', '=', 'initiatives.what_category_id')
            ->leftJoin('who_categories', 'who_categories.id', '=', 'initiatives.who_category_id')
            ->leftJoin('sub_what_categories', 'sub_what_categories.id', '=', 'initiatives.sub_what_category_id')
            ->leftJoin('places', 'places.id', '=', 'initiatives.place_id')
            ->leftJoin('study_zone_centers', 'study_zone_centers.id', '=', 'initiatives.sz_center_id')
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
                'initiatives.place_id',
                'initiatives.sz_center_id as centre_id',
                'initiative_contents.title as name',
                'initiative_contents.organization',
                'initiative_contents.date_string',
                'initiative_contents.description',
                'initiative_contents.content',
                'what_categories.name as category',
                'who_categories.name as serves',
                'sub_what_categories.name as kind',
                'places.name as place',
                'study_zone_centers.name as centre',
                DB::raw('(SELECT COUNT(*) FROM initiative_images WHERE initiative_images.initiative_id = initiatives.id) as photos'),
                DB::raw('(SELECT COUNT(*) FROM testimonies WHERE testimonies.initiative_id = initiatives.id) as testimonies'),
            ])
            ->first();
    }

    /** @return Collection<int, object{id: int, name: string}> */
    public static function donors(int $id): Collection
    {
        return DB::table('initiative_donors')
            ->join('donors', 'donors.id', '=', 'initiative_donors.donor_id')
            ->where('initiative_donors.initiative_id', $id)
            ->orderBy('donors.name')
            ->get(['donors.id', 'donors.name']);
    }
}
