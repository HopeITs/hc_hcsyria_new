<?php

use App\Actions\RecordPageView;
use App\Queries\CoverQuery;
use App\Queries\NewsListQuery;
use Livewire\Attributes\Layout;
use Livewire\Component;

/*
 * The news page, /our-news. Ported from new/public/newsletter.blade.php,
 * PublicController::newsletter() and the NewsPage Livewire component at
 * legacy commit 6d45ef9: the cover photo and the headlines under it, the
 * lead story beside the magazine, the list of stories with a pinned one
 * after every two rows, Load More, and the subscribe form.
 *
 * Not ported: the filter window (place, tag, dates, kind of story) and the
 * code behind it. Its button was commented out of the legacy page, so no
 * visitor could open it; flatpickr, Bootstrap's modal and the draft and
 * applied filter fields went with it. Also not drawn: the page-view count
 * and the English/French switch, which legacy computed and never showed.
 *
 * The legacy news page lacks the script that makes the menus open at once,
 * so here they slide (menu: slide).
 *
 * Each row of the list holds as many stories as the browser says the window
 * has columns (see resources/js/news-page.js).
 */
new #[Layout('layouts::app', [
    'title' => 'navbar.newsletter',
    'keywords' => null,
    'preloader' => true,
    'menu' => 'slide',
])] class extends Component
{
    /** The photos of the cover, kept under this category by the admin. */
    private const COVER = 40;

    /** The list starts with two rows of stories, and each Load More adds two more. */
    private const ROWS_PER_PAGE = 2;

    /** How many stories a row holds: 4 on phones, 6 on tablets, 5 beyond (set by the browser). */
    public int $chunkSize = 5;

    /** The stories added by Load More, beyond the first two rows. */
    public int $amount = 0;

    public function mount(RecordPageView $recordPageView): void
    {
        $recordPageView('our-news');
    }

    public function loadMore(): void
    {
        $this->amount += $this->columns() * self::ROWS_PER_PAGE;
    }

    /** The browser tells how many columns fit; anything else is taken as the default. */
    private function columns(): int
    {
        return in_array($this->chunkSize, [4, 5, 6], true) ? $this->chunkSize : 5;
    }

    public function with(): array
    {
        $columns = $this->columns();
        $regularLimit = $columns * self::ROWS_PER_PAGE + $this->amount;
        // One pinned story for every two rows (5 stories, a pinned one, 5 more).
        $pinnedLimit = intdiv($regularLimit, $columns * self::ROWS_PER_PAGE);

        $lead = NewsListQuery::lead();

        return [
            'columns' => $columns,
            'cover' => CoverQuery::images(self::COVER)->first(),
            'lead' => $lead,
            'ticker' => NewsListQuery::ticker($lead),
            'hasEdition' => NewsListQuery::hasEdition(),
            'ordinary' => NewsListQuery::ordinary($regularLimit, $lead),
            'pinned' => NewsListQuery::pinned($pinnedLimit),
            'hasMore' => NewsListQuery::countOrdinary($lead) > $regularLimit || NewsListQuery::countPinned() > $pinnedLimit,
        ];
    }
};
?>

<div>
    <div class="news-page" x-data="newsColumns">
        <div class="news-header">
            <h1 class="news-header__title">OUR NEWS</h1>
        </div>

        <div class="news-hero">
            <div class="news-hero__photo">
                <img class="news-hero__image" src="{{ $cover?->image_path ?? '/images/our-news/header-image.jpg' }}" alt="Header">
                @if (! empty($cover?->caption))
                    <div class="news-hero__caption">{{ $cover->caption }}</div>
                @endif
                <div class="news-hero__time" x-data="clock" wire:ignore></div>
            </div>

            <x-site.news-ticker :stories="$ticker"/>
        </div>

        <div class="news-top">
            <div class="news-top__lead">
                @if ($lead)
                    <x-site.news-card :news="$lead" lead/>
                @endif
            </div>

            <div class="news-top__magazine">
                @if ($hasEdition)
                    <a href="/rootsofhope" class="news-magazine">
                        <img src="/images/our-news/roots-of-hope-magazine.png" alt="Roots of Hope Magazine" class="news-magazine__image">
                    </a>
                @endif
            </div>
        </div>

        <div class="news-list">
            @if ($ordinary->isEmpty())
                <div class="news-empty">
                    <x-ui.icon name="bi-search" class="news-empty__icon"/>
                    <h3 class="news-empty__title">No news found matching your filters.</h3>
                    <p>Try clearing your filters or adjusting the search.</p>
                </div>
            @endif

            @php($rows = $ordinary->chunk($columns))
            @foreach ($rows as $index => $row)
                <div class="news-row">
                    @foreach ($row as $news)
                        <x-site.news-card :$news wire:key="story-{{ $news->id }}"/>
                    @endforeach
                </div>

                @if ($index % 2 === 0 && isset($pinned[intdiv($index, 2)]))
                    <x-site.news-pinned :news="$pinned[intdiv($index, 2)]" wire:key="pinned-{{ $pinned[intdiv($index, 2)]->id }}"/>
                @endif
            @endforeach

            {{-- Pinned stories left once every row has had its share. --}}
            @for ($i = $rows->isEmpty() ? 0 : intdiv($rows->count() + 1, 2); $i < $pinned->count(); $i++)
                <x-site.news-pinned :news="$pinned[$i]" pin wire:key="pinned-{{ $pinned[$i]->id }}"/>
            @endfor

            @if ($hasMore)
                <div class="news-more">
                    <button type="button" class="news-more__button" wire:click="loadMore" wire:loading.attr="disabled" wire:target="loadMore">
                        <span wire:loading.remove wire:target="loadMore">Load More</span>
                        <span wire:loading wire:target="loadMore">Loading...</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    <x-ui.reveal :ms="800" class="news-signup-title">
        <x-ui.section-title>Get Latest Updates On Our News</x-ui.section-title>
    </x-ui.reveal>

    <x-ui.reveal :ms="800" class="news-signup">
        <div class="news-signup__column">
            <livewire:newsletter-subscribe/>
        </div>
    </x-ui.reveal>
</div>
