@props(['count', 'interval' => 5000, 'pause' => true, 'keys' => false, 'scope' => true, 'pane' => true])

{{--
    A carousel (resources/js/carousel.js). It owns its slide state unless
    :scope is false, in which case it is a pane inside a scope somebody else
    provides — how two carousels are kept on the same slide. :pane false is
    the scope alone, with no box of its own.
--}}
<div {{ $attributes->class(['carousel' => $pane]) }}
     @if ($scope)
         x-data="carousel(@js(['count' => $count, 'interval' => $interval, 'pause' => $pause, 'keys' => $keys]))"
         @mouseenter="hover(true)" @mouseleave="hover(false)"
         @if ($keys) @keydown.window="arrow($event.key)" @endif
     @endif>
    {{ $slot }}
</div>
