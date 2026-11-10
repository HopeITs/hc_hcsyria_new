@props(['title' => null, 'count' => null, 'icon' => null])

<section {{ $attributes->class('panel') }}>
    @if ($title !== null || isset($toolbar))
        <div class="panel-h">
            @if ($title !== null)<h2>@if ($icon)<x-admin.icon :name="$icon" />@endif{{ $title }}@if ($count !== null)<span class="badge">{{ $count }}</span>@endif</h2>@endif
            @isset($toolbar){{ $toolbar }}@endisset
        </div>
    @endif
    {{ $slot }}
</section>
