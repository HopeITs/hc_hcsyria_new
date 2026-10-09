@props(['count'])

{{-- One dot per slide, under the carousel (put inside the carousel, after the track). --}}
<div {{ $attributes->class('carousel__dots') }}>
    @for ($i = 0; $i < $count; $i++)
        <button type="button" @class(['carousel__dot', 'is-active' => $i === 0]) :class="{ 'is-active': index === {{ $i }} }"
                @click="go({{ $i }})" aria-label="Slide {{ $i + 1 }}"></button>
    @endfor
</div>
