<?php

use App\Models\PageView;

const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
const IPAD = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
const DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0 Safari/537.36';

it('renders the home page with its eight programmes, in order', function () {
    $this->get('/home')
        ->assertOk()
        ->assertSeeInOrder(['LEAP', 'Rehabilitation', 'Children University', 'School to School', 'Family To Family', 'Micro Project', 'DISC', 'Study Zone'])
        ->assertSee('Welcome to <span>HOPE CENTER</span>', false)
        ->assertSee('why it matters?')
        ->assertSee('our programs');
});

it('sets the page title and head tags the legacy page had', function () {
    $this->get('/home')
        ->assertSee('<title>Home</title>', false)
        ->assertSee('<meta name="keywords" content="aleppo syria, hope, center, children, university">', false)
        ->assertSee('<link rel="alternate" hreflang="x-default" href="https://www.hcsyria.org">', false);
});

it('links every programme to its page', function () {
    $this->get('/home')->assertSeeInOrder([
        'href="/projects/leap"', 'href="/projects/rehabilitation"', 'href="/projects/childrenuni"',
        'href="/projects/school-to-school"', 'href="/projects/family-to-family"',
        'href="/projects/micro-projects"', 'href="/projects/disc"', 'href="/projects/study-zone"',
    ], false);
});

it('shows the desktop cover to desktops and tablets, the phone cover to phones', function (string $agent, string $cover, string $other) {
    $this->withHeader('User-Agent', $agent)->get('/home')
        ->assertSee($cover)
        ->assertDontSee($other);
})->with([
    'desktop' => [DESKTOP, 'roots-cover-desktop.jpg', 'roots-cover-mobile.jpg'],
    'tablet' => [IPAD, 'roots-cover-desktop.jpg', 'roots-cover-mobile.jpg'],
    'phone' => [IPHONE, 'roots-cover-mobile.jpg', 'roots-cover-desktop.jpg'],
]);

it('starts with the loading screen and a transparent navbar over the cover', function () {
    $this->get('/home')
        ->assertSee('class="preloader"', false)
        ->assertSee('class="site-nav site-nav--overlay"', false)
        ->assertSee('/images/logos/hcreverse.svg', false);
});

it('leaves pages that do not ask for one without a loading screen or overlay', function () {
    $this->get('/no-such-page')->assertDontSee('class="preloader"', false)->assertSee('class="site-nav"', false);
});

describe('page view counting', function () {
    it('counts nothing unless switched on, so staging cannot touch the live counters', function () {
        config(['hcsyria.count_page_views' => false]);
        $before = PageView::query()->where('page_name', 'home')->value('views');

        $this->get('/home')->assertOk()->assertSessionMissing('has_viewed_home');

        expect(PageView::query()->where('page_name', 'home')->value('views'))->toBe($before);
    });

    it('counts a visit once per session', function () {
        config(['hcsyria.count_page_views' => true]);
        $before = (int) PageView::query()->where('page_name', 'home')->value('views');

        $this->get('/home')->assertSessionHas('has_viewed_home', true);
        expect((int) PageView::query()->where('page_name', 'home')->value('views'))->toBe($before + 1);

        $this->withSession(['has_viewed_home' => true])->get('/home');
        expect((int) PageView::query()->where('page_name', 'home')->value('views'))->toBe($before + 1);
    });
});
