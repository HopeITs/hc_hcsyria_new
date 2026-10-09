@props(['i', 'slide' => false])

{{-- One slide. :slide makes a change of slide move sideways; otherwise it just switches. --}}
<div {{ $attributes->class(['carousel__item', 'carousel__item--slide' => $slide, 'is-active' => $i === 0]) }}
     :class="item({{ $i }})">{{ $slot }}</div>
