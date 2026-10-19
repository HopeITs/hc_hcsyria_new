<?php

use App\Models\Image;
use App\Models\Initiative;
use App\Models\InitiativeContent;
use App\Models\Project;
use App\Models\ProjectContent;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPlace;
use App\Models\UserRole;
use App\Queries\AdminAccessQuery;
use Illuminate\Support\Facades\DB;

/*
 * Fixtures the admin tests share: a staff account with a role (and cities), a
 * micro-project that is ready to publish, and the matcher for an alert's type.
 * Loaded by tests/Pest.php.
 */

function staff(string $role, array $cities = []): User
{
    $user = User::query()->create([
        'name' => 'Test '.$role,
        'email' => 'staff-'.uniqid().'@example.test',
        'password' => 'correct horse battery',
        'active' => true,
    ]);

    UserRole::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->firstOrCreate(['name' => $role])->id]);

    foreach ($cities as $city) {
        UserPlace::query()->create(['user_id' => $user->id, 'place_id' => $city]);
    }

    AdminAccessQuery::flush();

    return $user;
}

/** A pending project that is ready to publish: English checked, a photo, a donor. */
function adminProject(array $attributes = [], int $photos = 1): Project
{
    $number = random_int(500000, 900000);

    $project = Project::unguarded(fn () => Project::query()->create([
        'MicroProject_ID' => $number,
        'MPE_City' => 3,
        'english_check' => 1,
        'MPE_French' => 0,
        'MPE_Done' => 1,
        'MPE_Published' => 0,
        'hidden' => 0,
        'MPE_PublishDate' => now(),
        'MPE_FundedDate' => '2026-05-01',
        'MPE_AllMoneyNeeded' => 0,
        'MPE_BeneficiaryName' => 'zqa beneficiary',
        'MPE_PartnerName' => '0',
        'MPE_PartnerName2' => '0',
        'MPE_MoneyDonated' => 0,
        'MPE_Video' => '0',
        'MPE_Donor' => DB::table('donors')->where('is_micro', 1)->value('id'),
        'DOB' => 1980,
        'FamilyMembers' => 4,
        'projecttype_id' => DB::table('projecttype')->value('id'),
        'WorkLoss' => 0,
        'Mobile' => '0',
        'latitude' => '36.2',
        'longtitude' => '37.1',
        'SubCategory_ID' => DB::table('subcategory')->value('ID'),
        ...$attributes,
    ]));

    ProjectContent::query()->create([
        'project_id' => $project->id,
        'MicroProject_ID' => $number,
        'MPE_SuccessStory' => '0',
        'MPE_Name' => 'zqa workshop '.$number,
        'MPE_Suffering' => '<p>A workshop that was lost and is being built again.</p>',
        'MPE_ExpSkill' => '',
    ]);

    for ($order = 1; $order <= $photos; $order++) {
        Image::query()->create(['MPI_Path' => "/images/projects/$number/$order.jpg", 'MicroProject_ID' => $number, 'project_id' => $project->id, 'image_order' => $order]);
    }

    return $project;
}

function alertOf(string $type): Closure
{
    return fn (string $event, array $params): bool => ($params['type'] ?? null) === $type;
}

/** An initiative of a category the dashboard manages (social activities), with its text and one donor. */
function adminInitiative(array $attributes = [], array $content = []): Initiative
{
    $initiative = Initiative::unguarded(fn () => Initiative::query()->create([
        'logo' => '0',
        'video' => '0',
        'rank' => 0,
        'english_check' => 1,
        'french_translation' => '0',
        'online' => 0,
        'hidden' => 0,
        'what_category_id' => 1,
        'who_category_id' => 1,
        'sub_what_category_id' => null,
        'place_id' => 0,
        ...$attributes,
    ]));

    InitiativeContent::query()->create([
        'initiative_id' => $initiative->id,
        'title' => 'zqi initiative '.$initiative->id,
        'organization' => 'zqi organization',
        'date_string' => '2026',
        'description' => 'A short description.',
        'content' => '<p>Some content for the initiative.</p>',
        ...$content,
    ]);

    DB::table('initiatives_donors')->insert(['initiative_id' => $initiative->id, 'donor_id' => DB::table('donors')->orderBy('id')->value('id')]);

    return $initiative;
}
