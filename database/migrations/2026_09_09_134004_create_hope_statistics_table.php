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
        Schema::create('hope_statistics', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 200);
            $table->string('name_fr', 300);
            $table->string('description', 500);
            $table->string('description_fr', 700);
            $table->float('stat1_num');
            $table->float('stat2_num');
            $table->string('stat1_text', 100);
            $table->string('stat1_text_fr', 200);
            $table->string('stat2_text', 100);
            $table->string('stat2_text_fr', 200);
            $table->string('image', 600);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hope_statistics');
    }
};
