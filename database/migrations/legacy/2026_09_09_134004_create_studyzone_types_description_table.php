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
        Schema::create('studyzone_types_description', function (Blueprint $table) {
            $table->integer('id', true);
            $table->text('text_en');
            $table->text('text_fr');
            $table->integer('type_id')->index('fk_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studyzone_types_description');
    }
};
