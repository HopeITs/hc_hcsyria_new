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
        Schema::create('logs', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('operation', 200);
            $table->integer('project_id');
            $table->integer('user_id')->index('fk_logs1');
            $table->integer('category')->default(0);
            $table->integer('seen')->default(0);
            $table->longText('previous_data')->nullable();
            $table->longText('current_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
