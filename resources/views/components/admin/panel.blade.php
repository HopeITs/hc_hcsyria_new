@props(['title' => null, 'count' => null])

<section {{ $attributes->class('panel') }}>
    @if ($title !== null || isset($toolbar))
        <div class="panel-h">
            @if ($title !== null)<h2>{{ $title }}@if ($count !== null)<span class="badge">{{ $count }}</span>@endif</h2>@endif
            @isset($toolbar){{ $toolbar }}@endisset
        </div>
    @endif
    {{ $slot }}
</section>
