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
        Schema::create('news', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('rank')->nullable()->default(0);
            $table->integer('type')->default(0);
            $table->string('video', 200)->default('0');
            $table->text('file');
            $table->integer('english_check')->default(0);
            $table->string('french_translation', 1)->default('0');
            $table->integer('online')->default(0);
            $table->boolean('hidden')->default(false);
            $table->integer('what_category_id')->nullable()->index('fk_news2');
            $table->integer('who_category_id')->nullable()->index('fk_news1');
            $table->integer('place_id')->default(0)->index('fk_news3');
            $table->integer('edition_id')->nullable()->default(0)->index('fk_news4');
            $table->timestamps();
            $table->date('date')->nullable();
            $table->tinyInteger('priority_status')->default(0);
            $table->integer('view_count')->default(0);
            $table->integer('total_views')->default(0);
            $table->time('duration')->nullable();

            $table->index(['online', 'hidden'], 'news_state_hidden_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};
