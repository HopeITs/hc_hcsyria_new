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
        Schema::create('student_entries', function (Blueprint $table) {
            $table->integer('id', true);
            $table->integer('user_id')->index('fk_student_entries3');
            $table->integer('student_id')->index('fk_student_entries1');
            $table->integer('center_id')->index('fk_student_entries2');
            $table->mediumText('note');
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_entries');
    }
};
