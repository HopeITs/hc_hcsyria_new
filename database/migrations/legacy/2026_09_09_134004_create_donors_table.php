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
        Schema::create('donors', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 50);
            $table->string('code', 10);
            $table->string('abb_name', 50);
            $table->string('logo', 50);
            $table->integer('is_micro')->default(0);
            $table->integer('rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donors');
    }
};
