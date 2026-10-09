@props(['year', 'name', 'text', 'href', 'image'])

{{--
    A programme tile: photo, year on a rule, red name tag, text, read more.
    Markup of the legacy home page's .panel > .item, as one component.
--}}
<div {{ $attributes->class('program-card') }}>
    <div><img class="program-card__photo" src="{{ $image }}" alt=""></div>

    <x-ui.rule-heading>{{ $year }}</x-ui.rule-heading>
    <div class="program-card__name"><div>{{ $name }}</div></div>
    <div class="program-card__text"><div>{{ $text }}</div></div>
    <a href="{{ $href }}" class="program-card__more">read more</a>
</div>
