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
        Schema::create('who_categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->string('french_name', 50);
            $table->longText('description');
            $table->longText('french_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('who_categories');
    }
};
