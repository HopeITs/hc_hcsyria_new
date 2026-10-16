@props(['item', 'intro' => false])

{{--
    One line of an aspect of LEAP: a bold label and its text, or its bullets
    (resources/css/pages/leap.css). $intro runs the label into the text where
    the first thing the page shows did, as the item says (`tight`).
--}}
@php($tight = $intro && ($item['tight'] ?? false))

<div>
    @if ($tight)
        <span class="font-black">{{ trim($item['label']) }}</span>{{ trim($item['text']) }}
    @else
    <span class="font-black">{{ $item['label'] }}</span>
    @isset ($item['bullets'])
        <br>
        <div class="leap-aspects__list">
            @foreach ($item['bullets'] as $bullet)
                • {{ $bullet }}@unless ($loop->last)<br>@endunless
            @endforeach
        </div>
    @else
        {{ $item['text'] }}
    @endisset
    @endif
</div>
