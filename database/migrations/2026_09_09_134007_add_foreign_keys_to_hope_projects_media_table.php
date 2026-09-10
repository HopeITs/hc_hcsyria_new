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
        Schema::table('hope_projects_media', function (Blueprint $table) {
            $table->foreign(['project_id'], 'fk_project_media')->references(['id'])->on('hope_projects')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hope_projects_media', function (Blueprint $table) {
            $table->dropForeign('fk_project_media');
        });
    }
};
