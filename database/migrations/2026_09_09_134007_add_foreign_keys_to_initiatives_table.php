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
        Schema::table('initiatives', function (Blueprint $table) {
            $table->foreign(['who_category_id'], 'FK_initiatives1')->references(['id'])->on('who_categories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['what_category_id'], 'FK_initiatives2')->references(['id'])->on('what_categories')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['sub_what_category_id'], 'FK_initiatives3')->references(['id'])->on('sub_what_categories')->onUpdate('cascade')->onDelete('set null');
            $table->foreign(['place_id'], 'FK_initiatives4')->references(['id'])->on('places')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['sz_center_id'], 'FK_initiatives5')->references(['id'])->on('studyzonecenters')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('initiatives', function (Blueprint $table) {
            $table->dropForeign('FK_initiatives1');
            $table->dropForeign('FK_initiatives2');
            $table->dropForeign('FK_initiatives3');
            $table->dropForeign('FK_initiatives4');
            $table->dropForeign('FK_initiatives5');
        });
    }
};
