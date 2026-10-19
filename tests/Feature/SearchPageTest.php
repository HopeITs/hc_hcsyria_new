<?php

use App\Models\Initiative;
use App\Models\InitiativeContent;
use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectContent;
use App\Models\ProjectType;
use App\Models\User;
use App\Models\UserPermission;
use App\Queries\ActivitySearch;
use App\Queries\MemberAccessQuery;
use App\Queries\ProjectSearch;
use Livewire\Livewire;

/*
 * The site-wide search, /search?search=: the filter bar over a group of
 * activities and, for those who may see them, a group of projects. The
 * records are the shared database's own; what a test adds is rolled back.
 */

function searchMember(bool $authorised = true): User
{
    $user = User::query()->create([
        'name' => 'Search Member',
        'email' => 'search-'.uniqid().'@example.test',
        'password' => 'correct horse battery',
        'active' => true,
    ]);

    if ($authorised) {
        $permission = Permission::query()->firstOrCreate(['name' => MemberAccessQuery::PERMISSION]);
        UserPermission::query()->create(['user_id' => $user->id, 'permission_id' => $permission->id]);
    }

    return $user;
}

/** An activity nobody else shares a word with, so a search finds it alone. */
function searchActivity(array $attributes = [], array $content = []): Initiative
{
    $template = Initiative::query()->where('online', '1')->whereNull('sub_what_category_id')->first();
    $activity = $template->replicate();
    $activity->forceFill(['online' => '1', 'place_id' => 3, 'video' => '0', ...$attributes])->save();

    InitiativeContent::query()->create([
        'initiative_id' => $activity->id,
        'title' => 'Zqxw gathering',
        'content' => 'A long enough account of the zqxw gathering held in town.',
        'description' => 'zqxw summary',
        'organization' => 'Zqxw team',
        'date_string' => '1 May 2020',
        ...$content,
    ]);

    return $activity;
}

function searchProject(array $attributes = []): Project
{
    $project = Project::unguarded(fn () => Project::query()->create([
        'MicroProject_ID' => 99998,
        'MPE_French' => 0,
        'projecttype_id' => ProjectType::query()->value('id'),
        'MPE_City' => 3,
        'MPE_Done' => 1,
        'MPE_Published' => 1,
        'MPE_PublishDate' => now(),
        'MPE_AllMoneyNeeded' => 0,
        'MPE_BeneficiaryName' => 'zqxw beneficiary',
        'MPE_PartnerName' => '0',
        'MPE_PartnerName2' => '0',
        'MPE_MoneyDonated' => 0,
        'MPE_Video' => '0',
        'MPE_Donor' => 1,
        'DOB' => 1980,
        'FamilyMembers' => 4,
        'latitude' => '36.2',
        'longtitude' => '37.1',
        ...$attributes,
    ]));

    ProjectContent::query()->create([
        'project_id' => $project->id,
        'MicroProject_ID' => $project->MicroProject_ID,
        'MPE_SuccessStory' => '0',
        'MPE_Name' => 'zqxw workshop',
        'MPE_Suffering' => 'A long enough story about a workshop that was lost and is being built again.',
        'MPE_ExpSkill' => '',
    ]);

    return $project;
}

it('goes back to where the visitor was when there is nothing to search for', function () {
    $this->from('/who-we-are')->get('/search')->assertRedirect('/who-we-are');
});

it('goes to the home address when there is nowhere to go back to', function () {
    $this->get('/search')->assertRedirect('/');
});

it('renders the results under the filter bar, with the count in the heading', function () {
    $this->get('/search?search=zqxw')
        ->assertOk()
        ->assertSee('Hope Center Search')
        ->assertSeeInOrder(['Search Results', 'City-', 'Donor-', 'Apply', 'Initiatives (0)'])
        ->assertSee("Sorry, we couldn't find any results", false)
        ->assertDontSee('Projects (');
});

it('finds an activity by its title, text, summary, organiser or date', function (string $term) {
    $activity = searchActivity();

    expect(ActivitySearch::make()->search($term)->builder()->pluck('id')->all())->toBe([$activity->id]);

    $this->get('/search?search='.urlencode($term))->assertSee('Zqxw gathering');
})->with(['zqxw gathering', 'ZQXW summary', 'long enough account', 'zqxw team', '1 may 2020']);

it('finds an activity by the town it was in, and filters by city', function () {
    $activity = searchActivity(['place_id' => 3], ['title' => 'Qwxz gathering', 'content' => 'qwxz', 'description' => 'qwxz', 'organization' => '0', 'date_string' => '0']);

    expect(ActivitySearch::make()->search('Qwxz')->inCity(3)->builder()->pluck('id')->all())->toBe([$activity->id])
        ->and(ActivitySearch::make()->search('Qwxz')->inCity(1)->builder()->count())->toBe(0);
});

it('never lists an activity that is offline, or one of the stories that have a page of their own', function () {
    searchActivity(['online' => '0'], ['title' => 'Zqxw hidden']);
    searchActivity(['sub_what_category_id' => 2], ['title' => 'Zqxw story']);

    expect(ActivitySearch::make()->search('zqxw')->builder()->count())->toBe(0);
});

it('does not let an offline activity in through its town or its funder, as legacy\'s ungrouped conditions did', function () {
    $offline = searchActivity(['online' => '0', 'place_id' => 3], ['title' => 'Qwxz offline']);
    $town = $offline->place->name;

    expect(ActivitySearch::make()->search($town)->builder()->pluck('id')->all())->not->toContain($offline->id);
});

it('matches a search literally: a percent sign or an underscore is only itself', function () {
    searchActivity([], ['title' => 'Zqxw 100% sure']);

    expect(ActivitySearch::make()->search('100%')->builder()->count())->toBeGreaterThanOrEqual(1)
        ->and(ActivitySearch::make()->search('%')->builder()->count())->toBeLessThan(ActivitySearch::make()->builder()->count());

    $this->get('/search?search='.urlencode("' OR 1=1 --"))->assertOk();
    $this->get('/search?search='.urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});

it('links each activity to its own page, under its own category', function () {
    $activity = searchActivity();
    $category = Str::slug(strtolower($activity->whatCategory->name));

    $this->get('/search?search=zqxw')->assertSee('/projects/'.$category.'/'.$activity->id.'-zqxw-gathering', false);
});

it('keeps only the filters it knows, from the address or from Apply', function () {
    Livewire::withQueryParams(['search' => 'hope', 'city' => '<x>', 'donor' => 'nobody', 'view' => 'bad'])
        ->test('pages::search')
        ->assertSet('city', '')
        ->assertSet('donor', '')
        ->assertSet('view', 'tiles');

    Livewire::withQueryParams(['search' => 'hope'])->test('pages::search')
        ->call('apply', 'homs', 'nobody', true, str_repeat('x', 300))
        ->assertSet('city', 'homs')
        ->assertSet('donor', '')
        ->assertSet('hasVideo', true)
        ->assertSet('search', str_repeat('x', 100));
});

it('counts what the filters leave beside what the words alone find, and grows by six', function () {
    $search = Livewire::withQueryParams(['search' => 'hope'])->test('pages::search');
    $total = ActivitySearch::make()->search('hope')->builder()->count();

    $search->assertSet('count', $total)->assertSet('total', $total)->assertSet('activityLimit', 6);

    $search->call('loadMore', 'activities')->assertSet('activityLimit', 12)->assertSet('projectLimit', 15);

    $search->call('apply', 'aleppo', '', false, 'hope')
        ->assertSet('activityLimit', 6)
        ->assertSet('total', $total)
        ->assertSet('count', ActivitySearch::make()->search('hope')->inCity(3)->builder()->count());
});

it('shows and searches the projects only for those who may see them', function () {
    searchProject();

    $this->get('/search?search=zqxw')->assertDontSee('Projects (');
    $this->actingAs(searchMember(false))->get('/search?search=zqxw')->assertDontSee('Projects (');

    $this->actingAs(searchMember())->get('/search?search=zqxw')
        ->assertOk()
        ->assertSeeInOrder(['Projects (1)', 'zqxw workshop', 'Initiatives (0)']);
});

it('counts no projects for a visitor, even for words that match one', function () {
    searchProject();

    Livewire::withQueryParams(['search' => 'zqxw'])->test('pages::search')->assertSet('projectCount', 0)->assertSet('count', 0);
    Livewire::actingAs(searchMember())->withQueryParams(['search' => 'zqxw'])->test('pages::search')->assertSet('projectCount', 1)->assertSet('count', 1);
});

it('also finds a project by its donor and its city, as legacy did, but not on the list\'s own box', function () {
    $project = searchProject();
    $city = $project->mPECity->name;

    expect(ProjectSearch::make()->search($city)->matchingDonorAndCity()->builder()->pluck('id')->all())->toContain($project->id)
        ->and(ProjectSearch::make()->search($city)->builder()->pluck('id')->all())->not->toContain($project->id);
});

it('finds a project by its number', function () {
    $project = searchProject(['MicroProject_ID' => 99997]);

    Livewire::actingAs(searchMember())->withQueryParams(['search' => '99997'])->test('pages::search')
        ->assertSet('projectCount', 1)
        ->assertSee('zqxw workshop');
});
