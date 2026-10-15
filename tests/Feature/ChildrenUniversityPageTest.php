<?php

use App\Models\PageView;

it('renders Children University with its sections in order', function () {
    $this->get('/projects/childrenuni')
        ->assertOk()
        ->assertSeeInOrder(['Educational Program', 'Children University', 'Project Brief', 'Informatics', 'Activities', 'Our Partners']);
});

it('shows a button for each of the five subjects', function () {
    $this->get('/projects/childrenuni')
        ->assertSeeInOrder([
            'aria-label="Informatics"',
            'aria-label="Medicine"',
            'aria-label="Engineering"',
            'aria-label="Science"',
            'aria-label="Media"',
        ], false);
});

it('offers an Aleppo and a Homs tab', function () {
    $this->get('/projects/childrenuni')->assertSeeInOrder(['Aleppo', 'Homs']);
});

it('opens the tab named in the address, else the one whose page is asked for, else Aleppo', function (string $query, string $tab) {
    $this->get('/projects/childrenuni'.$query)->assertSee("activityTabs({ tab: '$tab' })", false);
})->with([
    'default' => ['', 'aleppo'],
    'tab' => ['?tab=homs', 'homs'],
    'page of Homs' => ['?ini_H=2', 'homs'],
    'nonsense tab' => ['?tab=<script>', 'aleppo'],
]);

it('wears the programme\'s own footer', function () {
    $this->get('/projects/childrenuni')
        ->assertSee('Children University Centers')
        ->assertSee('info-cualeppo@hcsyria.org')
        ->assertSee('info-cuhoms@hcsyria.org');
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'children_uni')->value('views');

    $this->get('/projects/childrenuni')->assertSessionHas('has_viewed_children_uni', true);

    expect((int) PageView::query()->where('page_name', 'children_uni')->value('views'))->toBe($before + 1);
});
