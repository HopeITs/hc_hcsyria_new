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
        DB::unprepared("CREATE PROCEDURE `get_statistics`()
BEGIN

    

    DECLARE EXIT HANDLER FOR SQLEXCEPTION 

    BEGIN

        

        SELECT NULL AS adisc_applied_students, 

               NULL AS adisc_completed_students, 

               NULL AS adisc_employed_students,

               NULL AS hdisc_applied_students, 

               NULL AS hdisc_completed_students, 

               NULL AS hdisc_employed_students,

               NULL AS ddisc_applied_students, 

               NULL AS ddisc_completed_students, 

               NULL AS ddisc_employed_students;

    END;

    

    SELECT 

        (SELECT COUNT(hcsyria_disc_a.student_demand.ID) 

         FROM hcsyria_disc_a.student_demand 

         WHERE hcsyria_disc_a.student_demand.Subject_ID = 0) AS adisc_applied_students,

        (SELECT COUNT(DISTINCT hcsyria_disc_a.student_traininggroup.Student_ID) 

         FROM hcsyria_disc_a.student_traininggroup

         LEFT JOIN hcsyria_disc_a.state ON (hcsyria_disc_a.state.ID = hcsyria_disc_a.student_traininggroup.State_ID)

         WHERE hcsyria_disc_a.state.Name LIKE 'Finished') AS adisc_completed_students,

        (SELECT COUNT(hcsyria_disc_a.student.ID) 

         FROM hcsyria_disc_a.student 

         WHERE hcsyria_disc_a.student.Employed = 1) AS adisc_employed_students,

        (SELECT COUNT(hcsyria_disc_h.student_demand.ID) 

         FROM hcsyria_disc_h.student_demand 

         WHERE hcsyria_disc_h.student_demand.Subject_ID = 0) AS hdisc_applied_students,

        (SELECT COUNT(DISTINCT hcsyria_disc_h.student_traininggroup.Student_ID) 

         FROM hcsyria_disc_h.student_traininggroup

         LEFT JOIN hcsyria_disc_h.state ON (hcsyria_disc_h.state.ID = hcsyria_disc_h.student_traininggroup.State_ID)

         WHERE hcsyria_disc_h.state.Name LIKE 'Finished') AS hdisc_completed_students,

        (SELECT COUNT(hcsyria_disc_h.student.ID) 

         FROM hcsyria_disc_h.student 

         WHERE hcsyria_disc_h.student.Employed = 1) AS hdisc_employed_students,

        (SELECT COUNT(hcsyria_disc_d.student_demand.ID) 

         FROM hcsyria_disc_d.student_demand 

         WHERE hcsyria_disc_d.student_demand.Subject_ID = 0) AS ddisc_applied_students,

        (SELECT COUNT(DISTINCT hcsyria_disc_d.student_traininggroup.Student_ID) 

         FROM hcsyria_disc_d.student_traininggroup

         LEFT JOIN hcsyria_disc_d.state ON (hcsyria_disc_d.state.ID = hcsyria_disc_d.student_traininggroup.State_ID)

         WHERE hcsyria_disc_d.state.Name LIKE 'Finished') AS ddisc_completed_students,

        (SELECT COUNT(hcsyria_disc_d.student.ID) 

         FROM hcsyria_disc_d.student 

         WHERE hcsyria_disc_d.student.Employed = 1) AS ddisc_employed_students;

END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS get_statistics");
    }
};
