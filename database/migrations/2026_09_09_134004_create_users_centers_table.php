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
        Schema::create('users_centers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->index('fk_users_centers1');
            $table->integer('studyzone_center_id')->index('fk_users_centers2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_centers');
    }
};
