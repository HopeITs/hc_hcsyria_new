<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_entries', function (Blueprint $table) {
            $table->foreign(['student_id'], 'FK_student_entries1')->references(['id'])->on('students')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['center_id'], 'FK_student_entries2')->references(['id'])->on('studyzonecenters')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['user_id'], 'FK_student_entries3')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_entries', function (Blueprint $table) {
            $table->dropForeign('FK_student_entries1');
            $table->dropForeign('FK_student_entries2');
            $table->dropForeign('FK_student_entries3');
        });
    }
};
