<?php

use App\Actions\RecordArticleView;
use App\Queries\NewsArticleQuery;
use App\Support\NewsPresenter;
use App\Support\SafeHtml;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * One news story, /our-news/{id}-{city}-{date}. Ported from
 * new/public/article.blade.php and PublicController::show_article() at
 * legacy commit 6d45ef9: the title with its video, file and share buttons,
 * the place, date and number of views, the video or the photos, the text,
 * and the newsletter sign-up.
 *
 * Only the id picks the story; the city and the date of the address are
 * not looked at (legacy used them for the share address and nothing else).
 * An id that is no story, or one that is offline, is a 404 (legacy: its
 * 404 design with a 200).
 *
 * Not ported: the English/French switch, the Facebook SDK and Leaflet
 * scripts the page loaded and never used, and the jquery.balloon tooltip on
 * the paperclip (the browser's own tooltip, with the file's name, stays).
 */
new #[Layout('layouts::app', [
    'title' => 'navbar.newsletter',
    'keywords' => null,
    'preloader' => true,
    'menu' => 'slide',
])] class extends Component
{
    public int $id = 0;

    public string $city = '';

    public string $date = '';

    public function mount(int $id, string $city, string $date, RecordArticleView $recordArticleView): void
    {
        $this->id = $id;
        $this->city = $city;
        $this->date = $date;

        $news = NewsArticleQuery::find($id);
        abort_if($news === null, 404);

        $recordArticleView($news);
    }

    public function render()
    {
        $news = NewsArticleQuery::find($this->id);
        abort_if($news === null, 404);

        $story = new NewsPresenter($news);

        return $this->view([
            'news' => $news,
            'story' => $story,
            'views' => $news->total_views ?? 0,
            'body' => SafeHtml::text($news->content?->content),
        ])->layoutData([
            // Legacy had no keywords tag here; a null would bring the organisation's back.
            'keywords' => '',
            'meta' => [
                ['property' => 'fb:app_id', 'content' => '2133577546862306'],
                ['property' => 'og:url', 'content' => 'https://hcsyria.org/our-news/'.$this->id.'-'.Str::slug(strtolower($this->city)).'-'.$this->date],
                ['property' => 'og:title', 'content' => $story->title()],
                ['property' => 'og:description', 'content' => (string) $news->content?->description],
            ],
        ]);
    }
};
?>

<div class="page-start">
    <div class="news-story">
        <div class="news-header news-header--story">
            <h1 class="news-header__title">OUR NEWS</h1>
        </div>

        <div class="news-story__paper">
            <div class="news-story__head">
                <div class="news-story__title-row">
                    <h1 class="news-story__title">{{ $story->title() }}</h1>

                    <div class="news-story__actions">
                        @if ($story->videoId() && $news->type !== 3)
                            <a href="{{ $news->video }}" class="news-story__action" data-lightbox="newsletterVideo-{{ $news->id }}" title="Play Video">
                                <x-ui.icon name="fa-play" class="news-story__icon news-story__icon--play"/>
                            </a>
                        @endif

                        @if ($story->fileName() !== null)
                            <a href="{{ $news->file }}" class="news-story__action" download title="{{ $story->fileName() }}">
                                <x-ui.icon name="fa-paperclip" class="news-story__icon news-story__icon--file"/>
                            </a>
                        @endif

                        <div class="news-share" x-data="shareMenu" @click.outside="close()">
                            <button type="button" class="news-story__action" title="Share Article" aria-haspopup="true" :aria-expanded="open" @click="toggle()">
                                <x-ui.icon name="fa-share" class="news-story__icon news-story__icon--share"/>
                            </button>

                            <div class="news-share__menu" :class="{ 'is-open': open }">
                                <button type="button" class="news-share__item" @click="copy()">
                                    <img src="/images/our-news/logo/copy-link-icon.svg" class="news-share__icon" alt="Copy">
                                    <span class="news-share__text" :class="{ 'is-copied': copied }" x-text="copied ? 'Link Copied!' : 'Copy Link'">Copy Link</span>
                                </button>
                                <a class="news-share__item" target="_blank" href="https://api.whatsapp.com/send?text={{ urlencode($story->title().' - '.url()->current()) }}">
                                    <img src="/images/our-news/logo/whatsapp-icon.svg" class="news-share__icon" alt="WhatsApp">
                                    <span class="news-share__text">WhatsApp</span>
                                </a>
                                <a class="news-share__item" target="_blank" href="https://t.me/share/url?url={{ urlencode(url()->current()) }}&amp;text={{ urlencode($story->title()) }}">
                                    <img src="/images/our-news/logo/telegram-icon.svg" class="news-share__icon" alt="Telegram">
                                    <span class="news-share__text">Telegram</span>
                                </a>
                                <a class="news-share__item" target="_blank" href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}">
                                    <img src="/images/our-news/logo/linked-in-icon.svg" class="news-share__icon" alt="LinkedIn">
                                    <span class="news-share__text">LinkedIn</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="news-story__rule news-story__rule--thin">

                <div class="news-story__meta">
                    <div>{{ $story->place() }} {{ $story->articleDate() }}</div>

                    <span class="news-story__views">
                        <span class="news-story__count">{{ $views }}</span>
                        <x-ui.icon name="fa-eye" class="news-story__icon news-story__icon--views"/>
                    </span>
                </div>
            </div>

            @if ($news->type === 3 && $story->videoId())
                <div class="news-story__media">
                    <div class="news-story__video">
                        <iframe src="https://www.youtube-nocookie.com/embed/{{ $story->videoId() }}" title="{{ $story->title() }}"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            @elseif ($news->newsImages->isNotEmpty())
                <div class="news-story__media">
                    <x-ui.carousel :count="$news->newsImages->count()" :interval="5000">
                        <x-ui.carousel.track>
                            @foreach ($news->newsImages as $i => $image)
                                {{-- The large copy of a gallery photo is named after it, with b before the extension. --}}
                                <x-ui.carousel.item :i="$i" slide>
                                    <img class="news-story__photo" src="{{ substr($image->path, 0, -4).'b.jpg' }}" alt="Article Image">
                                </x-ui.carousel.item>
                            @endforeach
                        </x-ui.carousel.track>
                        <x-ui.carousel.controls/>
                    </x-ui.carousel>
                </div>
            @endif

            <div class="news-story__text">{!! $body !!}</div>

            <hr class="news-story__rule news-story__rule--thick">
        </div>
    </div>

    <x-site.news-signup narrow/>
</div>
