<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * What staff changed on the public pages from the dashboard (Site content): one row
 * per slot of config/site_content.php that no longer shows its original. A slot with
 * no row shows what the page always showed; "Reset" deletes the row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('site_contents')) {
            return;
        }

        Schema::create('site_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('slot', 100)->unique();
            $table->text('value');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
