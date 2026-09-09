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
        Schema::create('profile', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('image_path', 50);
            $table->string('belong_to', 10)->default('hope')->index('belong_to');
            $table->unsignedInteger('rank')->default(0);
            $table->string('first_name', 15);
            $table->string('last_name', 15);
            $table->string('position', 100);
            $table->string('position_french', 100);
            $table->string('facebook_link', 50);
            $table->string('email', 30);
            $table->string('linkedin_link', 50);
            $table->string('phone', 20);
            $table->string('inactive', 10);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile');
    }
};
