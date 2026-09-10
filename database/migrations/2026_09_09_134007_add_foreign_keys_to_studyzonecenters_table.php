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
        Schema::table('studyzonecenters', function (Blueprint $table) {
            $table->foreign(['place_id'], 'FK_sz_centers1')->references(['id'])->on('places')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studyzonecenters', function (Blueprint $table) {
            $table->dropForeign('FK_sz_centers1');
        });
    }
};
