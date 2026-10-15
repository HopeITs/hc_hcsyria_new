<?php

use App\Models\Initiative;
use App\Queries\InitiativeListQuery;
use App\Queries\InitiativeTitleListQuery;
use App\Queries\MemberProjectQuery;
use Livewire\Livewire;

/*
 * The activities of Study Zone and DISC: tiles or rows, the micro-projects filter bar
 * (city, donor, search, video), and Load More six at a time. The
 * records are the shared database's own.
 */

function towns(): array
{
    return [
        'aleppo' => ['label' => 'Aleppo', 'places' => [3], 'page' => 'ini_A', 'byDate' => true, 'empty' => ['No Activities Yet', null]],
        'homs' => ['label' => 'Homs', 'places' => [1, 5], 'page' => 'ini_H', 'byDate' => false, 'empty' => ['Sorry, we couldn\'t find any results', 'Try different keywords.']],
    ];
}

function activities(array $extra = [])
{
    return Livewire::test('activity-list', [
        'category' => 7, 'subCategory' => 12, 'type' => 'study-zone', 'towns' => towns(), 'title' => 'Activities', ...$extra,
    ]);
}

it('opens on tiles, every city and six activities, with both layouts on the page', function () {
    $list = activities()
        ->assertSet('view', 'tiles')
        ->assertSet('city', '')
        ->assertSet('limit', 6)
        ->assertSeeHtml('activity-tile')
        ->assertSeeHtml('activity-card')
        ->assertSeeHtml('project-filters');

    expect(substr_count($list->html(), '<article'))->toBeLessThanOrEqual(6);
});

it('counts what it lists against what the programme has', function () {
    $homs = Initiative::query()->where('what_category_id', 7)->where('sub_what_category_id', 12)->whereIn('place_id', [1, 5])->where('online', '1')->count();

    activities()->call('apply', 'homs', '', false, '')->assertSet('count', $homs)->assertSet('city', 'homs');
});

it('adds six more with Load More, and stops when there are no more', function () {
    $total = Initiative::query()->where('what_category_id', 7)->where('sub_what_category_id', 12)->whereIn('place_id', [1, 5])->where('online', '1')->count();

    $list = activities()->call('apply', 'homs', '', false, '')->assertSee('Load More');

    for ($i = 1; $i < (int) ceil($total / 6); $i++) {
        $list->call('loadMore')->assertSet('limit', 6 * ($i + 1));
    }

    $list->assertDontSee('Load More');
});

it('applies a city, starts the list again and keeps the legacy page parameter working', function () {
    activities()->call('loadMore')->assertSet('limit', 12)
        ->call('apply', 'homs', '', false, '')->assertSet('limit', 6)->assertSet('city', 'homs');

    Livewire::withQueryParams(['ini_H' => '2'])->test('activity-list', [
        'category' => 7, 'subCategory' => 12, 'type' => 'study-zone', 'towns' => towns(), 'title' => 'Activities',
    ])->assertSet('city', 'homs');
});

it('filters by a donor of the programme, and drops one it does not know', function () {
    $donor = MemberProjectQuery::donors()->whereIn('id', InitiativeListQuery::donorIds(7, 12))->first();

    $list = activities()->call('apply', '', $donor->slug, false, '')->assertSet('donor', $donor->slug);
    expect($list->get('count'))->toBeGreaterThan(0)->toBeLessThanOrEqual($list->get('total'));

    activities()->call('apply', '', 'no-such-donor', false, '')->assertSet('donor', '');
});

it('takes only the cities and donors it has, a short search and a known view from the address', function () {
    Livewire::withQueryParams(['city' => '<script>', 'donor' => 'x', 'view' => 'x', 'search' => str_repeat('a', 300)])
        ->test('activity-list', [
            'category' => 7, 'subCategory' => 12, 'type' => 'study-zone', 'towns' => towns(), 'title' => 'Activities',
        ])->assertSet('city', '')->assertSet('donor', '')->assertSet('view', 'tiles')->assertSet('search', str_repeat('a', 100));
});

it('searches in place, and says so when nothing is found', function () {
    activities()
        ->call('apply', '', '', false, 'qqqq no activity has this')
        ->assertSee("Sorry, we couldn't find any results")
        ->assertSee('Try different keywords.')
        ->assertDontSee('Load More');

    activities()
        ->call('apply', '', '', false, "' OR 1=1 --")->assertOk()
        ->call('apply', '', '', false, '%')->assertOk();
});

it('has activity titles for the programme to suggest to the search box', function () {
    expect(InitiativeTitleListQuery::make()->forCategory(7)->titles()->count())->toBeGreaterThan(0);
});

it('draws the same filter bar and list on the Study Zone page and on DISC', function () {
    foreach (['/projects/study-zone', '/projects/disc'] as $page) {
        $this->get($page)->assertOk()->assertSee('project-filters', false)->assertSee('search-box__input', false)->assertSee('view-switch', false);
    }
});
