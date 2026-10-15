@props(['ms' => 400, 'as' => 'div'])

{{--
    Fades in, rising 100px, as it scrolls into view — the legacy
    data-aos="fade-up" data-aos-duration="{ms}". The hidden state is in the
    markup so nothing flashes before resources/js/reveal.js starts. Inside a
    Livewire component its own attributes are left alone (wire:ignore.self): the
    revealed class is the browser's, and an update would otherwise hide the
    section again.
--}}
<{{ $as }} {{ $attributes->class('reveal')->merge(['style' => "--reveal-ms: {$ms}ms"]) }} wire:ignore.self x-reveal>
    {{ $slot }}
</{{ $as }}>
