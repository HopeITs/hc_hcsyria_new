<?php

use App\Models\InitiativeImageRehabilitation;
use App\Models\PageView;

it('renders each category under its name with its description', function (string $slug, string $title, string $text) {
    $this->get("/projects/$slug")
        ->assertOk()
        ->assertSee("<title>$title</title>", false)
        ->assertSeeInOrder(['SOME SEE A HOPELESS END', $title, $text, 'Read More']);
})->with([
    ['social-activities', 'Social activities', 'The Christian organizations in Aleppo'],
    ['rehabilitation', 'Rehabilitation', 'The Rehabilitation work in the post-war period'],
    ['relief', 'Relief', 'Our relief programs are designed'],
    ['earthquake-response', 'Earthquake Response', 'Following the 7.8 magnitude earthquake'],
]);

it('lists only that category\'s online activities, each linking to its own page', function () {
    $html = $this->get('/projects/earthquake-response')->getContent();

    expect(substr_count($html, 'class="activity-card"'))->toBe(4)
        ->and($html)->toContain('href="/projects/earthquake-response/297-food-baskets-distribution"');
});

it('opens Relief with the Family to Family programme and alternates the other way round', function () {
    $this->get('/projects/relief')
        ->assertSeeInOrder([
            'Cash aid distributions - Aleppo (Family to Family)',
            'Preview Program',
            'Multi Purpose Cash Aid for families affected by the earthquake',
        ])
        ->assertSee('href="/projects/family-to-family"', false)
        ->assertSee('/images/initiatives/Family to Family/f4.jpg', false);

    $this->get('/projects/social-activities')->assertDontSee('Preview Program');
});

it('follows a rehabilitation project\'s photos with its before-and-after pairs', function () {
    $html = $this->get('/projects/rehabilitation')->getContent();

    foreach (InitiativeImageRehabilitation::query()->limit(3)->get() as $pair) {
        expect($html)->toContain($pair->path_1)->toContain($pair->path_2);
    }
});

it('answers the designed 404 for a category with no page, and for nonsense', function (string $slug) {
    $this->get("/projects/$slug")->assertNotFound();
})->with(['development', 'homework-room', 'hope-from-syria', 'nonsense', 'x%22%20OR%20%221%22=%221', '%25']);

it('counts a visit once per session under the name legacy used', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'social_activities_all')->value('views');

    $this->get('/projects/social-activities')->assertSessionHas('has_viewed_social_activities_all', true);

    expect((int) PageView::query()->where('page_name', 'social_activities_all')->value('views'))->toBe($before + 1);
});
