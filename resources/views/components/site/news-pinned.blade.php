@props(['news', 'pin' => false])

@php
    use App\Support\NewsPresenter;

    $story = new NewsPresenter($news);
    $photo = $story->photo(NewsPresenter::PINNED_PHOTO);
@endphp

{{--
    A pinned story: its photo beside a red box with the title and the
    beginning of the story (resources/css/pages/news.css). One follows every
    two rows of the list; those left over after the last row wear a pin.
--}}
<a href="{{ $story->url() }}" target="_blank" rel="noopener noreferrer" class="news-pinned">
    <div class="news-pinned__photo">
        @if ($story->duration())
            <div class="news-badge"><x-ui.icon name="bi-play-fill"/> {{ $story->duration() }}</div>
        @endif
        @if ($photo)
            <img src="{{ $photo }}" alt="Pinned News">
        @endif
    </div>
    <div class="news-pinned__box">
        @if ($pin)
            <x-ui.icon name="bi-pin-fill" class="news-pinned__pin"/>
        @endif
        <h2 class="news-title">{{ $story->title() }}</h2>
        <p class="news-content news-content--pinned">{{ $story->pinnedExcerpt() }}</p>
        <x-ui.icon name="bi-chevron-right" class="news-chevron"/>
    </div>
</a>
