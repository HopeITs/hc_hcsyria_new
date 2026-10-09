@props(['ms' => 400, 'as' => 'div'])

{{--
    Fades in, rising 100px, as it scrolls into view — the legacy
    data-aos="fade-up" data-aos-duration="{ms}". The hidden state is in the
    markup so nothing flashes before resources/js/reveal.js starts.
--}}
<{{ $as }} {{ $attributes->class('reveal')->merge(['style' => "--reveal-ms: {$ms}ms"]) }} x-reveal>
    {{ $slot }}
</{{ $as }}>
