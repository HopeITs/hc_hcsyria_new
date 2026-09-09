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
        Schema::create('testimonies_french', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('testimony_id')->index('fk_testemonies_fr1');
            $table->string('name', 70);
            $table->longText('text');
            $table->integer('translation_paid')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('testimonies_french');
    }
};
