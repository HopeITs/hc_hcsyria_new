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
        Schema::create('projects_french', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('project_id')->default(0)->index('fk_project2');
            $table->integer('MicroProject_ID');
            $table->longText('MPE_SuccessStory');
            $table->longText('MPE_Name');
            $table->longText('MPE_Suffering')->nullable();
            $table->mediumText('MPE_ExpSkill')->nullable();
            $table->char('translation_paid', 1)->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects_french');
    }
};
