<?php

namespace App\Queries;

use App\Enums\ContentKind;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/*
 * One micro-project as the staff pages show it: every column and the English
 * text, the names behind the ids, the photo count, and (separately) its photos
 * and the last things done to it. The column names match AdminProjectIndexQuery
 * rows, so the shared pieces (ProjectActions, ProjectReadiness, the status pill)
 * work on either.
 */
final class AdminProjectQuery
{
    public static function find(int $id): ?object
    {
        return DB::table('projects')
            ->leftJoin('project_contents', 'project_contents.project_id', '=', 'projects.id')
            ->leftJoin('places', 'places.id', '=', 'projects.MPE_City')
            ->leftJoin('project_types', 'project_types.id', '=', 'projects.projecttype_id')
            ->leftJoin('project_sub_categories', 'project_sub_categories.ID', '=', 'projects.SubCategory_ID')
            ->leftJoin('project_categories', 'project_categories.ID', '=', 'project_sub_categories.Category_ID')
            ->leftJoin('donors', 'donors.id', '=', 'projects.MPE_Donor')
            ->where('projects.id', $id)
            ->select([
                'projects.id',
                'projects.MicroProject_ID as number',
                'projects.MPE_City as city_id',
                'projects.MPE_Published as published',
                'projects.MPE_Done as done',
                'projects.hidden',
                'projects.english_check',
                'projects.MPE_PublishDate as published_at',
                'projects.MPE_FundedDate as funded',
                'projects.MPE_BeneficiaryName as beneficiary',
                'projects.MPE_PartnerName as partner',
                'projects.MPE_PartnerName2 as partner2',
                'projects.MPE_Video as video',
                'projects.MPE_Donor as donor_id',
                'projects.DOB as birth_year',
                'projects.FamilyMembers as family_members',
                'projects.projecttype_id as kind_id',
                'projects.SubCategory_ID as sub_category_id',
                'projects.Mobile as mobile',
                'projects.latitude',
                'projects.longtitude as longitude',
                'project_contents.MPE_Name as name',
                'project_contents.MPE_Suffering as story',
                'project_contents.MPE_ExpSkill as skills',
                'places.name as city',
                'project_types.name as kind',
                'project_categories.ID as category_id',
                'project_categories.Name as category',
                'project_sub_categories.Name as sub_category',
                'donors.name as donor',
                DB::raw('(SELECT COUNT(*) FROM images WHERE images.project_id = projects.id) as photos'),
            ])
            ->first();
    }

    /** The last things done to a project (or an initiative, by $kind), newest first. @return Collection<int, object{operation: string, by: ?string, at: string}> */
    public static function history(int $projectId, int $limit = 8, ContentKind $kind = ContentKind::Project): Collection
    {
        return DB::table('logs')
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->where('logs.category', $kind->logCategory())
            ->where('logs.project_id', $projectId)
            ->orderByDesc('logs.id')
            ->limit($limit)
            ->get(['logs.operation', 'users.name as by', 'logs.created_at as at']);
    }
}
