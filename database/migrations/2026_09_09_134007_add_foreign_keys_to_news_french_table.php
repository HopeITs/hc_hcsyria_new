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
        Schema::table('news_french', function (Blueprint $table) {
            $table->foreign(['news_id'], 'FK_news_french1')->references(['id'])->on('news')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_french', function (Blueprint $table) {
            $table->dropForeign('FK_news_french1');
        });
    }
};
