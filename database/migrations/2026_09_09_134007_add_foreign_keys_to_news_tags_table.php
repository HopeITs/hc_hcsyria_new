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
        Schema::table('news_tags', function (Blueprint $table) {
            $table->foreign(['news_id'], 'news_id_fk')->references(['id'])->on('news')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['tag_id'], 'tag_id_fk')->references(['id'])->on('tags')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news_tags', function (Blueprint $table) {
            $table->dropForeign('news_id_fk');
            $table->dropForeign('tag_id_fk');
        });
    }
};
