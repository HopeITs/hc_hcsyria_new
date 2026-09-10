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
        Schema::table('subcategory', function (Blueprint $table) {
            $table->foreign(['Category_ID'], 'FK_Category_SubCategory')->references(['ID'])->on('category')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcategory', function (Blueprint $table) {
            $table->dropForeign('FK_Category_SubCategory');
        });
    }
};
