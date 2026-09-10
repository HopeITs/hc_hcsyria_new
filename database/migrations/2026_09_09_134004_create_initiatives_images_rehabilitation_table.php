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
        Schema::create('initiatives_images_rehabilitation', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('path_1', 150);
            $table->string('path_2', 150);
            $table->integer('initiative_id')->index('fk_initiatives_imagesrehab1');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives_images_rehabilitation');
    }
};
