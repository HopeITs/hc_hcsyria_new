<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('CREATE PROCEDURE `clear_dublicates_entries`()
Begin

DELETE c1
FROM student_entries c1
INNER JOIN student_entries c2 
WHERE
    c1.id < c2.id AND 
    c1.`user_id` = c2.`user_id` AND 
    c1.`student_id` = c2.`student_id` AND 
    c1.`center_id` = c2.`center_id` AND
    c1.`note` = c2.`note` AND 
    
    day(c1.`created_at`) = day(c2.`created_at`) AND
    month(c1.`created_at`) = month(c2.`created_at`) AND
    year(c1.`created_at`) = year(c2.`created_at`);
END');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS clear_dublicates_entries');
    }
};
