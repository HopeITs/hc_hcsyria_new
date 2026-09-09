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
        Schema::create('studyzonecenters', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 100);
            $table->string('start_date', 11)->nullable();
            $table->string('work_hours', 100);
            $table->string('address', 200);
            $table->string('name_fr', 150);
            $table->string('work_hours_fr', 300);
            $table->string('address_fr', 250);
            $table->longText('location');
            $table->string('telephone', 50);
            $table->string('email', 100);
            $table->string('facebook', 200);
            $table->string('instagram', 200)->nullable();
            $table->integer('active')->default(1);
            $table->string('latitude', 300);
            $table->string('longtitude', 300);
            $table->integer('place_id')->default(0)->index('fk_sz_centers1');
            $table->integer('rank')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('studyzonecenters');
    }
};
