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
        Schema::table('initiatives_donors', function (Blueprint $table) {
            $table->foreign(['initiative_id'], 'FK_initiatives_donors1')->references(['id'])->on('initiatives')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['donor_id'], 'FK_initiatives_donors2')->references(['id'])->on('donors')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('initiatives_donors', function (Blueprint $table) {
            $table->dropForeign('FK_initiatives_donors1');
            $table->dropForeign('FK_initiatives_donors2');
        });
    }
};
