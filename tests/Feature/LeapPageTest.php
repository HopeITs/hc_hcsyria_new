<?php

use App\Models\Initiative;
use App\Models\PageView;
use App\Queries\InitiativeListQuery;

it('renders LEAP with its sections in order', function () {
    $this->get('/projects/leap')
        ->assertOk()
        ->assertSeeInOrder(['LEADERSHIP, EMPOWERMENT &amp; ACHIEVEMENT', 'LEAP PROGRAM', 'Project Brief', 'Vision', 'Four Aspects of LEAP Program', 'Steps', 'Our Partners', 'LEAP Centers'], false);
});

it('opens on Personal Growth, with a button for each of the four aspects', function () {
    $this->get('/projects/leap')
        ->assertSeeInOrder(['aria-label="Personal Growth"', 'aria-label="Spiritual Development"', 'aria-label="Visionary Leadership"', 'aria-label="Technical Skills"'], false)
        ->assertSee('/images/home/LEAP/Categories/personal_hover.png', false)
        ->assertSee('Building the leader’s identity, confidence, and relational intelligence');
});

it('keeps the legacy page\'s run-together words before a button is used, and the spaced ones after', function () {
    $html = $this->get('/projects/leap')->getContent();

    expect($html)->toContain('role rotation:</span>Leaders engage')
        ->and($html)->toContain('Life coaching</span>is a professional')
        ->and(preg_match('/role rotation:<\/span>\s+(<!--.*?-->\s*)*Leaders/s', $html))->toBe(1);
});

it('shows the activities only once the programme has an online one', function () {
    $count = Initiative::query()->where('what_category_id', 19)->where('online', '1')->count();

    expect(InitiativeListQuery::hasAny(19))->toBe($count > 0)
        ->and(InitiativeListQuery::hasAny(7))->toBeTrue();

    $page = $this->get('/projects/leap')->assertOk();
    $count > 0 ? $page->assertSee('Activities') : $page->assertDontSee('Activities');
});

it('lists the activities when the programme has one', function () {
    $row = Initiative::query()->where('online', '1')->first();
    $copy = $row->replicate();
    $copy->what_category_id = 19;
    $copy->place_id = 3;
    $copy->save();

    $this->get('/projects/leap')->assertOk()->assertSeeInOrder(['Steps', 'Activities', 'Our Partners']);
});

it('wears the programme\'s own footer', function () {
    $this->get('/projects/leap')
        ->assertSee('LEAP Centers')
        ->assertSee('leap.aleppo@hcsyria.org')
        ->assertSee('leap.homs@hcsyria.org')
        ->assertSee('tel:+963930306688', false)
        ->assertSee('tel:+963950018463', false);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'leap')->value('views');

    $this->get('/projects/leap')->assertSessionHas('has_viewed_leap', true);

    expect((int) PageView::query()->where('page_name', 'leap')->value('views'))->toBe($before + 1);
});
