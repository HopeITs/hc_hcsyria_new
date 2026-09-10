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
        Schema::table('news', function (Blueprint $table) {
            $table->foreign(['place_id'], 'FK_news3')->references(['id'])->on('places')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['edition_id'], 'FK_news4')->references(['id'])->on('editions')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign('FK_news3');
            $table->dropForeign('FK_news4');
        });
    }
};
