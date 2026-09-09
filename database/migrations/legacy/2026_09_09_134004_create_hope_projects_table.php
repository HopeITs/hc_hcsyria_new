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
        Schema::create('hope_projects', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('what_category_id');
            $table->string('name', 300);
            $table->string('name_fr', 500);
            $table->string('description', 1500);
            $table->string('description_fr', 700);
            $table->string('field', 100);
            $table->string('field_fr', 200);
            $table->string('logo', 600);
            $table->string('mediaType', 100);
            $table->text('mediaURL');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hope_projects');
    }
};
