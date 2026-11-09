<?php

use App\Models\PageView;
use App\Models\RootsOfHope;
use App\Queries\RootsOfHopeQuery;

it('renders the hero, the latest edition and the description', function () {
    $this->get('/rootsofhope')
        ->assertOk()
        ->assertSeeInOrder([
            'STORIES OF HOPE, FAITH &amp; HERITAGE',
            'roots of hope',
            'Published By',
            'Christian Hope Center',
            'Since 2013 (officially established in 2018)',
        ], false)
        ->assertSee('<title>Roots of Hope</title>', false)
        ->assertSee('/images/rootsofhope/Cover.png', false);
});

it('shows the last online edition large, with its preview link and PDF', function () {
    $latest = RootsOfHopeQuery::editions()->last();

    $this->get('/rootsofhope')
        ->assertOk()
        ->assertSee('class="roots-cover__edition" src="'.e($latest->image_path).'"', false)
        ->assertSee(route('roots.read', $latest->id), false)
        ->assertSee('<a href="'.e($latest->file_path).'" download>', false);
});

it('lists every online edition under All Editions, and none that is offline', function () {
    $offline = RootsOfHope::query()->create([
        'title' => 'Offline edition',
        'image_path' => '/images/rootsofhope/offline.jpg',
        'file_path' => '/files/rootsofhope/offline.pdf',
        'url' => 'https://example.test/offline',
        'online' => 0,
    ]);

    $page = $this->get('/rootsofhope')->assertOk()->assertSee('All Editions');

    RootsOfHopeQuery::editions()->each(fn (RootsOfHope $edition) => $page->assertSee(e($edition->title), false));
    $page->assertDontSee('Offline edition');
    expect(RootsOfHopeQuery::editions()->pluck('id'))->not->toContain($offline->id);
});

it('draws the page without editions rather than failing', function () {
    RootsOfHope::query()->update(['online' => 0]);

    $this->get('/rootsofhope')
        ->assertOk()
        ->assertSee('Since 2013')
        ->assertDontSee('All Editions')
        ->assertDontSee('Published By');
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'roots_of_hope')->value('views');

    $this->get('/rootsofhope')->assertSessionHas('has_viewed_roots_of_hope', true);

    expect((int) PageView::query()->where('page_name', 'roots_of_hope')->value('views'))->toBe($before + 1);
});

it('reads an online edition that has a PDF as a flipbook', function () {
    $edition = RootsOfHope::query()->create(['title' => 'Readable', 'image_path' => '0', 'file_path' => '/files/rootsofhope/readable.pdf', 'url' => '', 'online' => 1]);

    $this->get(route('roots.read', $edition->id))->assertOk()->assertSee('flipbook', false)->assertSee('/files/rootsofhope/readable.pdf', false);
});

it('does not read an offline edition or one without a PDF', function () {
    $offline = RootsOfHope::query()->create(['title' => 'Off', 'image_path' => '0', 'file_path' => '/files/rootsofhope/off.pdf', 'url' => '', 'online' => 0]);
    $noPdf = RootsOfHope::query()->create(['title' => 'No PDF', 'image_path' => '0', 'file_path' => '0', 'url' => '', 'online' => 1]);

    $this->get(route('roots.read', $offline->id))->assertNotFound();
    $this->get(route('roots.read', $noPdf->id))->assertNotFound();
});
