<?php

use App\Models\PageView;

it('renders Study Zone with its sections in order', function () {
    $this->get('/projects/study-zone')
        ->assertOk()
        ->assertSee('Study Zone')
        ->assertSeeInOrder(['Project Brief', 'Find Your Zone', 'Testimonies', 'Activities', 'Our Spread', 'Our Partners']);
});

it('names the five towns on the activity tabs, never a translation key', function () {
    $this->get('/projects/study-zone')
        ->assertSeeInOrder(['Aleppo', 'Homs', 'Damascus', 'Latakia', 'Tartus'])
        ->assertDontSee('projects.latakia')
        ->assertDontSee('projects.tartus');
});

it('frames the brief video from YouTube\'s cookieless host', function () {
    $this->get('/projects/study-zone')
        ->assertSee('https://www.youtube-nocookie.com/embed/', false)
        ->assertDontSee('src="https://www.youtube.com/embed/', false);
});

it('leaves the statistics band out when the Study Zone database is not set up', function () {
    config(['database.connections.studyzone.database' => null]);

    $this->get('/projects/study-zone')->assertOk()->assertDontSee('Top 3 Education Fields');
});

it('searches activities without trusting the query', function () {
    $this->get('/projects/study-zone?search='.urlencode("' OR 1=1 --"))->assertOk();
    $this->get('/projects/study-zone?search=%25')->assertOk();
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'study_zone')->value('views');

    $this->get('/projects/study-zone')->assertSessionHas('has_viewed_study_zone', true);

    expect((int) PageView::query()->where('page_name', 'study_zone')->value('views'))->toBe($before + 1);
});
