@props(['title' => false, 'caps' => false])

{{--
    A heading with a thin rule running through it (resources/css/ui/
    rule-heading.css). :title is the large centred section variant; :caps
    uppercases the text.
--}}
<div {{ $attributes->class(['rule-heading', 'rule-heading--title' => $title]) }}>
    <div @class(['uppercase' => $caps])><span>{{ $slot }}</span></div>
</div>
