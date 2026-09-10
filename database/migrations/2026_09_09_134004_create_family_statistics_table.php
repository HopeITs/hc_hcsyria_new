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
        Schema::create('family_statistics', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 300);
            $table->string('name_fr', 500);
            $table->float('statis_num')->nullable();
            $table->string('logo', 500);
            $table->integer('family_statis_type_id')->index('fk_familystatistics1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_statistics');
    }
};
