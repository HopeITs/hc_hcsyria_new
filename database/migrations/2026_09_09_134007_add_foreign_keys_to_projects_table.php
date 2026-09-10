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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreign(['SubCategory_ID'], 'FK_projects1')->references(['ID'])->on('subcategory')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['projecttype_id'], 'FK_projects2')->references(['id'])->on('projecttype')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['MPE_Donor'], 'FK_projects3')->references(['id'])->on('donors')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['MPE_City'], 'FK_projects4')->references(['id'])->on('places')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign('FK_projects1');
            $table->dropForeign('FK_projects2');
            $table->dropForeign('FK_projects3');
            $table->dropForeign('FK_projects4');
        });
    }
};
