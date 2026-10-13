@props(['bars', 'height' => 296, 'heights' => []])

{{--
    Concentric bars, innermost first, each filling clockwise from twelve
    o'clock over a pale track (resources/css/ui/radial-bars.css). It draws
    inside an Alpine scope made by radialBars() (resources/js/radial-bars.js),
    which knows the figures; this only lays the rings out.

    Each bar is ['key', 'label', 'color']. :height is the box the rings sit
    in, in pixels; the rings fill 0.4 of it from the centre, 80 across in
    all, as the amCharts radar chart did, and the labels stand to the left of
    twelve o'clock, each in its bar's colour. That chart sized itself to its
    box, and a page's box can change with the window: :heights is then a map
    of size name => height ('tablet' => 208), each drawn separately and shown
    where resources/css/ui/radial-bars.css says (tablet, phone).
--}}
@php
    $sizes = ['' => $height] + $heights;
@endphp

<div {{ $attributes->class('radial-bars') }} x-ref="chart">
    @foreach ($sizes as $name => $size)
        @php
            $outer = 0.4 * $size;
            $rings = App\Support\RadialBars::rings($bars, $size);
        @endphp
        <svg @class(['radial-bars__svg', 'radial-bars__svg--'.$name => $name !== '']) viewBox="{{ -$outer }} {{ -$size / 2 }} {{ 2 * $outer }} {{ $size }}"
             style="--bar-width: {{ App\Support\RadialBars::thickness(count($bars)) }}" aria-hidden="true">
            @foreach ($rings as $ring)
                <g style="--cap: {{ $ring['cap'] }}">
                    <circle class="radial-bars__track" cx="0" cy="0" r="{{ $ring['radius'] }}" pathLength="100"
                            style="stroke-dasharray: {{ 75 - 2 * $ring['cap'] }} 100"/>
                    <circle class="radial-bars__bar" cx="0" cy="0" r="{{ $ring['radius'] }}" pathLength="100"
                            stroke="{{ $ring['color'] }}"
                            :style="`stroke-dasharray: ${length('{{ $ring['key'] }}', '{{ $name }}')} 100`"
                            @mousemove="point($event, '{{ $ring['key'] }}')" @mouseleave="leave()"/>
                    <text class="radial-bars__label" x="-10" y="{{ 5 - $ring['radius'] }}" text-anchor="end"
                          fill="{{ $ring['color'] }}">{{ $ring['label'] }}</text>
                </g>
            @endforeach
        </svg>
    @endforeach

    <div class="radial-bars__tip" x-show="tip" x-cloak
         :style="tip && `left: ${tip.x}px; top: ${tip.y}px; background: ${tip.color}`" x-text="tip?.text"></div>
</div>
