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
        Schema::create('users_places', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->index('fk_users_places1');
            $table->integer('place_id')->index('fk_users_places2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_places');
    }
};
