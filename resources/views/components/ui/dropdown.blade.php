{{--
    A button that opens a list beneath it (resources/css/ui/dropdown.css),
    the look of the Bootstrap dropdown the legacy filter bar used. The
    button's text goes in the `trigger` slot, the choices in the default one
    as <li><a class="dropdown__item" href="#">...</a></li>; choosing one
    closes the list.
--}}
<div {{ $attributes->class('dropdown') }} x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button" class="dropdown__button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="true">
        <span>{{ $trigger }}</span>
    </button>

    <ul class="dropdown__menu" x-show="open" x-cloak @click="open = false">
        {{ $slot }}
    </ul>
</div>
