@props(['label', 'value', 'hint' => null, 'tone' => null, 'icon' => null, 'href' => null])

@php $tag = $href ? 'a' : 'div'; @endphp
<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif {{ $attributes->class(['tile', $tone]) }}>
    <div class="top">
        <div>
            <div class="l">{{ $label }}</div>
            <div class="v">{{ $value }}</div>
        </div>
        @if ($icon)<div class="ic"><x-admin.icon :name="$icon" /></div>@endif
    </div>
    @if ($hint)<div class="hint">{{ $hint }}</div>@endif
</{{ $tag }}>
