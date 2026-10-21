<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/*
 * Cutover step 2 of 3: plural snake_case names, pivots named after the two
 * tables they join, and the *_english tables named for what they hold (the
 * only copy of each text, now that French is gone).
 *
 * Renaming keeps rows, indexes and foreign keys. Column names are unchanged.
 */
return new class extends Migration
{
    private const RENAMES = [
        'age' => 'ages',
        'category' => 'project_categories',
        'subcategory' => 'project_sub_categories',
        'projecttype' => 'project_types',
        'profile' => 'team_members',
        'backgrounds' => 'covers',
        'familypartners' => 'family_partners',
        'studyzonecenters' => 'study_zone_centers',
        'rootsofhope' => 'roots_of_hope',
        'initiatives_english' => 'initiative_contents',
        'news_english' => 'news_contents',
        'projects_english' => 'project_contents',
        'successes_english' => 'success_contents',
        'testimonies_english' => 'testimony_contents',
        'initiatives_donors' => 'initiative_donors',
        'projects_donors' => 'project_donors',
        'initiatives_images' => 'initiative_images',
        'initiatives_images_rehabilitation' => 'initiative_rehabilitation_images',
        'hope_projects_media' => 'hope_project_media',
        'users_roles' => 'role_user',
        'users_permissions' => 'permission_user',
        'users_places' => 'place_user',
        'users_centers' => 'study_zone_center_user',
    ];

    public function up(): void
    {
        foreach (self::RENAMES as $old => $new) {
            if (Schema::hasTable($old) && ! Schema::hasTable($new)) {
                Schema::rename($old, $new);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::RENAMES, true) as $old => $new) {
            if (Schema::hasTable($new) && ! Schema::hasTable($old)) {
                Schema::rename($new, $old);
            }
        }
    }
};
