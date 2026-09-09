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
        Schema::table('sub_what_categories', function (Blueprint $table) {
            $table->foreign(['what_category_id'], 'FK_subwhatcategory')->references(['id'])->on('what_categories')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_what_categories', function (Blueprint $table) {
            $table->dropForeign('FK_subwhatcategory');
        });
    }
};
