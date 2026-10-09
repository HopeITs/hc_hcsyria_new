<?php

use App\Models\PageView;
use App\Queries\PartnerQuery;

it('renders on both of the addresses the legacy router gave it', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertSee('<title>Who We Are</title>', false)
        ->assertSee('SOME SEE A HOPELESS END')
        ->assertSee('OTHERS SEE AN ENDLESS HOPE')
        ->assertSee('Christian Hope Center');
})->with(['/who-we-are', '/who-we-are/partners']);

it('shows its sections in order', function () {
    $this->get('/who-we-are')->assertSeeInOrder([
        'Who We Are', 'our values', 'Our Mission', 'our vision and strategy', 'Our Partners',
    ]);
});

it('says the mission in three lines', function () {
    $this->get('/who-we-are')->assertSeeInOrder([
        'strengthen families and communities in Syria',
        'social services, and community-based programs',
        'and a better quality of life.',
    ]);
});

it('offers the four areas of support', function () {
    $this->get('/who-we-are')->assertSeeInOrder(['family support', 'youth support', 'children support', 'elderlies support']);
});

it('puts the partners in the given order, and any it was not told about last', function () {
    $ids = PartnerQuery::publicDonorsInOrder([3, 1])->pluck('id')->all();

    expect(array_slice($ids, 0, 2))->toBe([3, 1])
        ->and(array_slice($ids, 2))->not->toContain(3, 1);
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'who_we_are')->value('views');

    $this->get('/who-we-are')->assertSessionHas('has_viewed_who_we_are', true);

    expect((int) PageView::query()->where('page_name', 'who_we_are')->value('views'))->toBe($before + 1);
});
