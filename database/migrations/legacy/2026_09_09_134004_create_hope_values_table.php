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
        Schema::create('hope_values', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 200);
            $table->text('description');
            $table->text('quote');
            $table->string('quote_source', 20);
            $table->string('name_fr', 200);
            $table->text('description_fr');
            $table->text('quote_fr');
            $table->string('quote_source_fr', 20);
            $table->string('image_path', 200);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hope_values');
    }
};
