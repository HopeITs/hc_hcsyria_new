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
        Schema::create('students', function (Blueprint $table) {
            $table->integer('id', true)->unique('id');
            $table->string('firstname', 100);
            $table->string('fathername', 100)->nullable();
            $table->string('lastname', 100);
            $table->string('nationalnumber', 50)->nullable();
            $table->year('DOB');
            $table->integer('gender_id')->index('fk_students4');
            $table->integer('religion_id')->default(0)->index('fk_students5');
            $table->string('email', 500)->nullable();
            $table->string('mobile', 100)->nullable();
            $table->string('whatsapp', 100)->nullable();
            $table->integer('is_employed');
            $table->integer('street_id')->nullable()->index('fk_student1');
            $table->integer('educationfield_id')->nullable()->index('fk_student2');
            $table->integer('educationlevel_id')->nullable()->index('fk_students3');
            $table->integer('is_student');
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->dateTime('updated_at')->useCurrent();

            $table->primary(['id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
