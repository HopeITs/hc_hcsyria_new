@props(['activity', 'type', 'highlight' => null])

{{--
    One activity of a programme as a tile (resources/css/ui/activity-tile.css):
    its first photo (the rest wait behind it for the lightbox), title, date,
    who ran it, a short opening of its text and "Read More", with a small video
    icon when it has one. The row layout of the same activity is
    x-site.activity-card; the two read the activity the same way.
--}}
@php
    $content = $activity->content;
    $photos = $activity->initiativeImages;
    $excerpt = strlen($content?->content ?? '') > 30
        ? implode(' ', array_slice(explode(' ', strip_tags($content->content)), 0, 18)).'...'
        : null;
    $date = $content?->date_string !== '0' ? $content?->date_string : null;
    $organization = $content?->organization !== '0' ? $content?->organization : null;
    $href = '/projects/'.$type.'/'.$activity->id.'-'.Str::slug(strtolower((string) $content?->title));
@endphp

<article {{ $attributes->class('activity-tile') }}>
    <div class="activity-tile__photos">
        @foreach ($photos as $i => $photo)
            <a href="{{ substr($photo->path, 0, -4).'b.jpg' }}" data-lightbox="activity-tile-{{ $activity->id }}"
               data-caption="{{ ucfirst((string) $content?->title) }}" @if ($i > 0) hidden @endif>
                <img src="{{ $photo->path }}" alt="{{ $content?->title }}" loading="lazy">
            </a>
        @endforeach
    </div>

    <div class="activity-tile__body">
        <div class="activity-tile__head">
            <h3 class="activity-tile__title">{{ \App\Support\Highlight::text($content?->title, $highlight) }}</h3>
            @if ($activity->video !== '0')
                <a href="{{ $activity->video }}" data-lightbox="video-tile-{{ $activity->id }}" aria-label="Video">
                    <img class="activity-tile__video" src="/images/ico/video-ini-icon.svg" alt="">
                </a>
            @endif
        </div>

        @if ($date || $organization)
            <div class="activity-tile__meta">
                @if ($date)<span><strong>Date:</strong> {{ \App\Support\Highlight::text($date, $highlight) }}</span>@endif
                @if ($organization)<span><strong>{{ __('initiatives.by') }}{{ \App\Support\Highlight::text($organization, $highlight) }}</strong></span>@endif
            </div>
        @endif

        @if ($excerpt)
            <p class="activity-tile__excerpt">{{ \App\Support\Highlight::text($excerpt, $highlight) }}</p>
        @endif

        <a class="activity-tile__more" href="{{ $href }}">{{ __('main.readMore') }}</a>
    </div>
</article>
