<?php

use App\Models\Initiative;
use App\Queries\InitiativeTitleListQuery;
use Livewire\Livewire;

/*
 * The activities of Study Zone and DISC: tiles or rows, a tab for each town,
 * a search where the programme has one, and Load More six at a time. The
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

it('opens on tiles, the first town and six activities, with both layouts on the page', function () {
    $list = activities()
        ->assertSet('view', 'tiles')
        ->assertSet('town', '')
        ->assertSet('limit', 6)
        ->assertSeeHtml('activity-tile')
        ->assertSeeHtml('activity-card');

    expect(substr_count($list->html(), '<article'))->toBeLessThanOrEqual(6);
});

it('adds six more with Load More, and stops when there are no more', function () {
    $total = Initiative::query()->where('what_category_id', 7)->where('sub_what_category_id', 12)->whereIn('place_id', [1, 5])->where('online', '1')->count();

    $list = activities()->set('town', 'homs')->assertSee('Load More');

    for ($i = 1; $i < (int) ceil($total / 6); $i++) {
        $list->call('loadMore')->assertSet('limit', 6 * ($i + 1));
    }

    $list->assertDontSee('Load More');
});

it('changes town, starts the list again and keeps the legacy page parameter working', function () {
    activities()->call('loadMore')->assertSet('limit', 12)
        ->set('town', 'homs')->assertSet('limit', 6)->assertSet('town', 'homs');

    Livewire::withQueryParams(['ini_H' => '2'])->test('activity-list', [
        'category' => 7, 'subCategory' => 12, 'type' => 'study-zone', 'towns' => towns(), 'title' => 'Activities',
    ])->assertSet('town', 'homs');
});

it('takes only the towns it has, a short search and a known view from the address', function () {
    Livewire::withQueryParams(['tab' => '<script>', 'view' => 'x', 'search' => str_repeat('a', 300)])
        ->test('activity-list', [
            'category' => 7, 'subCategory' => 12, 'type' => 'study-zone', 'towns' => towns(), 'title' => 'Activities', 'searchable' => true,
        ])->assertSet('town', '')->assertSet('view', 'tiles')->assertSet('search', str_repeat('a', 100));
});

it('searches in place, and says so when nothing is found', function () {
    activities(['searchable' => true])
        ->set('search', 'qqqq no activity has this')
        ->assertSee('Sorry, we couldn\'t find any results')
        ->assertSee('Try different keywords.')
        ->assertDontSee('Load More');

    activities(['searchable' => true])
        ->set('search', "' OR 1=1 --")->assertOk()
        ->set('search', '%')->assertOk();
});

it('ignores a search where the programme has no search box', function () {
    activities()->set('search', 'anything')->assertSet('search', '');
});

it('has activity titles for the programme to suggest to the search box', function () {
    expect(InitiativeTitleListQuery::make()->forCategory(7)->titles()->count())->toBeGreaterThan(0);
});

it('draws the same list on the Study Zone page, searchable, and on DISC, without a search box', function () {
    $this->get('/projects/study-zone')->assertOk()->assertSee('search-box', false)->assertSee('view-switch', false);
    $this->get('/projects/disc')->assertOk()->assertDontSee('search-box__input', false)->assertSee('view-switch', false);
});
