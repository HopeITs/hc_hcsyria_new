<?php

namespace App\Queries;

use Illuminate\Support\Facades\DB;

/*
 * One success story as the staff pages show it: its columns and English text, the
 * names behind the ids and the micro project it is linked to. The state columns
 * are named like AdminProjectQuery's so the shared pieces work on either; a
 * story's `number` is its id, and it is neither hidden nor in a city.
 */
final class AdminSuccessQuery
{
    public static function find(int $id): ?object
    {
        return DB::table('successes')
            ->join('success_contents', 'success_contents.success_id', '=', 'successes.id')
            ->leftJoin('what_categories', 'what_categories.id', '=', 'successes.what_category_id')
            ->leftJoin('sub_what_categories', 'sub_what_categories.id', '=', 'successes.sub_what_category_id')
            ->leftJoin('who_categories', 'who_categories.id', '=', 'successes.who_category_id')
            ->leftJoin('places', 'places.id', '=', 'successes.place_id')
            ->leftJoin('study_zone_centers', 'study_zone_centers.id', '=', 'successes.sz_center_id')
            ->leftJoin('projects', 'projects.id', '=', 'successes.project_id')
            ->leftJoin('project_contents', 'project_contents.project_id', '=', 'projects.id')
            ->where('successes.id', $id)
            ->select([
                'successes.id',
                'successes.id as number',
                'successes.online as published',
                DB::raw('0 as hidden'),
                'successes.english_check',
                'successes.image_path',
                'successes.video',
                'successes.created_at',
                'successes.what_category_id as category_id',
                'successes.who_category_id as serves_id',
                'successes.sub_what_category_id as kind_id',
                'successes.place_id',
                'successes.sz_center_id as centre_id',
                'successes.project_id',
                'success_contents.title as name',
                'success_contents.text',
                'what_categories.name as category',
                'sub_what_categories.name as kind',
                'who_categories.name as serves',
                'places.name as place',
                'study_zone_centers.name as centre',
                'projects.MicroProject_ID as project_number',
                'project_contents.MPE_Name as project_name',
            ])
            ->first();
    }
}
