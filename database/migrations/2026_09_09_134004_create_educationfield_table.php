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
        Schema::create('educationfield', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 200)->nullable();
            $table->string('name_en', 200)->nullable();
            $table->string('name_en_abb', 100);
            $table->integer('educationdegree_id')->nullable()->index('fk_educationfield1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('educationfield');
    }
};
