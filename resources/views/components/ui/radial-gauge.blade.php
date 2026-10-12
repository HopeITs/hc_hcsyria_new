@props(['value', 'of'])

{{--
    A ring that fills clockwise from nine o'clock, value / of of the way
    round, over a grey track (resources/css/ui/radial-gauge.css). Replaces the
    Chart.js radial-gauge plugin: same ring, same round ends, same 1.5s fill,
    without a charting library. The ring is drawn the size of its box.
--}}
@php
    $share = $of > 0 ? min(max($value / $of, 0), 1) : 0;
@endphp

<svg {{ $attributes->class('radial-gauge') }} viewBox="0 0 100 100" aria-hidden="true" focusable="false">
    <circle class="radial-gauge__track" cx="50" cy="50" r="44.1"/>
    <circle class="radial-gauge__fill" cx="50" cy="50" r="44.1" pathLength="100"
            style="--gauge-share: {{ round($share * 100, 3) }}"/>
</svg>
