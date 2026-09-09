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
        Schema::create('successes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('image_path', 150);
            $table->string('inner_image_path', 150);
            $table->string('inner_big_image_path', 150);
            $table->string('video', 100)->default('0');
            $table->integer('who_category_id')->index('fk_successes1');
            $table->integer('what_category_id')->index('fk_successes2');
            $table->integer('sub_what_category_id')->nullable()->index('fk_successes5');
            $table->integer('place_id')->default(0)->index('fk_successes6');
            $table->integer('sz_center_id')->nullable()->index('fk_successes3');
            $table->integer('project_id')->nullable()->index('fk_successes4');
            $table->integer('english_check')->default(0);
            $table->string('french_translation', 1)->default('0');
            $table->string('online', 1)->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('successes');
    }
};
