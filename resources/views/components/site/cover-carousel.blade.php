@props(['images', 'phone' => false])

{{--
    The cover photo(s) under the hero title (legacy partials/cover-carousel).
    Each photo has the torn edge along its bottom. With more than one it
    slides every five seconds, with arrows and dots; phones also get the
    whole thing rising into view.
--}}
@php
    $count = $images->count();
    $rise = $phone ? ['x-reveal' => true, 'class' => 'reveal', 'style' => '--reveal-ms: 800ms'] : [];
@endphp

<x-ui.carousel :count="$count" :interval="5000" {{ $attributes->merge(['class' => 'cover-carousel'])->merge($rise) }}>
    <x-ui.carousel.track class="cover-carousel__track">
        @foreach ($images as $i => $image)
            <x-ui.carousel.item :i="$i" slide class="relative">
                <img class="block h-full w-full object-cover" src="{{ $image->image_path }}" alt="">
                <img class="torn-edge" src="/images/home/image-mask.png" alt="">
            </x-ui.carousel.item>
        @endforeach

        @if ($count > 1)
            <x-ui.carousel.controls/>
        @endif
    </x-ui.carousel.track>

    @if ($count > 1)
        <x-ui.carousel.dots :count="$count"/>
    @endif
</x-ui.carousel>
