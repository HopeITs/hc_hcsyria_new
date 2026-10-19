<?php

use App\Enums\ContentKind;
use App\Enums\ContentTransition;
use App\Models\News;
use App\Models\NewsContent;
use App\Support\Admin\DocumentFiles;
use App\Support\Admin\PhotoFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

/*
 * The staff dashboard's newsletter items: who may open what, the list, the state
 * changes (the shared code, plus legacy's one-Important-item rule), and the create /
 * edit / delete forms with tags and a PDF attachment. Like every feature test these
 * run against hcsyria_db inside a rolled-back transaction and use items they create
 * themselves; files go to a throwaway folder standing in for MEDIA_ROOT.
 */

beforeEach(function () {
    $this->media = sys_get_temp_dir().'/hc-media-'.uniqid();
    File::ensureDirectoryExists($this->media.'/files');
    config(['hcsyria.media_root' => $this->media]);
});

afterEach(fn () => File::deleteDirectory($this->media));

function newsForm(array $overrides = []): array
{
    return [
        'form.type' => '0',
        'form.place_id' => '3',
        'form.date' => '2026-10-01',
        'form.title' => 'zqn new item',
        'form.description' => 'A short description.',
        'form.content' => '<p>A long enough text for a news item.</p><script>x</script>',
        'form.tags' => [(string) DB::table('tags')->orderBy('id')->value('id')],
        ...$overrides,
    ];
}

function newsPage(string $role = 'Admin', array $params = [])
{
    return Livewire::actingAs(staff($role))->test('pages::admin.news.form', $params);
}

function pdf(string $name = 'report.pdf', ?string $bytes = null): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $bytes ?? "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF");
}

// ----------------------------------------------------------- who gets in, the list

it('lets every role open the list and an item', function (string $role) {
    $news = adminNews();
    $user = staff($role);

    $this->actingAs($user)->get('/admin/news')->assertOk()->assertSee('Newsletter');
    $this->actingAs($user)->get("/admin/news/{$news->id}")->assertOk()->assertSee("zqn news {$news->id}");
})->with(['Admin', 'Publisher', 'English Editor', 'English Checker', 'Photos Editor', 'Viewer']);

it('gives create and edit to the English Editor and the Admin only', function (string $role, bool $mayWrite) {
    $news = adminNews();
    $user = staff($role);

    $create = $this->actingAs($user)->get('/admin/news/create');
    $edit = $this->actingAs($user)->get("/admin/news/{$news->id}/edit");

    $mayWrite ? $create->assertOk() : $create->assertForbidden();
    $mayWrite ? $edit->assertOk() : $edit->assertForbidden();
})->with([['Admin', true], ['English Editor', true], ['Publisher', false], ['English Checker', false], ['Photos Editor', false], ['Viewer', false]]);

it('shows the section in the sidebar after Successes, and in the Ctrl K list', function () {
    $this->actingAs(staff('Viewer'))->get('/admin/home')->assertSeeInOrder(['Micro projects', 'Initiatives', 'Successes', 'Newsletter'])->assertDontSee('New newsletter item');
    $this->actingAs(staff('English Editor'))->get('/admin/home')->assertSee('New newsletter item');
});

it('lists items, finds by title, filters by kind, place and tag, and counts the tabs', function () {
    $article = adminNews();
    $video = adminNews(['type' => 3, 'video' => 'https://www.youtube.com/embed/x', 'place_id' => 1, 'online' => 1]);
    $tag = DB::table('tags')->orderByDesc('id')->value('id');
    DB::table('news_tags')->where('news_id', $video->id)->update(['tag_id' => $tag]);

    $page = Livewire::actingAs(staff('Viewer'))->test('pages::admin.news.index')->set('search', 'zqn news');

    expect($page->viewData('counts'))->toMatchArray(['all' => 2, 'pending' => 1, 'online' => 1]);
    $page->set('type', '3')->assertSee("zqn news {$video->id}")->assertDontSee("zqn news {$article->id}");
    $page->set('type', '')->set('place', '3')->assertSee("zqn news {$article->id}")->assertDontSee("zqn news {$video->id}");
    $page->set('place', '')->set('tag', (string) $tag)->assertSee("zqn news {$video->id}")->assertDontSee("zqn news {$article->id}");
    $page->set('tag', '')->set('tab', 'online')->assertSee("zqn news {$video->id}")->assertDontSee("zqn news {$article->id}");
});

it('draws only the buttons the role may use', function () {
    $news = adminNews();

    Livewire::actingAs(staff('Viewer'))->test('pages::admin.news.index')->set('search', "zqn news {$news->id}")->assertDontSee('Publish')->assertDontSee('Edit</a>', false);
    Livewire::actingAs(staff('Publisher'))->test('pages::admin.news.index')->set('search', "zqn news {$news->id}")->assertSee('Publish')->assertDontSee('Edit</a>', false);
    Livewire::actingAs(staff('English Editor'))->test('pages::admin.news.index')->set('search', "zqn news {$news->id}")->assertSee('Edit</a>', false)->assertDontSee('Publish');
});

// -------------------------------------------------------------------- state moves

it('publishes an item whose English is checked, with legacy\'s log text', function () {
    $news = adminNews(['hidden' => 1]);
    $user = staff('Publisher');

    Livewire::actingAs($user)->test('pages::admin.news.index')->call('moveItem', $news->id, 'unhide');
    Livewire::actingAs($user)->test('pages::admin.news.index')
        ->call('moveItem', $news->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success' && str_contains($params['message'], "News #{$news->id} is online"));

    expect($news->refresh())->online->toBe(1)->hidden->toBeFalse()
        ->and(newsLogs($news)->orderBy('id')->pluck('operation')->all())->toBe(['News Status: Pending to publish', 'News Status: Online']);
});

it('will not publish an item whose English is not checked', function () {
    $news = adminNews(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.news.index')
        ->call('moveItem', $news->id, 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error' && str_contains($params['message'], 'English text is not checked'));

    expect($news->refresh()->online)->toBe(0);
});

it('walks an item through every move', function () {
    $news = adminNews(['english_check' => 0, 'online' => 1]);
    $page = Livewire::actingAs(staff('Admin'))->test('pages::admin.news.index');

    $page->call('moveItem', $news->id, 'unpublish');
    $page->call('moveItem', $news->id, 'hide');
    expect($news->refresh())->hidden->toBeTrue()->online->toBe(0);
    $page->call('moveItem', $news->id, 'unhide');
    $page->call('moveItem', $news->id, 'check-english');

    expect($news->refresh())->hidden->toBeFalse()->english_check->toBe(1)
        ->and(newsLogs($news)->orderBy('id')->pluck('operation')->all())
        ->toBe(['News Status: Offline', 'News Status: Hidden', 'News Status: Pending to publish', 'English Content Checked']);
});

it('keeps one Important item online: publishing a new one makes the old one regular', function () {
    $old = adminNews(['online' => 1, 'priority_status' => 1]);
    $new = adminNews(['priority_status' => 1]);
    $pinned = adminNews(['online' => 1, 'priority_status' => 2]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.news.index')->call('moveItem', $new->id, 'publish');

    expect($new->refresh())->online->toBe(1)->priority_status->toBe(1)
        ->and($old->refresh()->priority_status)->toBe(0)
        ->and($pinned->refresh()->priority_status)->toBe(2)
        ->and(newsLogs($old)->where('operation', "Important status replaced by News #{$new->id}")->exists())->toBeTrue();
});

it('refuses a move the role may not make', function (string $role, string $move) {
    $news = adminNews(['online' => 1, 'english_check' => 0]);

    Livewire::actingAs(staff($role))->test('pages::admin.news.index')
        ->call('moveItem', $news->id, $move)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');

    expect($news->refresh())->online->toBe(1)->english_check->toBe(0)->hidden->toBeFalse();
})->with([['Viewer', 'unpublish'], ['Publisher', 'unpublish'], ['English Editor', 'hide'], ['Photos Editor', 'check-english']]);

it('moves several at once and counts the ones it skipped', function () {
    $ready = adminNews();
    $notReady = adminNews(['english_check' => 0]);

    Livewire::actingAs(staff('Admin'))->test('pages::admin.news.index')
        ->call('transitionMany', [$ready->id, $notReady->id], 'publish')
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'warning' && str_contains($params['message'], '1 of 2 news'));

    expect($ready->refresh()->online)->toBe(1)->and($notReady->refresh()->online)->toBe(0);
});

// ------------------------------------------------------------ create, edit, delete

it('creates an item as legacy did: offline, unchecked, "0" for what was left empty, with its tags and a log row', function () {
    $user = staff('English Editor');
    $values = newsForm(['form.description' => '', 'form.edition_id' => '', 'form.rank' => '']);

    Livewire::actingAs($user)->test('pages::admin.news.form')->fill($values)->call('save')->assertHasNoErrors()->assertRedirect();

    $content = NewsContent::query()->where('title', 'zqn new item')->firstOrFail();
    $news = News::query()->findOrFail($content->news_id);

    expect($news)->online->toBe(0)->english_check->toBe(0)->hidden->toBeFalse()->video->toBe('0')->file->toBe('0')->rank->toBe(1)->priority_status->toBe(0)->edition_id->toBeNull()
        ->and($content)->description->toBe('0')
        ->and($content->content)->toBe('<p>A long enough text for a news item.</p>x')
        ->and(DB::table('news_tags')->where('news_id', $news->id)->count())->toBe(1)
        ->and(newsLogs($news)->where(['operation' => 'News Created', 'user_id' => $user->id])->exists())->toBeTrue();
});

it('asks for what is missing', function (array $values, array $errors) {
    newsPage()->fill(newsForm($values))->call('save')->assertHasErrors($errors)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
})->with([
    'a title, a date and text' => [['form.title' => '', 'form.date' => '', 'form.content' => '<p></p>'], ['form.title', 'form.date', 'form.content']],
    'a video link for a video item' => [['form.type' => '3', 'form.video' => ''], ['form.video']],
    'a known kind and place' => [['form.type' => '9', 'form.place_id' => '999'], ['form.type', 'form.place_id']],
    'a tag' => [['form.tags' => [], 'form.newTags' => ' , '], ['tags']],
    'a title no longer than 100' => [['form.title' => str_repeat('a', 101)], ['form.title']],
]);

it('makes the new tags typed in, reuses one that exists by any capitalisation, and ignores repeats', function () {
    $existing = DB::table('tags')->orderBy('id')->first();
    $before = DB::table('tags')->count();

    newsPage()->fill(newsForm(['form.tags' => [], 'form.newTags' => 'zqn fresh tag, '.mb_strtoupper($existing->name).', zqn fresh tag']))->call('save')->assertHasNoErrors();

    $news = News::query()->findOrFail(NewsContent::query()->where('title', 'zqn new item')->value('news_id'));
    $names = DB::table('news_tags')->join('tags', 'tags.id', '=', 'news_tags.tag_id')->where('news_id', $news->id)->pluck('name')->all();

    expect($names)->toEqualCanonicalizing(['zqn fresh tag', $existing->name])->and(DB::table('tags')->count())->toBe($before + 1);
});

it('refuses a tag longer than 50 characters', function () {
    newsPage()->fill(newsForm(['form.tags' => [], 'form.newTags' => str_repeat('t', 51)]))->call('save')->assertHasErrors('form.newTags');
});

it('stores an attached PDF under a safe name, and refuses a file that only says it is one', function () {
    newsPage()->fill(newsForm())->set('attachment', pdf('../../M&E Report 2020.pdf'))->call('save')->assertHasNoErrors();

    $news = News::query()->findOrFail(NewsContent::query()->where('title', 'zqn new item')->value('news_id'));
    expect($news->file)->toStartWith('/files/news/')->toEndWith('-ME Report 2020.pdf')->not->toContain('..')
        ->and(File::exists($this->media.$news->file))->toBeTrue();

    newsPage()->fill(newsForm(['form.title' => 'zqn fake']))->set('attachment', pdf('x.pdf', '<?php echo 1;'))->call('save')->assertHasErrors('attachment');
    expect(NewsContent::query()->where('title', 'zqn fake')->exists())->toBeFalse();
});

it('edits an item and logs one row for each change, takes it offline, and syncs the tags', function () {
    $news = adminNews(['online' => 1]);
    $user = staff('English Editor');
    $other = DB::table('tags')->where('id', '!=', DB::table('news_tags')->where('news_id', $news->id)->value('tag_id'))->orderBy('id')->value('id');

    Livewire::actingAs($user)->test('pages::admin.news.form', ['news' => $news])
        ->assertSet('form.title', "zqn news {$news->id}")
        ->set('form.title', 'zqn renamed')->set('form.date', '2026-10-05')->set('form.tags', [(string) $other])
        ->call('save')->assertHasNoErrors();

    $logs = newsLogs($news)->get()->keyBy('operation');
    expect($logs->keys()->all())->toEqualCanonicalizing(['News Edited: Title', 'News Edited: Date', 'News Edited: Tags', 'News Status: Offline'])
        ->and($logs['News Edited: Title'])->previous_data->toBe("zqn news {$news->id}")->current_data->toBe('zqn renamed')
        ->and($news->refresh()->online)->toBe(0)
        ->and(DB::table('news_tags')->where('news_id', $news->id)->pluck('tag_id')->all())->toBe([$other]);
});

it('does not log an edit when only line endings changed', function () {
    $news = adminNews([], ['content' => "<p>One</p>\r\n<p>Two</p>"]);

    newsPage('Admin', ['news' => $news])->set('form.content', "<p>One</p>\n<p>Two</p>")->call('save');

    expect(newsLogs($news)->where('operation', 'News Edited: Content')->exists())->toBeFalse();
});

it('replaces and removes an attachment, deleting the old file', function () {
    $news = adminNews();
    newsPage('Admin', ['news' => $news])->set('attachment', pdf('one.pdf'))->call('save');
    $first = $news->refresh()->file;

    newsPage('Admin', ['news' => $news])->set('attachment', pdf('two.pdf'))->call('save');
    $second = $news->refresh()->file;
    expect($second)->not->toBe($first)->and(File::exists($this->media.$first))->toBeFalse()->and(File::exists($this->media.$second))->toBeTrue();

    newsPage('Admin', ['news' => $news])->set('removeFile', true)->call('save');
    expect($news->refresh()->file)->toBe('0')->and(File::exists($this->media.$second))->toBeFalse()
        ->and(newsLogs($news)->where('operation', 'News Edited: File')->count())->toBe(3);
});

it('deletes an item for an Admin only, with its tag links and its attachment, and logs it', function () {
    $news = adminNews();
    newsPage('Admin', ['news' => $news])->set('attachment', pdf('one.pdf'))->call('save');
    $path = $news->refresh()->file;
    $admin = staff('Admin');

    Livewire::actingAs(staff('English Editor'))->test('pages::admin.news.index')->call('deleteItem', $news->id)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'error');
    expect(News::query()->whereKey($news->id)->exists())->toBeTrue();

    Livewire::actingAs($admin)->test('pages::admin.news.index')->call('deleteItem', $news->id)
        ->assertDispatched('admin-alert', fn (string $event, array $params): bool => $params['type'] === 'success');

    expect(News::query()->whereKey($news->id)->exists())->toBeFalse()
        ->and(NewsContent::query()->where('news_id', $news->id)->exists())->toBeFalse()
        ->and(DB::table('news_tags')->where('news_id', $news->id)->exists())->toBeFalse()
        ->and(File::exists($this->media.$path))->toBeFalse()
        ->and(newsLogs($news)->where(['operation' => 'News deleted', 'user_id' => $admin->id])->exists())->toBeTrue();
});

// ------------------------------------------------------------------ small pieces

it('keeps a stored document inside /files, and never touches anything outside it', function () {
    file_put_contents($this->media.'/secret.txt', 'keep');

    expect(PhotoFiles::delete('/files/../secret.txt'))->toBe(0)
        ->and(File::exists($this->media.'/secret.txt'))->toBeTrue()
        ->and(DocumentFiles::available())->toBeTrue();
});

it('tells the four kinds of content apart', function () {
    expect(ContentKind::of(adminNews()))->toBe(ContentKind::News)
        ->and(ContentKind::News->route('edit'))->toBe('admin.news.edit')
        ->and(ContentKind::News->noun())->toBe('news')
        ->and(ContentKind::News->supports(ContentTransition::Hide))->toBeTrue();
});
