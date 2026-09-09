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
        Schema::create('initiatives', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('logo', 100)->default('0');
            $table->string('video', 100);
            $table->integer('rank')->default(0);
            $table->integer('english_check')->default(0);
            $table->string('french_translation', 1)->default('0');
            $table->integer('online')->default(0);
            $table->boolean('hidden')->default(false);
            $table->integer('what_category_id')->index('fk_initiatives2');
            $table->integer('who_category_id')->index('fk_initiatives1');
            $table->integer('sub_what_category_id')->nullable()->index('fk_initiatives3');
            $table->integer('place_id')->default(0)->index('fk_initiatives4');
            $table->integer('sz_center_id')->nullable()->index('fk_initiatives5');
            $table->timestamps();

            $table->index(['online', 'hidden'], 'initiatives_state_hidden_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives');
    }
};
