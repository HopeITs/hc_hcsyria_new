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
        Schema::table('educationfield', function (Blueprint $table) {
            $table->foreign(['educationdegree_id'], 'FK_educationfield1')->references(['id'])->on('educationdegree')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('educationfield', function (Blueprint $table) {
            $table->dropForeign('FK_educationfield1');
        });
    }
};
