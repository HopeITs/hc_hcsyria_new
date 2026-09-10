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
        Schema::table('testimonies_french', function (Blueprint $table) {
            $table->foreign(['testimony_id'], 'FK_testemonies_fr1')->references(['id'])->on('testimonies')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('testimonies_french', function (Blueprint $table) {
            $table->dropForeign('FK_testemonies_fr1');
        });
    }
};
