@props(['project', 'highlight' => null])

{{--
    A project as a tile: its first photo (the rest wait behind it for the
    lightbox), name, who it is for, where and by whom it was funded, and the
    icons (resources/css/ui/project-card.css).
--}}
@php($presenter = new \App\Support\ProjectPresenter($project))
<div {{ $attributes->class('project-tile') }}>
    <div class="project-card">
        <div class="project-card__photos">
            @foreach ($presenter->photos() as $i => $photo)
                <a href="{{ $photo['large'] }}" data-lightbox="tile-{{ $project->id }}" data-caption="{{ $presenter->heading() }}" @if ($i > 0) hidden @endif>
                    <img src="{{ $photo['src'] }}" alt="{{ $presenter->heading() }}" loading="lazy">
                </a>
            @endforeach
        </div>
        <div class="card-title">{{ \App\Support\Highlight::text($presenter->tileTitle(), $highlight) }}</div>
        <div class="card-subtitle"><span>{{ \App\Support\Highlight::text(implode(', ', $presenter->people()), $highlight) }}</span></div>
        <div class="project-card__foot">
            <div class="card-content">{{ \App\Support\Highlight::text($presenter->city(), $highlight) }} , {{ \App\Support\Highlight::text($presenter->donor(), $highlight) }}</div>
            <div class="project-card__actions">
                <x-site.project-actions :$presenter layout="tile"/>
            </div>
        </div>
    </div>
</div>
