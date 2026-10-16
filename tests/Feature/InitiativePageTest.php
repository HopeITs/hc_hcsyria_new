<?php

use App\Models\Initiative;
use Illuminate\Support\Str;

const RELIEF = '/projects/relief/289-multi-purpose-cash-aid-for-families-affected-by-the-earthquake';

it('renders an activity under its title with its text, photos and funders', function () {
    $this->get(RELIEF)
        ->assertOk()
        ->assertSee('<title>Multi Purpose Cash Aid for families affected by the earthquake</title>', false)
        ->assertSeeInOrder(['Relief', 'Multi Purpose Cash Aid for families affected by the earthquake', 'The situation in Aleppo remains challenging', 'Funded by:', 'Related posts'], false)
        ->assertSee('data-lightbox="initiative-289"', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('name="description"', false);
});

it('shows the big carousel only when there is more than one photo', function () {
    $this->get(RELIEF)->assertSee('initiative__carousel', false);

    $one = Initiative::query()->withCount('initiativeImages')->having('initiative_images_count', 1)
        ->where('online', '1')->whereHas('content')->whereHas('whatCategory')
        ->where(fn ($query) => $query->where('sub_what_category_id', '<>', 2)->orWhereNull('sub_what_category_id'))->first();

    if ($one === null) {
        $this->markTestSkipped('No activity with exactly one photo in the database.');
    }

    $slug = Str::slug(strtolower($one->whatCategory->name));
    $title = Str::slug(strtolower($one->content->title));

    $this->get("/projects/$slug/{$one->id}-$title")->assertOk()->assertDontSee('initiative__carousel', false);
});

it('draws a programme\'s title line and logo in its hero', function (string $url, string $title, string $logo) {
    $this->get($url)->assertOk()->assertSee($title, false)->assertSee("/images/logos/$logo.svg", false);
})->with([
    ['/projects/family-to-family/41-armenian-catholic', 'Aid Program', 'Family-to-Family'],
    ['/projects/study-zone/440-discover-your-english-path', 'Youth Program', 'study'],
    ['/projects/school-to-school/210-raising-awareness-for-a-heathier-generation', 'Educational Support', 'school'],
    ['/projects/children-university/434-shaping-bright-futures', 'Educational Program', 'Children-Uni'],
]);

it('names DISC by its two-line text', function () {
    $this->get('/projects/disc/302-extracurricular-activity')
        ->assertOk()
        ->assertSee('DEVELOPMENT IMPACT<br>AND SUPPORT CENTER', false)
        ->assertSee('accent-disc', false);
});

it('follows a rehabilitation project with its before-and-after sliders', function () {
    $this->get('/projects/rehabilitation/33-st-vartan-garden-and-playgrounds')
        ->assertOk()
        ->assertSee('The Result of Reconstruction')
        ->assertSee('before-after__handle', false);

    $this->get(RELIEF)->assertDontSee('The Result of Reconstruction');
});

it('points to three other activities of the same category', function () {
    $html = $this->get(RELIEF)->getContent();

    // Three tiles, with the two before and the two after them the loop slides through.
    expect(substr_count($html, 'class="related-carousel__item"'))->toBe(7)
        ->and(Str::between($html, 'related-carousel__stage', 'related-carousel__nav'))->not->toContain('/projects/relief/289-');
});

it('answers the designed 404 unless the whole address is right', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    'another title' => ['/projects/relief/289-something-else'],
    'another category' => ['/projects/disc/289-multi-purpose-cash-aid-for-families-affected-by-the-earthquake'],
    'no such activity' => ['/projects/relief/99999999-x'],
    'not a number' => ['/projects/relief/abc-x'],
    'a quote in the title' => ['/projects/relief/289-x%22%20OR%20%221%22=%221'],
    'a wildcard' => ['/projects/%25/289-%25'],
]);

it('does not show a Family to Family story, which has its own page', function () {
    $story = Initiative::query()->where('sub_what_category_id', 2)->whereHas('content')->whereHas('whatCategory')->first();

    if ($story === null) {
        $this->markTestSkipped('No story in the database.');
    }

    $slug = Str::slug(strtolower($story->whatCategory->name));
    $title = Str::slug(strtolower($story->content->title));

    $this->get("/projects/$slug/{$story->id}-$title")->assertNotFound();
});
