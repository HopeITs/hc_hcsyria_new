<?php

use App\Models\PageView;

it('renders DISC with its sections in order', function () {
    $this->get('/projects/disc')
        ->assertOk()
        ->assertSee('DEVELOPMENT IMPACT<br>AND SUPPORT CENTER', false)
        ->assertSeeInOrder([
            'Project Brief', 'Essential Programs', 'Testemonies', 'Statistics', 'Activities', 'Our Partners', 'DISC Centers',
        ]);
});

it('offers the four programmes, English Skills first', function () {
    $this->get('/projects/disc')
        ->assertSeeInOrder(['English<br>Skills', 'Computer<br>Skills', 'Accompaniment', 'Employability<br>Skills'], false);
});

it('frames the brief video from YouTube\'s cookieless host', function () {
    $this->get('/projects/disc')
        ->assertSee('https://www.youtube-nocookie.com/embed/', false)
        ->assertDontSee('src="https://www.youtube.com/embed/', false);
});

it('opens the tab named in the address, else the one whose page is asked for, else Aleppo', function (string $query, string $tab) {
    $this->get('/projects/disc'.$query)->assertSee("activityTabs({ tab: '$tab' })", false);
})->with([
    'nothing' => ['', 'aleppo'],
    'tab' => ['?tab=damas', 'damas'],
    'page of Homs' => ['?ini_H=2', 'homs'],
    'nonsense tab' => ['?tab=<script>', 'aleppo'],
]);

it('lists the three DISC centres in the footer', function () {
    $this->get('/projects/disc')
        ->assertSeeInOrder(['info-adisc@hcsyria.org', 'info-hdisc@hcsyria.org', 'info-ddisc@hcsyria.org'])
        ->assertSee('tel:+963989162117', false)
        ->assertDontSee('tel:+963963989162117', false);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'disc')->value('views');

    $this->get('/projects/disc')->assertSessionHas('has_viewed_disc', true);

    expect((int) PageView::query()->where('page_name', 'disc')->value('views'))->toBe($before + 1);
});
