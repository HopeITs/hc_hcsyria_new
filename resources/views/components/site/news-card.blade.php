@props(['news', 'lead' => false])

@php
    use App\Support\NewsPresenter;

    $story = new NewsPresenter($news);
    $photo = $story->photo($lead ? NewsPresenter::LEAD_PHOTO : NewsPresenter::TILE_PHOTO);
@endphp

{{--
    A story as a photo card with its date, place and title over the bottom
    (resources/css/pages/news.css): the square tile in the list, or with
    :lead the large one that heads the page, which also prints the beginning
    of the story. A video's length shows in a badge. Legacy drew a placeholder
    from picsum.photos for a story with no photo; this draws the card
    without one (the content security policy allows no such host).
--}}
<a href="{{ $story->url() }}" target="_blank" rel="noopener noreferrer" @class(['news-card', 'news-card--lead' => $lead])>
    @if ($story->duration())
        <div class="news-badge"><x-ui.icon name="bi-play-fill"/> {{ $story->duration() }}</div>
    @endif
    @if ($photo)
        <img src="{{ $photo }}" alt="{{ $lead ? $story->title() : 'News Thumbnail' }}">
    @endif
    <div class="news-overlay">
        <span class="news-meta">{{ $story->meta() }}</span>
        @if ($lead)
            <h2 class="news-title news-title--large">{{ $story->title() }}</h2>
            <p class="news-content news-content--lead">{{ $story->leadExcerpt() }}</p>
        @else
            <h3 class="news-title news-title--clamp">{{ $story->title() }}</h3>
            <x-ui.icon name="bi-chevron-right" class="news-chevron"/>
        @endif
    </div>
</a>
