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
        Schema::table('family_statistics', function (Blueprint $table) {
            $table->foreign(['family_statis_type_id'], 'FK_familystatistics1')->references(['id'])->on('family_statis_type')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_statistics', function (Blueprint $table) {
            $table->dropForeign('FK_familystatistics1');
        });
    }
};
