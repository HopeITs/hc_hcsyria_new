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
        Schema::create('successes_english', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('success_id')->unique('success_id');
            $table->string('title', 100);
            $table->longText('text');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('successes_english');
    }
};
