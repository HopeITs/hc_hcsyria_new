<?php

use App\Models\PageView;

it('renders Family to Family with its sections in order', function () {
    $this->get('/projects/family-to-family')
        ->assertOk()
        ->assertSeeInOrder(['Aid Program', 'Matthew 25:40', 'Project Brief', 'Statistics', 'Partners', 'Distribution Locations', 'Our Partners']);
});

it('breaks the verse into nine lines for wide screens and three for phones', function () {
    $html = $this->get('/projects/family-to-family')->getContent();

    expect($html)->toContain('answer and say')->toContain('Verily I say unto you, In as much as ye have done it unto');
});

it('shows the survey figures, three for wide screens and four for phones', function () {
    $this->get('/projects/family-to-family')
        ->assertSee('/images/initiatives/Family to Family/s3.svg', false)
        ->assertSee('/images/initiatives/Family to Family/statis-mobile/s4.svg', false);
});

it('pairs each partner with its own photograph, in order', function () {
    $html = $this->get('/projects/family-to-family')->getContent();

    expect($html)->toContain('/images/initiatives/Family to Family/p1.jpg')->toContain('/images/initiatives/Family to Family/p2.jpg');
});

it('draws the distribution map from the places table, one marker each', function () {
    $this->get('/projects/family-to-family')->assertSee('familyMap(', false);
});

it('frames the videos from YouTube\'s cookieless host', function () {
    $this->get('/projects/family-to-family')
        ->assertSee('https://www.youtube-nocookie.com/embed/ApyHAvgggcE', false)
        ->assertDontSee('src="https://www.youtube.com/embed/', false);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'family_to_family')->value('views');

    $this->get('/projects/family-to-family')->assertSessionHas('has_viewed_family_to_family', true);

    expect((int) PageView::query()->where('page_name', 'family_to_family')->value('views'))->toBe($before + 1);
});
