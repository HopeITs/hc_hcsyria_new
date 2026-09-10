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
        Schema::create('childrenspecializations', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->string('name_fr', 200);
            $table->string('description', 700);
            $table->string('description_fr', 800);
            $table->string('logo', 700);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('childrenspecializations');
    }
};
