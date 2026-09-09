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
        Schema::create('testimonies_english', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('testimony_id')->index('fk_testemonies_en1');
            $table->string('name', 70);
            $table->longText('text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonies_english');
    }
};
