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
        Schema::table('users_places', function (Blueprint $table) {
            $table->foreign(['user_id'], 'FK_users_places1')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['place_id'], 'FK_users_places2')->references(['id'])->on('places')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_places', function (Blueprint $table) {
            $table->dropForeign('FK_users_places1');
            $table->dropForeign('FK_users_places2');
        });
    }
};
