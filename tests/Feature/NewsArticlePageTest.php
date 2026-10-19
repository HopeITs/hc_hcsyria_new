<?php

use App\Models\News;
use App\Models\NewsContent;
use App\Models\NewsImage;
use App\Queries\NewsArticleQuery;
use App\Support\NewsPresenter;
use App\Support\SafeHtml;

/*
 * /our-news/{id}-{city}-{date}: one story with its video or photos, its
 * text, the share menu and the sign-up. Stories are the shared database's
 * own, plus ones made inside each test's rolled-back transaction.
 */

function makeStory(array $attributes = [], array $text = [], array $photos = []): News
{
    $story = News::query()->create($attributes + [
        'rank' => 0, 'type' => 0, 'english_check' => 1, 'online' => 1, 'what_category_id' => 0, 'who_category_id' => 0,
        'place_id' => 3, 'edition_id' => null, 'file' => '0', 'video' => '0', 'date' => '2030-04-05', 'priority_status' => 0,
        'total_views' => 7, 'view_count' => 5,
    ]);
    NewsContent::query()->create($text + ['news_id' => $story->id, 'title' => 'A story for the test', 'description' => 'What it is about', 'content' => '<p>First paragraph.</p>']);

    foreach ($photos as $order => $path) {
        NewsImage::query()->create(['news_id' => $story->id, 'path' => $path, 'image_order' => $order, 'sizetype' => 3]);
    }

    return $story;
}

it('shows the story: header, title, place and date, views, text and the sign-up', function () {
    $story = makeStory(text: ['title' => 'Quiet <b>title</b> & more']);

    $this->get("/our-news/{$story->id}-aleppo-20300405")
        ->assertOk()
        ->assertSee('<title>Our News</title>', false)
        ->assertSeeInOrder(['OUR NEWS', 'Quiet &lt;b&gt;title&lt;/b&gt; &amp; more', 'Aleppo 5/4/2030', '7', '<p>First paragraph.</p>', 'Get Latest Updates On Our News', 'Subscribe our newsletter to receive the latest news.'], false);
});

it('finds the story by its id alone, whatever city and date the address has', function () {
    $story = makeStory();

    $this->get("/our-news/{$story->id}-homs-countryside-19990101")->assertOk()->assertSee('A story for the test');
    $this->get("/our-news/{$story->id}-nowhere-00000000")->assertOk()->assertSee('A story for the test');
});

it('is a 404 for an unknown, offline or malformed address', function (string $address) {
    $this->get($address)->assertNotFound();
})->with(['/our-news/999999999-aleppo-20300405', '/our-news/abc-aleppo-20300405', '/our-news/78-aleppo-2030', '/our-news/78-Aleppo%20x-20300405']);

it('is a 404 for a story that is offline', function () {
    $story = makeStory(['online' => 0]);

    expect(NewsArticleQuery::find($story->id))->toBeNull();
    $this->get("/our-news/{$story->id}-aleppo-20300405")->assertNotFound();
});

it('names the place Syria for the national place', function () {
    $story = makeStory(['place_id' => 0]);

    $this->get("/our-news/{$story->id}-syria-20300405")->assertSee('Syria 5/4/2030');
});

it('draws the gallery photos as a carousel, in their order, from the large copy of each', function () {
    $story = makeStory(photos: [2 => '/images/news/9/second.jpg', 1 => '/images/news/9/first.jpg']);
    NewsImage::query()->create(['news_id' => $story->id, 'path' => '/images/news/9/thumb.jpg', 'image_order' => 0, 'sizetype' => 0]);

    $page = $this->get("/our-news/{$story->id}-aleppo-20300405")->assertOk();

    $page->assertSeeInOrder(['/images/news/9/firstb.jpg', '/images/news/9/secondb.jpg'], false)
        ->assertDontSee('thumbb.jpg')
        ->assertSee('carousel__control--prev', false);
    expect(substr_count($page->getContent(), 'class="news-story__photo"'))->toBe(2);
});

it('has no carousel for a story without gallery photos', function () {
    $story = makeStory();

    $this->get("/our-news/{$story->id}-aleppo-20300405")->assertDontSee('news-story__photo', false)->assertDontSee('carousel__control', false);
});

it('frames a video story (type 3) from the privacy host, in whichever form its address was given', function (string $address) {
    $story = makeStory(['type' => 3, 'video' => $address]);

    $this->get("/our-news/{$story->id}-aleppo-20300405")
        ->assertOk()
        ->assertSee('<iframe src="https://www.youtube-nocookie.com/embed/cb-3kN3je4Y"', false)
        ->assertDontSee('data-lightbox', false);
})->with([
    'embed' => 'https://www.youtube.com/embed/cb-3kN3je4Y?si=abc',
    'watch' => 'https://www.youtube.com/watch?v=cb-3kN3je4Y&t=3',
    'short' => 'https://youtu.be/cb-3kN3je4Y?si=abc',
]);

it('adds a play button that opens the video in the lightbox to a photo story that has one', function () {
    $story = makeStory(['video' => 'https://www.youtube.com/embed/Tk6Lqo22C0o?si=x'], photos: [0 => '/images/news/9/a.jpg']);

    $this->get("/our-news/{$story->id}-aleppo-20300405")
        ->assertSee('href="https://www.youtube.com/embed/Tk6Lqo22C0o?si=x" class="news-story__action" data-lightbox="newsletterVideo-'.$story->id.'"', false)
        ->assertSee('news-story__photo', false);
});

it('draws no video, and no button, for an address that is not a YouTube one', function (string $video) {
    $story = makeStory(['type' => 3, 'video' => $video]);

    $this->get("/our-news/{$story->id}-aleppo-20300405")->assertOk()->assertDontSee('news-story__video', false)->assertDontSee('Play Video');
})->with(['https://evil.example/embed/abc', '\\n', '0', '']);

it('links an attached file, named by the file', function () {
    $story = makeStory(['file' => '/files/news/Report 2020.pdf']);

    $this->get("/our-news/{$story->id}-aleppo-20300405")
        ->assertSee('href="/files/news/Report 2020.pdf" class="news-story__action" download title="Report 2020.pdf"', false);
});

it('offers four ways to share the story, carrying its title and address', function () {
    $story = makeStory(text: ['title' => 'Share me']);
    $address = urlencode(url("/our-news/{$story->id}-aleppo-20300405"));

    $this->get("/our-news/{$story->id}-aleppo-20300405")
        ->assertSee('Copy Link')
        ->assertSee('https://api.whatsapp.com/send?text='.urlencode('Share me - '.url("/our-news/{$story->id}-aleppo-20300405")), false)
        ->assertSee("https://t.me/share/url?url={$address}&amp;text=Share+me", false)
        ->assertSee("https://www.linkedin.com/sharing/share-offsite/?url={$address}", false);
});

it('puts the story in the share tags of the page', function () {
    $story = makeStory(text: ['title' => 'Tagged', 'description' => 'Why it matters']);

    $this->get("/our-news/{$story->id}-homs-countryside-20300405")
        ->assertSee('property="og:url"  content="https://hcsyria.org/our-news/'.$story->id.'-homs-countryside-20300405"', false)
        ->assertSee('property="og:title"  content="Tagged"', false)
        ->assertSee('property="og:description"  content="Why it matters"', false)
        ->assertDontSee('name="keywords"', false);
});

it('prints only what an editor can make, and none of its attributes or scripts', function () {
    $story = makeStory(text: ['content' => '<p onclick="x()">Hello <strong>there</strong></p><script>alert(1)</script><a href="javascript:x()">link</a><ol><li style="color:red">one</li></ol>']);

    $this->get("/our-news/{$story->id}-aleppo-20300405")
        ->assertSee('<p>Hello <strong>there</strong></p>alert(1)link<ol><li>one</li></ol>', false)
        ->assertDontSee('onclick', false)
        ->assertDontSee('<script>alert', false);

    expect(SafeHtml::text(null))->toBe('');
});

it('counts a visit in total_views, and the visitor once per session in view_count, when counting is on', function () {
    config(['hcsyria.count_page_views' => true]);
    $story = makeStory();

    $this->get("/our-news/{$story->id}-aleppo-20300405")->assertSee('news-story__count">8<', false);
    $this->get("/our-news/{$story->id}-aleppo-20300405")->assertSee('news-story__count">9<', false);

    $story->refresh();
    expect($story->total_views)->toBe(9)->and($story->view_count)->toBe(6);
});

it('does not touch the counts when counting is off', function () {
    $story = makeStory();

    $this->get("/our-news/{$story->id}-aleppo-20300405")->assertSee('news-story__count">7<', false);

    expect($story->refresh()->total_views)->toBe(7)->and($story->view_count)->toBe(5);
});

it('is the address the news list links to', function () {
    $story = makeStory(['place_id' => 5, 'date' => '2031-02-03']);
    $address = (new NewsPresenter($story->load('place')))->url();

    expect($address)->toBe("/our-news/{$story->id}-homs-countryside-20310203");
    $this->get($address)->assertOk();
});
