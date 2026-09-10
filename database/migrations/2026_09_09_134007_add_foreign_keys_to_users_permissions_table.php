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
        Schema::table('users_permissions', function (Blueprint $table) {
            $table->foreign(['user_id'], 'FK_users_permissions1')->references(['id'])->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['permission_id'], 'FK_users_permissions2')->references(['id'])->on('permissions')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users_permissions', function (Blueprint $table) {
            $table->dropForeign('FK_users_permissions1');
            $table->dropForeign('FK_users_permissions2');
        });
    }
};
