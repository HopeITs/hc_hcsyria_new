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
        Schema::create('sub_what_categories', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('what_category_id')->index('fk_subwhatcategory');
            $table->string('name', 30)->unique('name');
            $table->string('french_name', 30);
            $table->longText('description');
            $table->longText('french_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_what_categories');
    }
};
