<?php

use App\Models\Project;
use App\Queries\AdminStatisticsQuery;
use App\Support\Admin\Csv;
use App\Support\Admin\Reports;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
 * The dashboard's statistics, the Reports page and the CSV downloads. Like every feature
 * test these run against hcsyria_db inside a rolled-back transaction, so the figures are
 * compared with what the same query read before the test added its own projects.
 */

/*
 * The test database may hold no reference rows (cities, donors, types, categories), so each
 * test makes the ones its projects point to; they are rolled back with the test.
 */
beforeEach(function () {
    $this->cities = [
        DB::table('places')->insertGetId(['name' => 'zqa city one', 'is_micro' => 1, 'rank' => 1]),
        DB::table('places')->insertGetId(['name' => 'zqa city two', 'is_micro' => 1, 'rank' => 2]),
    ];

    if (! DB::table('donors')->where('is_micro', 1)->exists()) {
        DB::table('donors')->insert(['name' => 'zqa donor', 'code' => 'zqa', 'abb_name' => 'zqa', 'logo' => '', 'is_micro' => 1, 'rank' => 1]);
    }

    DB::table('project_types')->exists() || DB::table('project_types')->insert(['name' => 'zqa type']);

    if (! DB::table('project_sub_categories')->exists()) {
        $category = DB::table('project_categories')->insertGetId(['Name' => 'zqa category']);
        DB::table('project_sub_categories')->insert(['Name' => 'zqa sub', 'Category_ID' => $category]);
    }
});

/** A project in the first test city, so it points to a city that exists. */
function reportProject(array $attributes = []): Project
{
    return adminProject(['MPE_City' => test()->cities[0], ...$attributes]);
}

it('is open to the Admin and the Publisher only', function (string $role, int $status) {
    $this->actingAs(staff($role))->get('/admin/reports')->assertStatus($status);
    $this->actingAs(staff($role))->get('/admin/reports/funding-by-city.csv')->assertStatus($status);
})->with([['Admin', 200], ['Publisher', 200], ['Viewer', 403], ['English Editor', 403], ['Photos Editor', 403], ['English Checker', 403]]);

it('adds a project\'s money and people to the funding and beneficiary figures', function () {
    $before = new AdminStatisticsQuery(null);
    $money = $before->funding();
    $people = $before->beneficiaries();

    reportProject(['MPE_AllMoneyNeeded' => 1000, 'MPE_MoneyDonated' => 600, 'FamilyMembers' => 5]);
    reportProject(['MPE_AllMoneyNeeded' => 500, 'MPE_MoneyDonated' => 500, 'FamilyMembers' => 3, 'MPE_Done' => '2']); // cancelled: not counted

    $after = new AdminStatisticsQuery(null);

    expect($after->funding())
        ->projects->toBe($money['projects'] + 1)
        ->needed->toBe($money['needed'] + 1000)
        ->donated->toBe($money['donated'] + 600)
        ->and($after->beneficiaries())
        ->families->toBe($people['families'] + 1)
        ->people->toBe($people['people'] + 5);
});

it('counts only the cities the account may see, and the one chosen', function () {
    [$city, $other] = $this->cities;
    $before = (new AdminStatisticsQuery([$city]))->funding()['donated'];

    reportProject(['MPE_City' => $city, 'MPE_MoneyDonated' => 250]);
    reportProject(['MPE_City' => $other, 'MPE_MoneyDonated' => 9000]);

    expect((new AdminStatisticsQuery([$city]))->funding()['donated'])->toBe($before + 250)
        ->and((new AdminStatisticsQuery(null, $other))->fundingByCity()->firstWhere('name', 'zqa city two')->donated)->toBe(9000);
});

it('keeps the unknown birth year apart from the age bands', function () {
    $before = collect((new AdminStatisticsQuery(null))->ageBands())->keyBy('band');

    reportProject(['DOB' => 1980]);
    reportProject(['DOB' => (int) now()->year - 10]);

    $after = collect((new AdminStatisticsQuery(null))->ageBands())->keyBy('band');

    expect($after['Not recorded']['projects'])->toBe($before['Not recorded']['projects'] + 1)
        ->and($after['Under 18']['projects'])->toBe($before['Under 18']['projects'] + 1);
});

it('shows the funding tiles on the dashboard to those who see reports, and hides them from the rest', function () {
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.dashboard')->assertSee('Money donated')->assertSee('People reached')->assertSee('Open the reports');
    Livewire::actingAs(staff('Viewer'))->test('pages::admin.dashboard')->assertSee('Funded, not published')->assertDontSee('Money donated')->assertDontSee('Open the reports');
});

it('limits the city filter to the account\'s cities', function () {
    [$mine, $other] = $this->cities;
    $publisher = staff('Publisher', [$mine]);

    $this->actingAs($publisher)->get('/admin/reports/funding-by-city.csv?city='.$other)->assertForbidden();
    Livewire::actingAs($publisher)->test('pages::admin.reports')->set('city', (string) $other)->assertSet('city', '');
});

it('downloads a report as CSV with a byte order mark and the same headings as the page', function () {
    reportProject(['MPE_AllMoneyNeeded' => 100, 'MPE_MoneyDonated' => 40]);

    $response = $this->actingAs(staff('Admin'))->get('/admin/reports/funding-by-city.csv')->assertOk();

    expect($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->streamedContent())->toStartWith("\xEF\xBB\xBFCity,Projects,\"Needed (")
        ->and(Reports::build('funding-by-city', new AdminStatisticsQuery(null))['headings'][0])->toBe('City');
});

it('answers 404 for a report that does not exist', function () {
    $this->actingAs(staff('Admin'))->get('/admin/reports/nothing.csv')->assertNotFound();
});

it('quotes text that a spreadsheet would run as a formula', function () {
    expect(Csv::cell('=HYPERLINK("x")'))->toBe("'=HYPERLINK(\"x\")")
        ->and(Csv::cell('+1'))->toBe("'+1")
        ->and(Csv::cell('Aleppo'))->toBe('Aleppo')
        ->and(Csv::cell(-5))->toBe(-5);
});

it('exports the projects list with the page\'s filters, and the subscribers to those who may see them', function () {
    $project = reportProject(['MPE_BeneficiaryName' => 'zqa csv']);
    $number = $project->MicroProject_ID;

    $csv = $this->actingAs(staff('Viewer'))->get('/admin/projects.csv?search='.$number)->assertOk()->streamedContent();
    expect($csv)->toContain((string) $number)->toContain('Number,Name,City');

    DB::table('newsletter_subscribers')->insert(['mail' => 'zqa-csv@example.test', 'active' => 1, 'token' => 'x']);

    $list = $this->actingAs(staff('Publisher'))->get('/admin/news/subscribers.csv?search=zqa-csv')->assertOk()->streamedContent();
    expect($list)->toStartWith("\xEF\xBB\xBFAddress,Status,Subscribed\n")->toContain('zqa-csv@example.test,Active,');
    $this->actingAs(staff('Viewer'))->get('/admin/news/subscribers.csv')->assertForbidden();
});
