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
        Schema::create('news_french', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('news_id')->index('fk_news_french1');
            $table->string('title', 120);
            $table->string('description', 1100);
            $table->longText('content');
            $table->char('translation_paid', 1)->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_french');
    }
};
