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
        Schema::create('initiatives_french', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('initiative_id')->unique('initiative_id');
            $table->string('title', 70);
            $table->string('organization', 70);
            $table->string('date_string', 100);
            $table->string('description', 500);
            $table->longText('content');
            $table->char('translation_paid', 1)->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives_french');
    }
};
