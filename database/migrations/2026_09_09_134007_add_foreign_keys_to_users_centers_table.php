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
        Schema::table('users_centers', function (Blueprint $table) {
            $table->foreign(['user_id'], 'FK_users_centers1')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['studyzone_center_id'], 'FK_users_centers2')->references(['id'])->on('studyzonecenters')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_centers', function (Blueprint $table) {
            $table->dropForeign('FK_users_centers1');
            $table->dropForeign('FK_users_centers2');
        });
    }
};
