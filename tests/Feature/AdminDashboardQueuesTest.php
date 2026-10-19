<?php

use App\Queries\AdminInitiativeIndexQuery;
use App\Queries\AdminNewsIndexQuery;
use App\Queries\AdminSuccessIndexQuery;
use Livewire\Livewire;

/*
 * The dashboard's queues for initiatives, successes and newsletter items, beside the micro
 * projects' queue: what waits for this person in each section, by their role, with links to
 * the items. Like every feature test these run against hcsyria_db inside a rolled-back
 * transaction.
 */

function dashboardQueues(string $role)
{
    $page = Livewire::actingAs(staff($role))->test('pages::admin.dashboard');

    return [$page, collect($page->viewData('sectionQueues'))->keyBy('section')];
}

it('shows each section\'s pending items to whoever publishes, with the same count as the list', function () {
    adminInitiative();
    adminSuccess();
    adminNews();

    [$page, $queues] = dashboardQueues('Publisher');

    expect($queues->keys()->all())->toBe(['admin.initiatives', 'admin.successes', 'admin.news'])
        ->and($queues['admin.initiatives'])->tab->toBe('pending')->count->toBe(AdminInitiativeIndexQuery::make()->counts()['pending'])
        ->and($queues['admin.successes']['count'])->toBe(AdminSuccessIndexQuery::make()->counts()['pending'])
        ->and($queues['admin.news']['count'])->toBe(AdminNewsIndexQuery::make()->counts()['pending'])
        ->and($queues['admin.news']['count'])->toBeGreaterThan(0);
    $page->assertSee('Initiatives waiting to be published')->assertSee('Successes waiting to be published')->assertSee('Newsletter items waiting to be published');
});

it('shows the items waiting for the English check to whoever checks it', function () {
    adminNews(['english_check' => 0]);

    [$page, $queues] = dashboardQueues('English Checker');

    expect($queues['admin.news'])->tab->toBe('english')->count->toBe(AdminNewsIndexQuery::make()->counts()['english']);
    $page->assertSee('Newsletter items waiting for the English check');
});

it('shows the latest items to those who neither publish nor check English', function (string $role) {
    [$page, $queues] = dashboardQueues($role);

    expect($queues->pluck('tab')->unique()->all())->toBe(['all']);
    $page->assertSee('Latest initiatives')->assertSee('Latest successes')->assertSee('Latest newsletter items');
})->with(['Viewer', 'English Editor', 'Photos Editor']);

it('links each row to the item\'s own page, and the panel to the list on the same tab', function () {
    adminNews();

    $page = Livewire::actingAs(staff('Viewer'))->test('pages::admin.dashboard');

    expect(collect($page->viewData('sectionQueues'))->firstWhere('section', 'admin.news')['rows'])->not->toBeEmpty();
    $page->assertSee(route('admin.news.index', ['tab' => 'all']), false)->assertSee(route('admin.initiatives.show', AdminInitiativeIndexQuery::make()->tab('all')->paginate(1)->items()[0]->id), false);
});

it('shows no queue for a section the account cannot open', function () {
    [, $queues] = dashboardQueues('Nobody');

    expect($queues)->toBeEmpty();
});
