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
        Schema::create('initiatives_english', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('initiative_id')->index('fk_initiatives_english1');
            $table->string('title', 70);
            $table->string('organization', 70);
            $table->string('date_string', 70);
            $table->string('description', 400);
            $table->longText('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives_english');
    }
};
