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
        Schema::table('students', function (Blueprint $table) {
            $table->foreign(['street_id'], 'FK_student1')->references(['id'])->on('street')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['educationfield_id'], 'FK_student2')->references(['id'])->on('educationfield')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['educationlevel_id'], 'FK_students3')->references(['id'])->on('educationlevel')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['gender_id'], 'FK_students4')->references(['id'])->on('genders')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['religion_id'], 'FK_students5')->references(['id'])->on('religions')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign('FK_student1');
            $table->dropForeign('FK_student2');
            $table->dropForeign('FK_students3');
            $table->dropForeign('FK_students4');
            $table->dropForeign('FK_students5');
        });
    }
};
