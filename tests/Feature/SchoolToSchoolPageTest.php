<?php

use App\Models\PageView;

it('renders School to School with its sections in order', function () {
    $this->get('/projects/school-to-school')
        ->assertOk()
        ->assertSeeInOrder(['Educational Support', 'Project Brief', 'Students Mailbox', 'Statistics', 'Activities', 'Our Partners']);
});

it('shows the three letters of the mailbox', function () {
    $this->get('/projects/school-to-school')
        ->assertSeeInOrder([
            '/images/initiatives/School to School/letter1.jpg',
            '/images/initiatives/School to School/letter2.jpg',
            '/images/initiatives/School to School/letter3.jpg',
        ], false);
});

it('draws the statistics scene with a card for each figure', function () {
    $this->get('/projects/school-to-school')
        ->assertSee('/images/home/school-statistics.svg', false)
        ->assertSee('--stat-to: 4098', false)
        ->assertSee('--stat-to: 14', false)
        ->assertSee('--stat-to: 44', false);
});

it('frames the brief video from YouTube\'s cookieless host', function () {
    $this->get('/projects/school-to-school')
        ->assertSee('https://www.youtube-nocookie.com/embed/', false)
        ->assertDontSee('src="https://www.youtube.com/embed/', false);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'school_to_school')->value('views');

    $this->get('/projects/school-to-school')->assertSessionHas('has_viewed_school_to_school', true);

    expect((int) PageView::query()->where('page_name', 'school_to_school')->value('views'))->toBe($before + 1);
});

it('searches the activities and says so when nothing matches', function () {
    $this->get('/projects/school-to-school?search=zzzzqqqq')
        ->assertOk()
        ->assertSee("Sorry, we couldn't find any results", false);
    $this->get('/projects/school-to-school?search=%25')->assertOk();
});
