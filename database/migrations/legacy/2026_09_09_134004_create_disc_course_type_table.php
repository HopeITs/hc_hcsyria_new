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
        Schema::create('disc_course_type', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 300);
            $table->string('name_fr', 300);
            $table->string('description', 500);
            $table->string('description_fr', 500);
            $table->string('logo', 1000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disc_course_type');
    }
};
