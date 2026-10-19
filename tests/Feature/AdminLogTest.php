<?php

use App\Models\Log;
use App\Queries\AdminLogQuery;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

/*
 * The activity log page and the notification bell, for the Admin only. Like every feature
 * test these run against hcsyria_db inside a rolled-back transaction; entries they make are
 * worded "zql ..." so the search finds only them.
 */

function logEntry(array $attributes = []): Log
{
    $when = $attributes['created_at'] ?? null;
    unset($attributes['created_at']);

    $log = Log::query()->create([
        'category' => 0,
        'project_id' => 7,
        'operation' => 'zql did a thing',
        'user_id' => staff('Viewer')->id,
        'seen' => 1,
        ...$attributes,
    ]);

    // created_at is not mass-assignable.
    $when === null || DB::table('logs')->where('id', $log->id)->update(['created_at' => $when]);

    return $log;
}

it('is closed to every role but the Admin', function (string $role) {
    $this->actingAs(staff($role))->get('/admin/log')->assertForbidden();
    Livewire::actingAs(staff($role))->test('pages::admin.log')->assertForbidden();
})->with(['Viewer', 'Publisher', 'English Editor', 'Photos Editor', 'English Checker']);

it('lists entries newest first with who, what, which item and the old and new value', function () {
    $old = logEntry(['operation' => 'zql old', 'created_at' => '2026-01-01 10:00:00']);
    $new = logEntry(['operation' => 'zql new', 'previous_data' => 'before', 'current_data' => 'after', 'created_at' => '2026-02-01 10:00:00']);

    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.log')->set('search', 'zql');

    $page->assertSeeInOrder(['zql new', 'zql old'])->assertSee('before')->assertSee('after')->assertSee('#7')->assertSee('Test Viewer');
    expect($old->id)->toBeLessThan($new->id);
});

it('filters by tab, grouping the newsletter\'s lists with its items, and searches by wording, item and name', function () {
    logEntry(['operation' => 'zql project', 'category' => 0]);
    logEntry(['operation' => 'zql tag', 'category' => 35, 'project_id' => 55]);
    logEntry(['operation' => 'zql subscriber', 'category' => 6]);
    logEntry(['operation' => 'zql user', 'category' => 12]);
    logEntry(['operation' => 'zql hidden visit', 'category' => 8]);

    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.log')->set('search', 'zql');
    $page->assertSee('zql project')->assertSee('zql tag')->assertSee('zql user')->assertDontSee('zql hidden visit');

    $page->set('tab', 'newsletter')->assertSee('zql tag')->assertSee('zql subscriber')->assertDontSee('zql project');
    $page->set('tab', 'all')->set('search', '#55')->assertSee('Nothing matches')->set('search', '55')->assertSee('zql tag');
    $page->set('search', 'Test Viewer')->assertSee('zql project');
    expect(AdminLogQuery::tabOf(35))->toBe('newsletter')->and(AdminLogQuery::tabOf(8))->toBeNull();
});

it('leaves out entries before 2022', function () {
    logEntry(['operation' => 'zql ancient', 'created_at' => '2019-05-05 10:00:00']);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.log')->set('search', 'zql ancient')->assertSee('Nothing matches');
});

it('shows the bell\'s unseen entries to the Admin, not the ones they made, and marks them all seen', function () {
    DB::table('logs')->update(['seen' => 1]);
    $admin = staff('Admin');
    logEntry(['operation' => 'zql from someone', 'seen' => 0]);
    logEntry(['operation' => 'zql mine', 'seen' => 0, 'user_id' => $admin->id]);
    logEntry(['operation' => 'zql student visit', 'seen' => 0, 'category' => 8]);

    $bell = Livewire::actingAs($admin)->test('admin.notifications');
    $bell->assertSee('1 new change')->assertSee('zql from someone')->assertDontSee('zql mine')->assertDontSee('zql student visit');

    $bell->call('markSeen')->assertSee('Nothing new');
    expect(DB::table('logs')->where('seen', 0)->count())->toBe(0);
});

it('shows no bell to anyone but the Admin, and refuses their mark-as-seen', function () {
    logEntry(['operation' => 'zql unseen', 'seen' => 0]);
    $viewer = staff('Viewer');

    Livewire::actingAs($viewer)->test('admin.notifications')->assertDontSee('zql unseen')->assertDontSee('Notifications')->call('markSeen')->assertForbidden();
    expect(Log::query()->where('operation', 'zql unseen')->value('seen'))->toBe(0);
});

it('offers the log in the menu to the Admin only', function () {
    $this->actingAs(staff('Admin'))->get('/admin/home')->assertSee('Activity log');
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertDontSee('Activity log');
});
