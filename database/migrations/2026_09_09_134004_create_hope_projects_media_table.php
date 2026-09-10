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
        Schema::create('hope_projects_media', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('project_id')->index('fk_project_media');
            $table->string('mediaType', 100);
            $table->string('mediaURL', 600);
            $table->integer('mediaOrder');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hope_projects_media');
    }
};
