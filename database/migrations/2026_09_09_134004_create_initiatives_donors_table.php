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
        Schema::create('initiatives_donors', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('initiative_id')->index('fk_initiatives_donors1');
            $table->integer('donor_id')->index('fk_initiatives_donors2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives_donors');
    }
};
