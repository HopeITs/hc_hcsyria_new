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
        Schema::create('initiatives_images', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('initiative_id')->index('fk_initiatives_images1');
            $table->string('path', 150);
            $table->integer('image_order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives_images');
    }
};
