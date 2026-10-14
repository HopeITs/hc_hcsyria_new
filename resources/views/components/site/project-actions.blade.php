@props(['presenter', 'layout' => 'tile'])

{{--
    The icons on a project: its video (opens in the lightbox), its position
    on Google Maps, a link to copy, and, on a tile, the button that opens its
    details. Each kind of card had them in its own order, and only the tiles
    and the details window explained them with a tooltip.
--}}
@php
    $project = $presenter->project;
    $order = match ($layout) {
        'row' => ['copy', 'location', 'video'],
        'modal' => ['location', 'copy', 'video'],
        default => ['video', 'location', 'copy', 'more'],
    };
    $tips = $layout !== 'row';
@endphp
@foreach ($order as $icon)
    @if ($icon === 'video' && $presenter->video())
        <a href="{{ $presenter->video() }}" data-lightbox="video-{{ $project->id }}" aria-label="Video">
            <x-site.project-icon name="video" :tip="$tips ? 'video' : null"/>
        </a>
    @elseif ($icon === 'location' && $presenter->hasPosition())
        <a href="{{ $presenter->mapUrl() }}" target="_blank" rel="noopener" aria-label="Location">
            <x-site.project-icon name="location" :tip="$tips ? ($layout === 'modal' ? 'location' : null) : null"/>
        </a>
    @elseif ($icon === 'copy')
        <x-site.project-icon name="copy" :tip="$tips ? 'copy' : null" :copy="$project->id" role="button"/>
    @elseif ($icon === 'more' && $layout === 'tile')
        <button type="button" class="project-more-button" wire:click="open({{ $project->id }})" aria-label="Read more">
            <x-site.project-icon name="readmore" tip="read more"/>
        </button>
    @endif
@endforeach
