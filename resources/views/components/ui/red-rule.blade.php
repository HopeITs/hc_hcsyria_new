@props(['short' => false, 'ms' => null])

{{--
    The red bar between sections (resources/css/ui/red-rule.css). :ms makes it
    rise into view as it scrolls on, as the legacy ones that carried data-aos did.
--}}
@if ($ms !== null)
    <x-ui.reveal :ms="$ms" {{ $attributes->class(['red-rule', 'red-rule--short' => $short]) }}/>
@else
    <div {{ $attributes->class(['red-rule', 'red-rule--short' => $short]) }}></div>
@endif
