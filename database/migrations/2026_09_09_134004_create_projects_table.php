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
        Schema::create('projects', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedInteger('MicroProject_ID');
            $table->integer('MPE_City')->index('fk_projects4');
            $table->integer('english_check')->default(0);
            $table->char('MPE_French', 1);
            $table->char('MPE_Done', 1);
            $table->char('MPE_Published', 1)->default('0');
            $table->boolean('hidden')->default(false);
            $table->dateTime('MPE_PublishDate')->nullable();
            $table->date('MPE_FundedDate')->nullable();
            $table->integer('MPE_AllMoneyNeeded');
            $table->string('MPE_BeneficiaryName', 200);
            $table->string('MPE_PartnerName', 200);
            $table->string('MPE_PartnerName2', 200)->default('0');
            $table->integer('MPE_MoneyDonated');
            $table->longText('MPE_Video');
            $table->integer('MPE_Donor')->nullable()->index('fk_projects3');
            $table->char('NeedConversion', 1)->default('0');
            $table->year('DOB')->default('1980');
            $table->integer('FamilyMembers')->default(0);
            $table->integer('projecttype_id')->default(0)->index('fk_projects2');
            $table->integer('WorkLoss')->default(0);
            $table->string('Mobile', 50)->default('0');
            $table->string('latitude', 300)->default('0');
            $table->string('longtitude', 300)->default('0');
            $table->integer('SubCategory_ID')->nullable()->index('fk_projects1');

            $table->index(['MPE_Published', 'hidden'], 'projects_state_hidden_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
