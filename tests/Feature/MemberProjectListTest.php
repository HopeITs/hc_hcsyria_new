<?php

use App\Models\Permission;
use App\Models\Project;
use App\Models\ProjectContent;
use App\Models\ProjectType;
use App\Models\User;
use App\Models\UserPermission;
use App\Queries\MemberAccessQuery;
use App\Queries\MemberProjectQuery;
use App\Queries\ProjectSearch;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

/*
 * What a signed-in member sees on /projects/micro-projects: the list, its
 * filters, the details window and the map's pins. member() is MemberLoginTest's.
 */

function plainMember(): User
{
    return User::query()->create([
        'name' => 'Test Member',
        'email' => 'list-'.uniqid().'@example.test',
        'password' => 'correct horse battery',
        'active' => true,
    ]);
}

function authorisedMember(): User
{
    $user = plainMember();
    $permission = Permission::query()->firstOrCreate(['name' => MemberAccessQuery::PERMISSION]);
    UserPermission::query()->create(['user_id' => $user->id, 'permission_id' => $permission->id]);

    return $user;
}

/** A listed project nobody else shares a name with, so a search finds it alone. */
function listedProject(array $attributes = [], array $content = []): Project
{
    $project = Project::unguarded(fn () => Project::query()->create([
        'MicroProject_ID' => 99999,
        'MPE_French' => 0,
        'projecttype_id' => ProjectType::query()->value('id'),
        'MPE_City' => 3,
        'MPE_Done' => 1,
        'MPE_Published' => 1,
        'MPE_PublishDate' => now(),
        'MPE_AllMoneyNeeded' => 0,
        'MPE_BeneficiaryName' => 'zqx beneficiary',
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
        'MPE_Name' => 'zqx workshop',
        'MPE_Suffering' => 'A long enough story about a workshop that was lost and is being built again.',
        'MPE_ExpSkill' => '',
        ...$content,
    ]);

    return $project;
}

it('shows an authorised member the list and the map in place of the gate', function () {
    $this->actingAs(authorisedMember())->get('/projects/micro-projects')
        ->assertOk()
        ->assertSeeInOrder(['Projects', 'Filter Result:', 'Load More', 'Map Locations', 'Our Partners'])
        ->assertDontSee('This section contains private information');
});

it('keeps the list from an account without the permission, and tells it so', function () {
    $this->actingAs(plainMember())->get('/projects/micro-projects')
        ->assertSee('Your account is not authorized to view this section.')
        ->assertDontSee('Filter Result:')
        ->assertDontSee('Map Locations');
});

it('lists the newest projects first, fifteen at a time', function () {
    $user = authorisedMember();
    $newest = listedProject(['MPE_PublishDate' => now()->addYear()], ['MPE_Name' => 'zqx newest']);

    $component = Livewire::actingAs($user)->test('micro-project-list')
        ->assertSee('zqx newest')
        ->assertSet('limit', 15)
        ->assertSee('Load More');

    expect($component->get('count'))->toBeGreaterThan(15);

    $component->call('loadMore')->assertSet('limit', 30);
    expect($newest->fresh())->not->toBeNull();
});

it('applies a draft of filters and starts the list again from the first page', function () {
    $project = listedProject();
    $other = listedProject(['MPE_City' => 1, 'MPE_BeneficiaryName' => 'zqx other'], ['MPE_Name' => 'zqx elsewhere']);

    Livewire::actingAs(authorisedMember())->test('micro-project-list')
        ->call('loadMore')
        ->call('apply', '', '', false, 'zqx')
        ->assertSet('limit', 15)
        ->assertSet('count', 2)
        ->assertSee('zqx workshop')
        ->assertSee('zqx elsewhere')
        ->call('apply', '3', '', false, 'zqx')
        ->assertSet('count', 1)
        ->assertSee('zqx workshop')
        ->assertDontSee('zqx elsewhere')
        ->assertDispatched('projects-filtered');

    expect($project->MPE_City)->toBe(3)->and($other->MPE_City)->toBe(1);
});

it('filters to projects with a video, a donor, or a number', function () {
    listedProject(['MPE_Video' => 'https://www.youtube.com/embed/abc123', 'MPE_Donor' => 2, 'MicroProject_ID' => 88888]);
    listedProject(['MicroProject_ID' => 88887], ['MPE_Name' => 'zqx silent']);

    Livewire::actingAs(authorisedMember())->test('micro-project-list')
        ->call('apply', '', '', true, 'zqx')
        ->assertSet('count', 1)
        ->assertDontSee('zqx silent')
        ->call('apply', '', '2', false, 'zqx')
        ->assertSet('count', 1)
        ->call('apply', '', '', false, '88887')
        ->assertSet('count', 1)
        ->assertSee('zqx silent');
});

it('says so when nothing matches', function () {
    Livewire::actingAs(authorisedMember())->test('micro-project-list')
        ->call('apply', '', '', false, 'no project is called this qqqq')
        ->assertSet('count', 0)
        ->assertSee('No Projects Found')
        ->assertDontSee('Load More');
});

it('switches between tiles and rows, and refuses any other view', function () {
    listedProject();

    Livewire::actingAs(authorisedMember())->test('micro-project-list')
        ->assertSet('view', 'tiles')
        ->assertSeeHtml('id="tiles-section"')
        ->call('show', 'grid')
        ->assertSeeHtml('id="grid-section"')
        ->call('show', 'something else')
        ->assertSet('view', 'tiles');
});

it('opens a project from a link to it, and not one that is not on the public list', function () {
    $project = listedProject(['MPE_Published' => 0], ['MPE_Name' => 'zqx hidden']);
    $listed = listedProject([], ['MPE_Name' => 'zqx linked']);

    Livewire::actingAs(authorisedMember())->withQueryParams(['projectDetailsModal' => $listed->id])
        ->test('micro-project-list')
        ->assertSet('linked', true)
        ->assertSee('Zqx linked')
        ->call('close')
        ->assertSet('details', '')
        ->assertDontSee('Zqx linked');

    Livewire::actingAs(authorisedMember())->withQueryParams(['projectDetailsModal' => $project->id])
        ->test('micro-project-list')
        ->assertDontSee('Zqx hidden');
});

it('opens the details of a tile with its facts', function () {
    $project = listedProject([], ['MPE_Name' => 'zqx clicked']);

    Livewire::actingAs(authorisedMember())->test('micro-project-list')
        ->call('apply', '', '', false, 'zqx clicked')
        ->call('open', $project->id)
        ->assertSet('details', (string) $project->id)
        ->assertSet('linked', false)
        ->assertSee('Zqx clicked')
        ->assertSee('Beneficiary:')
        ->assertSee('years old')
        ->assertSee('Family members:');
});

it('has a pin for each filtered project that has a position', function () {
    listedProject();
    listedProject(['latitude' => '0', 'longtitude' => '0'], ['MPE_Name' => 'zqx nowhere']);

    $pins = MemberProjectQuery::markers(ProjectSearch::make()->search('zqx'));

    expect($pins)->toHaveCount(1)
        ->and($pins[0])->toMatchArray(['name' => 'zqx workshop', 'beneficiary' => 'zqx beneficiary'])
        ->and((float) $pins[0]['lat'])->toBe(36.2);
});

it('offers names and beneficiaries to the search box, without the zero of a missing partner', function () {
    listedProject(['MPE_PartnerName' => 'zqx partner']);
    Cache::forget(MemberProjectQuery::SUGGESTIONS_KEY);

    $found = MemberProjectQuery::suggestions();

    expect($found['projects'])->toContain('zqx workshop')
        ->and($found['beneficiaries'])->toContain('zqx beneficiary', 'zqx partner')
        ->and($found['beneficiaries'])->not->toContain('0');
});

it('refuses anyone the gate does not let in, whatever they send it', function () {
    Livewire::test('micro-project-list')->assertForbidden();
    Livewire::actingAs(plainMember())->test('micro-project-list')->assertForbidden();
});

it('shows the member a badge with a way to sign out, and a visitor none', function () {
    $user = authorisedMember();

    $this->actingAs($user)->get('/projects/micro-projects')
        ->assertSee('Sign out')
        ->assertSee('Logged in')
        ->assertSeeHtml('action="/logout"');

    auth()->logout();

    $this->get('/projects/micro-projects')->assertDontSee('Logged in');
});

it('opens already filtered from the filters in the address', function () {
    listedProject();
    listedProject(['MPE_City' => 1, 'MPE_Video' => 'https://www.youtube.com/embed/abc'], ['MPE_Name' => 'zqx elsewhere']);

    Livewire::actingAs(authorisedMember())->withQueryParams(['search' => 'zqx', 'city' => '1', 'video' => '1', 'view' => 'grid'])
        ->test('micro-project-list')
        ->assertSet('search', 'zqx')
        ->assertSet('city', '1')
        ->assertSet('hasVideo', true)
        ->assertSet('view', 'grid')
        ->assertSet('count', 1)
        ->assertSee('Zqx elsewhere')
        ->assertDontSee('Zqx workshop');
});

it('ignores filters in the address that are not what Apply could send', function () {
    Livewire::actingAs(authorisedMember())->withQueryParams(['city' => 'abc', 'donor' => '1 or 1=1', 'view' => 'x'])
        ->test('micro-project-list')
        ->assertSet('city', '')
        ->assertSet('donor', '')
        ->assertSet('view', 'tiles');
});

it('starts the filter bar from the filters in the address', function () {
    $this->actingAs(authorisedMember())->get('/projects/micro-projects?city=3&search=zqx')
        ->assertSee('City-', false)
        ->assertSeeHtml('&quot;search&quot;:&quot;zqx&quot;');
});

it('keeps the suggestions for a while instead of reading every project each time', function () {
    Cache::forget(MemberProjectQuery::SUGGESTIONS_KEY);
    MemberProjectQuery::suggestions();
    listedProject([], ['MPE_Name' => 'zqx late arrival']);

    expect(MemberProjectQuery::suggestions()['projects'])->not->toContain('zqx late arrival');

    Cache::forget(MemberProjectQuery::SUGGESTIONS_KEY);

    expect(MemberProjectQuery::suggestions()['projects'])->toContain('zqx late arrival');
});

it('puts the open details window in the address and ignores one that is not a number', function () {
    $project = listedProject([], ['MPE_Name' => 'zqx addressed']);

    Livewire::actingAs(authorisedMember())->withQueryParams(['projectDetailsModal' => (string) $project->id])
        ->test('micro-project-list')
        ->assertSet('details', (string) $project->id)
        ->assertSee('Zqx addressed');

    Livewire::actingAs(authorisedMember())->withQueryParams(['projectDetailsModal' => 'abc'])
        ->test('micro-project-list')
        ->assertSet('details', '')
        ->assertSet('linked', false);
});
