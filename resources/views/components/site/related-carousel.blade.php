@props(['items'])

{{--
    "Related posts" (legacy: an Owl carousel, resources/js/related-carousel.js,
    resources/css/ui/related-carousel.css): tiles of another activity's photo
    and title, two at a time, with arrows at the sides and a dot for each pair.
    $items: rows of href, image and title. The stage repeats the last two tiles
    before the first and the first two after the last, so the loop slides on.
--}}
@php
    $count = count($items);
    $stage = $count > 2 ? [...array_slice($items, -2), ...$items, ...array_slice($items, 0, 2)] : $items;
@endphp

<div {{ $attributes->class('related-carousel') }} x-data="relatedCarousel(@js(['count' => $count]))">
    <div class="related-carousel__window">
        <div class="related-carousel__stage" :class="{ 'is-jumping': jumping }"
             style="--total: {{ count($stage) }}; --offset: 0" :style="{ '--offset': offset }">
            @foreach ($stage as $item)
                <div class="related-carousel__slot">
                    <a class="related-carousel__item" href="{{ $item['href'] }}">
                        <div class="related-carousel__photo">
                            @if ($item['image'])
                                <img src="{{ $item['image'] }}" alt="">
                            @endif
                        </div>
                        <div class="related-carousel__title"><div>{{ $item['title'] }}</div></div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    <div class="related-carousel__nav">
        <button type="button" class="related-carousel__arrow related-carousel__arrow--prev" @click="move(-1)" aria-label="Previous"><x-ui.icon name="fa-angle-left"/></button>
        <button type="button" class="related-carousel__arrow related-carousel__arrow--next" @click="move(1)" aria-label="Next"><x-ui.icon name="fa-angle-right"/></button>
    </div>

    <div class="related-carousel__dots">
        @for ($i = 0; $i < ceil($count / 2); $i++)
            <button type="button" class="related-carousel__dot" :class="{ 'is-active': page === {{ $i }} }" @click="to({{ $i }})" aria-label="Page {{ $i + 1 }}"><span></span></button>
        @endfor
    </div>
</div>
