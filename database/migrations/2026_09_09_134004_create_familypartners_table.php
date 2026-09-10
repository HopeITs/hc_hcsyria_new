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
        Schema::create('familypartners', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->string('location', 200)->nullable();
            $table->string('location_latitude', 200);
            $table->string('location_longtitude', 200);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('familypartners');
    }
};
