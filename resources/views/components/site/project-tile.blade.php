@props(['project'])

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
        <div class="card-title">{{ $presenter->tileTitle() }}</div>
        <div class="card-subtitle"><span>{{ implode(', ', $presenter->people()) }}</span></div>
        <div class="project-card__foot">
            <div class="card-content">{{ $presenter->city() }} , {{ $presenter->donor() }}</div>
            <div class="project-card__actions">
                <x-site.project-actions :$presenter layout="tile"/>
            </div>
        </div>
    </div>
</div>
