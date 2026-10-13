@props(['value', 'of', 'color' => '#bc1818', 'track' => '#bababa', 'thickness' => 18, 'from' => 'left'])

{{--
    A ring that fills clockwise, value / of of the way round, over a track
    (resources/css/ui/radial-gauge.css). Replaces the Chart.js radial-gauge
    plugin: same ring, same round ends, same 1.5s fill, without a charting
    library. The ring is drawn the size of its box.

    :thickness is the ring's width as a percentage of its radius (Chart.js's
    100 - centerPercentage); :from is where the fill starts, 'left' (nine
    o'clock) or 'top'.
--}}
@php
    $share = $of > 0 ? min(max($value / $of, 0), 1) : 0;
    $width = round(48.5 * $thickness / 100, 2);
    $radius = round(48.5 - $width / 2, 2);
@endphp

<svg {{ $attributes->class('radial-gauge') }} viewBox="0 0 100 100" aria-hidden="true" focusable="false"
     style="--gauge-color: {{ $color }}; --gauge-track: {{ $track }}; --gauge-width: {{ $width }}; --gauge-turn: {{ $from === 'top' ? '-90deg' : '180deg' }}">
    <circle class="radial-gauge__track" cx="50" cy="50" r="{{ $radius }}"/>
    <circle class="radial-gauge__fill" cx="50" cy="50" r="{{ $radius }}" pathLength="100"
            style="--gauge-share: {{ round($share * 100, 3) }}"/>
</svg>
