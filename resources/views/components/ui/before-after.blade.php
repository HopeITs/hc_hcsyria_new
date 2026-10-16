@props(['before', 'after'])

{{--
    A before-and-after slider (resources/js/before-after.js,
    resources/css/ui/before-after.css): the two photos of one place, the first
    left of a handle you drag and the second right of it. Hovering dims the
    photos and names the sides, as legacy's twentytwenty did.
--}}
<div {{ $attributes->class('before-after') }} x-data="beforeAfter" :class="{ 'is-dragging': dragging }">
    <img class="before-after__before" src="{{ $before }}" alt="" :style="{ clipPath: beforeClip }" draggable="false">
    <img class="before-after__after" src="{{ $after }}" alt="" :style="{ clipPath: afterClip }" draggable="false">

    <div class="before-after__overlay">
        <div class="before-after__label before-after__label--before" data-content="Before" x-show="pct >= 0.15"></div>
        <div class="before-after__label before-after__label--after" data-content="After" x-show="pct <= 0.85"></div>
    </div>

    <div class="before-after__handle" :style="{ left: `${pct * 100}%` }" role="slider" aria-label="Before and after" aria-valuemin="0" aria-valuemax="100"
         :aria-valuenow="Math.round(pct * 100)" @pointerdown="start($event)" @pointermove="move($event)" @pointerup="end()" @pointercancel="end()">
        <span class="before-after__arrow before-after__arrow--left"></span>
        <span class="before-after__arrow before-after__arrow--right"></span>
    </div>
</div>
