<?php

use App\Models\Edition;
use App\Models\News;
use App\Models\NewsContent;
use App\Models\PageView;
use App\Models\Subscriber;
use App\Queries\NewsListQuery;
use App\Support\NewsPresenter;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

/*
 * /our-news: the lead story, the headlines, the list with its pinned
 * stories and Load More, and the newsletter sign-up. The stories are the
 * shared database's own; each test is rolled back.
 */

function news(array $attributes, string $title = 'A test story', string $content = 'Some words.'): News
{
    $news = News::query()->create($attributes + [
        'rank' => 0, 'type' => 0, 'english_check' => 1, 'online' => 1, 'what_category_id' => 0,
        'who_category_id' => 0, 'place_id' => 3, 'file' => '', 'edition_id' => null, 'date' => '2030-01-01', 'priority_status' => 0,
    ]);
    NewsContent::query()->create(['news_id' => $news->id, 'title' => $title, 'description' => '', 'content' => $content]);

    return $news;
}

it('renders the page: header, cover, headlines, lead, list and the sign-up', function () {
    $lead = NewsListQuery::lead();

    $this->get('/our-news')
        ->assertOk()
        ->assertSee('<title>Our News</title>', false)
        ->assertSeeInOrder(['OUR NEWS', 'news-hero', 'news-ticker', 'news-card--lead', 'news-list', 'Get Latest Updates On Our News', 'Subscribe our newsletter to receive the latest news.'], false)
        ->assertSee(e($lead->content->title), false);
});

it('leads with the important story, which is not repeated in the list', function () {
    $lead = NewsListQuery::lead();

    expect($lead->priority_status)->toBe(NewsListQuery::IMPORTANT);
    expect(NewsListQuery::ordinary(1000, $lead)->modelKeys())->not->toContain($lead->id);
});

it('leads with the latest ordinary story when none is important', function () {
    News::query()->where('priority_status', NewsListQuery::IMPORTANT)->update(['priority_status' => NewsListQuery::ORDINARY]);
    $latest = news(['date' => '2031-05-05'], 'The newest story');

    expect(NewsListQuery::lead()->id)->toBe($latest->id);
    expect(NewsListQuery::ordinary(1000, $latest)->modelKeys())->not->toContain($latest->id);
});

it('leaves out stories that are offline', function () {
    $offline = news(['online' => 0, 'date' => '2032-01-01'], 'Not for the public');

    $this->get('/our-news')->assertOk()->assertDontSee('Not for the public');
    expect(NewsListQuery::ordinary(1000, null)->modelKeys())->not->toContain($offline->id);
});

it('shows two rows of five stories and one pinned story after the first row', function () {
    $page = $this->get('/our-news')->assertOk();

    expect(substr_count($page->getContent(), 'class="news-row"'))->toBe(2);
    expect(substr_count($page->getContent(), 'class="news-pinned"'))->toBe(1);

    $page->assertSeeInOrder(['class="news-row"', 'class="news-pinned"', 'class="news-row"'], false);
});

it('links each story to its page by id, place and date, in a new tab', function () {
    $story = news(['date' => '2033-02-03', 'place_id' => 5], 'Linked story');

    $this->get('/our-news')
        ->assertSee('href="/our-news/'.$story->id.'-homs-countryside-20330203" target="_blank" rel="noopener noreferrer"', false)
        ->assertSee('03/02/2033 - Homs Countryside', false);
});

it('calls the national place, and a story with no place, Syria', function () {
    $everywhere = news(['place_id' => 0], 'For all of Syria');

    expect((new NewsPresenter($everywhere->load('place')))->place())->toBe('Syria');
    expect((new NewsPresenter($everywhere->load('place')))->url())->toEndWith('-syria-20300101');
});

it('adds two rows to the list with Load More, and a pinned story for each two', function () {
    $page = Livewire::test('pages::our-news');
    $rows = fn () => substr_count($page->html(), 'class="news-row"');

    expect($rows())->toBe(2);

    $page->call('loadMore');
    expect($rows())->toBe(4);
    expect(substr_count($page->html(), 'class="news-pinned"'))->toBe(2);
});

it('lays the list out in as many columns as the browser reports, and takes any other number as five', function () {
    $page = Livewire::test('pages::our-news')->set('chunkSize', 4);
    expect(substr_count($page->html(), 'class="news-row"'))->toBe(2);

    $page->call('loadMore');
    // Four columns: 8 stories at first, 8 more at each click.
    expect(NewsListQuery::ordinary(16, NewsListQuery::lead())->count())->toBe(16);

    $page->set('chunkSize', 99)->set('amount', 0);
    expect(substr_count($page->html(), 'class="news-card"'))->toBe(10);
});

it('stops offering Load More once every story is shown', function () {
    $page = Livewire::test('pages::our-news')->assertSee('Load More');

    $total = NewsListQuery::countOrdinary(NewsListQuery::lead());
    $page->set('amount', $total)->assertDontSee('Load More');
});

it('scrolls the pinned story, the lead and then the latest five as headlines', function () {
    $lead = NewsListQuery::lead();
    $headlines = NewsListQuery::ticker($lead);

    expect($headlines)->toHaveCount(7);
    expect($headlines[0]->priority_status)->toBe(NewsListQuery::PINNED);
    expect($headlines[1]->id)->toBe($lead->id);
    expect($headlines->modelKeys())->toBe(array_values(array_unique($headlines->modelKeys())));
});

it('draws every headline twice for the endless scroll', function () {
    $page = $this->get('/our-news')->assertOk();

    expect(substr_count($page->getContent(), 'class="news-ticker__item"'))->toBe(14);
    expect(preg_match_all('/class="news-ticker__item"\s+aria-hidden="true"/', $page->getContent()))->toBe(7);
});

it('links the magazine only while an edition is online', function () {
    $this->get('/our-news')->assertSee('class="news-magazine"', false);

    Edition::query()->update(['online' => 0]);

    $this->get('/our-news')->assertOk()->assertDontSee('class="news-magazine"', false);
});

it('formats a video length as minutes and seconds, or with hours from an hour up', function () {
    $short = news(['duration' => '00:03:54']);
    $long = news(['duration' => '01:02:03']);
    $article = news(['duration' => null]);

    expect((new NewsPresenter($short))->duration())->toBe('03:54');
    expect((new NewsPresenter($long))->duration())->toBe('01:02:03');
    expect((new NewsPresenter($article))->duration())->toBeNull();
});

it('cuts the lead story after 36 words with a break after the 18th, and escapes what it prints', function () {
    $words = collect(range(1, 40))->map(fn ($n) => "w{$n}")->implode(' ');
    $story = news([], 'Long one', "<p>{$words} &amp; <script>alert(1)</script></p>");

    $excerpt = (string) (new NewsPresenter($story->load('content')))->leadExcerpt();

    expect($excerpt)->toStartWith('w1 w2')
        ->and($excerpt)->toContain('w18 <br> w19')
        ->and($excerpt)->toEndWith('w36...')
        ->and($excerpt)->not->toContain('w37');

    $unsafe = news([], 'Unsafe', 'a &lt;b&gt; <i>tag</i>');
    expect((string) (new NewsPresenter($unsafe->load('content')))->leadExcerpt())->toBe('a &lt;b&gt; tag');
});

it('breaks a pinned story three times and ends it with dots when it is long', function () {
    $words = collect(range(1, 70))->map(fn ($n) => "w{$n}")->implode(' ');
    $story = news([], 'Pinned', $words);

    $excerpt = (string) (new NewsPresenter($story->load('content')))->pinnedExcerpt();

    expect(substr_count($excerpt, '<br>'))->toBe(3)
        ->and($excerpt)->toContain('w15 <br> w16')
        ->and($excerpt)->toEndWith('...');
});

it('counts a visit once per session under the legacy page name', function () {
    config(['hcsyria.count_page_views' => true]);
    $before = (int) PageView::query()->where('page_name', 'our-news')->value('views');

    $this->get('/our-news')->assertSessionHas('has_viewed_our-news', true);
    $this->get('/our-news');

    expect((int) PageView::query()->where('page_name', 'our-news')->value('views'))->toBe($before + 1);
});

describe('the newsletter sign-up', function () {
    beforeEach(fn () => RateLimiter::clear('newsletter-subscribe:127.0.0.1'));

    it('adds a new address with a token for the unsubscribe link, and clears the box', function () {
        Livewire::test('newsletter-subscribe')
            ->set('email', 'new.reader@example.test')
            ->call('subscribe')
            ->assertSet('result', 'subscribed')
            ->assertSet('email', '')
            ->assertSee('Subscribed!');

        $row = Subscriber::query()->where('mail', 'new.reader@example.test')->first();
        expect($row)->not->toBeNull()
            ->and(strlen($row->token))->toBe(50)
            ->and($row->active)->toBe(1);
    });

    it('says so, and adds nothing, for an address that is already on the list', function () {
        $existing = Subscriber::query()->value('mail');
        $before = Subscriber::query()->count();

        Livewire::test('newsletter-subscribe')
            ->set('email', $existing)
            ->call('subscribe')
            ->assertSet('result', 'exists')
            ->assertSee('Email already exists!');

        expect(Subscriber::query()->count())->toBe($before);
    });

    it('asks for a valid address when there is none or it is not one', function (string $email) {
        $before = Subscriber::query()->count();

        Livewire::test('newsletter-subscribe')
            ->set('email', $email)
            ->call('subscribe')
            ->assertSet('result', 'invalid')
            ->assertSee('Please enter a valid email address.');

        expect(Subscriber::query()->count())->toBe($before);
    })->with(['', 'not an address', 'two@@example.test', str_repeat('a', 200).'@example.test']);

    it('clears the note when it is told to, as the three-second poll does', function () {
        Livewire::test('newsletter-subscribe')
            ->set('email', Subscriber::query()->value('mail'))
            ->call('subscribe')
            ->assertSeeHtml('wire:poll.3s="clear"')
            ->call('clear')
            ->assertSet('result', '')
            ->assertDontSeeHtml('wire:poll.3s');
    });

    it('answers with a server error after ten tries a minute, as the legacy throttle did', function () {
        $box = Livewire::test('newsletter-subscribe')->set('email', Subscriber::query()->value('mail'));

        foreach (range(1, 10) as $try) {
            $box->call('subscribe')->assertSet('result', 'exists');
        }

        $box->call('subscribe')
            ->assertSet('result', 'error')
            ->assertSee('Server error. Please try again later.');
    });
});
