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
        Schema::create('rootsofhope', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('title', 50);
            $table->string('image_path', 100);
            $table->string('file_path', 100);
            $table->string('url', 100);
            $table->integer('online');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rootsofhope');
    }
};
