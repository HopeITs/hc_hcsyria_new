@props(['label', 'value', 'hint' => null, 'tone' => null, 'icon' => null, 'href' => null, 'meter' => null])

{{--
    One figure on the dashboard. $tone gives the number its colour (good, warn,
    crit) and $meter draws a thin bar under it, for a share out of a whole.
--}}
@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class(['tile', $tone]) }}>
    <div class="top">
        <div>
            <div class="l">{{ $label }}</div>
            <div class="v">{{ $value }}</div>
        </div>
        @if ($icon)<div class="ic"><x-admin.icon :name="$icon" /></div>@endif
    </div>
    @if ($meter !== null)
        <span class="meter" role="presentation"><i style="width: {{ max(0, min(100, (float) $meter)) }}%"></i></span>
    @endif
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
</{{ $tag }}>
