<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * What staff changed on the public pages from the dashboard (Site content): one row
 * per slot of config/site_content.php, and per rewritten line of text (slot
 * "text:<page or *>:<fingerprint>"), that no longer shows its original. Anything
 * with no row shows what the page always showed; "Reset" deletes the row.
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
            // For a rewritten line of text: the words it replaces, kept so a line that left the page can still be seen and reset.
            $table->text('original')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};
