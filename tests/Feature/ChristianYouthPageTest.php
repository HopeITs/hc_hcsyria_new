<?php

use App\Models\Cover;
use App\Models\PageView;
use App\Queries\CoverQuery;

it('renders the page with its sections in order', function () {
    $this->get('/christianyouth')
        ->assertOk()
        ->assertSeeInOrder([
            'The First Gathering Of The Syrian Christian Youth Leaders',
            'For years young Syrian Christians have been suffering in silence',
            'The Syrian Christian Young Leaders Conference is held',
            'Together Today, for a',
            'Work Plan and Agenda:',
            'The Main Objectives:',
            'Participants',
            'More',
            'Damascus/Damascus villages',
        ], false)
        ->assertSee('<title>Christian Youth</title>', false);
});

it('lists the two plans and the four objectives', function () {
    $html = $this->get('/christianyouth')->getContent();

    expect(substr_count($html, 'class="reveal youth-list'))->toBe(2)
        ->and($html)->toContain('Preceding the conference, three preparatory meetings')
        ->and($html)->toContain('Put forward recommendations which the participants aspire to achieve');
});

it('shows the cover photos of the page, in their order', function () {
    $covers = CoverQuery::images(15);

    $page = $this->get('/christianyouth')->assertOk();

    $covers->each(fn (Cover $cover) => $page->assertSee($cover->image_path, false));
});

it('slides the four photos beside each list', function () {
    $html = $this->get('/christianyouth')->getContent();

    foreach (range(1, 8) as $i) {
        expect($html)->toContain("/images/youth/new/$i.jpg");
    }
});

it('frames the video from YouTube\'s cookieless host', function () {
    $this->get('/christianyouth')
        ->assertSee('https://www.youtube-nocookie.com/embed/gF8AzxiWRos', false)
        ->assertDontSee('src="https://www.youtube.com/embed/', false);
});

it('hangs the emblem over the cover', function () {
    $this->get('/christianyouth')->assertSee('/images/logos/CYG.svg', false);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'christian_youth')->value('views');

    $this->get('/christianyouth')->assertSessionHas('has_viewed_christian_youth', true);

    expect((int) PageView::query()->where('page_name', 'christian_youth')->value('views'))->toBe($before + 1);
});
