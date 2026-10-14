@props(['presenter', 'group', 'controls' => true, 'dots' => false, 'slide' => true])

{{--
    A project's photos as a carousel that moves on by itself; each opens the
    larger copy in the lightbox as one of the project's gallery. The dots are
    for the details window.
--}}
@php($photos = $presenter->photos())
<x-ui.carousel :count="count($photos)" {{ $attributes }}>
    <x-ui.carousel.track class="project-photos__track">
        @foreach ($photos as $i => $photo)
            <x-ui.carousel.item :$i :$slide>
                <a href="{{ $photo['large'] }}" data-lightbox="{{ $group }}" data-caption="{{ $presenter->heading() }}" class="project-photo">
                    <img src="{{ $photo['src'] }}" alt="{{ $presenter->heading() }}" loading="lazy">
                </a>
            </x-ui.carousel.item>
        @endforeach
        @if ($controls && count($photos) > 1)
            <x-ui.carousel.controls/>
        @endif
    </x-ui.carousel.track>
    @if ($dots)
        <x-ui.carousel.dots :count="count($photos)"/>
    @endif
</x-ui.carousel>
