<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Cutover step 1 of 3: drop the tables the new site does not use.
 *
 *  - The students module (students, student_entries and the lookups only they
 *    point at) belongs to the Study Zone tracking app, which has its own
 *    database.
 *  - The *_french tables hold the French copy of the content, which the new
 *    site does not have.
 *  - The rest have no reader or writer: loose dumps with no primary key,
 *    lookups nothing joins to, and statistics tables the site computes itself.
 *
 * This deletes data. Take a backup of hcsyria_db first.
 */
return new class extends Migration
{
    /** Children before parents, so every foreign key is gone before its target. */
    private const TABLES = [
        // students module
        'student_entries',
        'students',
        'educationfield',
        'educationdegree',
        'educationlevel',
        'religions',
        'genders',
        'street',
        // French copy of the content
        'initiatives_french',
        'news_french',
        'projects_french',
        'successes_french',
        'testimonies_french',
        // unused
        'family_statistics',
        'family_statis_type',
        'studyzone_types_description',
        'studyzone_types',
        'hope_statistics',
        'childrenspecializations',
        'disc_course_type',
        'searchtype',
        'info_disc',
        'youthprofiles',
    ];

    public function up(): void
    {
        // It deletes duplicate student_entries rows, so it goes with the table.
        DB::unprepared('DROP PROCEDURE IF EXISTS clear_dublicates_entries');

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Dropped tables cannot be rebuilt here; restore them from the pre-cutover backup.');
    }
};
