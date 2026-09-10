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
        Schema::table('studyzone_types_description', function (Blueprint $table) {
            $table->foreign(['type_id'], 'FK_type_id')->references(['id'])->on('studyzone_types')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studyzone_types_description', function (Blueprint $table) {
            $table->dropForeign('FK_type_id');
        });
    }
};
