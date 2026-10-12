<?php

use App\Models\PageView;

it('renders for a visitor with its brief, statistics, gate and funders', function () {
    $this->get('/projects/micro-projects')
        ->assertOk()
        ->assertSee('<title>Micro Project</title>', false)
        ->assertSee('BE SUCCESSFUL &amp; INSPIRE OTHERS TO BE', false)
        ->assertSeeInOrder([
            'Project Brief', 'Statistics', 'Total Projects in', 'Project Sustainability rate',
            "Beneficiaries' Age Classification", 'Top 5 Project Categories',
            'This section contains private information', 'Log in', 'Our Partners', 'Micro Projects Centers',
        ]);
});

it('shows a tile for every city, Idlib reading SOON until it has a project', function () {
    $html = $this->get('/projects/micro-projects')->getContent();

    foreach (['Aleppo', 'Homs', 'Damascus', 'Latakia', 'Al Wadi', 'Idlib', 'Tartus'] as $city) {
        expect($html)->toContain($city);
    }
});

it('keeps the private project list out of the page', function () {
    $this->get('/projects/micro-projects')
        ->assertDontSee('syria_map')
        ->assertDontSee('Load More');
});

it('draws one ring per age band, without a charting library', function () {
    $html = $this->get('/projects/micro-projects')->getContent();

    expect(substr_count($html, 'radial-gauge__fill'))->toBeGreaterThan(0)
        ->and($html)->not->toContain('chart.js')
        ->and($html)->not->toContain('<canvas');
});

it('posts the sign-in to /login with a CSRF token', function () {
    $this->get('/projects/micro-projects')
        ->assertSee('action="/login"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false);
});

it('ends with the micro-projects centres footer', function () {
    $this->get('/projects/micro-projects')
        ->assertSeeInOrder(['Micro Projects Centers', '+963 993 310 227', '+963 950 018 430', '+963 989 162 108', '+963 950 018 484', 'Visit our Newsletter']);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'micro_projects')->value('views');

    $this->get('/projects/micro-projects')->assertSessionHas('has_viewed_micro_projects', true);

    expect((int) PageView::query()->where('page_name', 'micro_projects')->value('views'))->toBe($before + 1);
});
