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
        Schema::create('projects_english', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('project_id')->default(0)->index('fk_project');
            $table->integer('MicroProject_ID');
            $table->longText('MPE_SuccessStory');
            $table->longText('MPE_Name');
            $table->longText('MPE_Suffering')->nullable();
            $table->mediumText('MPE_ExpSkill')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_english');
    }
};
