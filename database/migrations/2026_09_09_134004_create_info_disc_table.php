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
        Schema::create('info_disc', function (Blueprint $table) {
            $table->integer('id')->nullable();
            $table->string('city', 8)->nullable();
            $table->string('facebook', 37)->nullable();
            $table->bigInteger('phone')->nullable();
            $table->string('location', 204)->nullable();
            $table->string('email', 22)->nullable();
            $table->string('website', 33)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_disc');
    }
};
