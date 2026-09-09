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
        Schema::table('successes', function (Blueprint $table) {
            $table->foreign(['who_category_id'], 'FK_successes1')->references(['id'])->on('who_categories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['what_category_id'], 'FK_successes2')->references(['id'])->on('what_categories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['sz_center_id'], 'FK_successes3')->references(['id'])->on('studyzonecenters')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['project_id'], 'FK_successes4')->references(['id'])->on('projects')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['sub_what_category_id'], 'FK_successes5')->references(['id'])->on('sub_what_categories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['place_id'], 'FK_successes6')->references(['id'])->on('places')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('successes', function (Blueprint $table) {
            $table->dropForeign('FK_successes1');
            $table->dropForeign('FK_successes2');
            $table->dropForeign('FK_successes3');
            $table->dropForeign('FK_successes4');
            $table->dropForeign('FK_successes5');
            $table->dropForeign('FK_successes6');
        });
    }
};
