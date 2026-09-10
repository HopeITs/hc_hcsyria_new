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
        Schema::create('youthprofiles', function (Blueprint $table) {
            $table->string('id', 3)->nullable();
            $table->string('fullname_ar', 24)->nullable();
            $table->string('firstname', 14)->nullable();
            $table->string('fathername', 12)->nullable();
            $table->string('lastname', 12)->nullable();
            $table->string('link', 20)->nullable();
            $table->string('dob', 10)->nullable();
            $table->string('city', 11)->nullable();
            $table->string('position', 10)->nullable();
            $table->string('email', 29)->nullable();
            $table->bigInteger('mobile')->nullable();
            $table->bigInteger('whatsapp')->nullable();
            $table->string('facebook', 30)->nullable();
            $table->string('image_path', 26)->nullable();
            $table->string('gender', 6)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youthprofiles');
    }
};
