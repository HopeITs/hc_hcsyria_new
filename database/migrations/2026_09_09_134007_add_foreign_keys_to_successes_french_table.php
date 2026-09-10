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
        Schema::table('successes_french', function (Blueprint $table) {
            $table->foreign(['success_id'], 'FK_successes_french1')->references(['id'])->on('successes')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('successes_french', function (Blueprint $table) {
            $table->dropForeign('FK_successes_french1');
        });
    }
};
