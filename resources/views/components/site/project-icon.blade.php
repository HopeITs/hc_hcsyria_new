@props(['name', 'tip' => null, 'copy' => null])

{{--
    One of the small grey icons on a project (video, location, copy, read
    more): it turns dark under the pointer, and says what it does in a
    tooltip. :copy is a project's id; clicking then copies the link that
    opens that project's details (resources/js/project-list.js).
--}}
<img {{ $attributes->class('project-icon') }} src="/images/ico/project-{{ $name }}.svg" alt=""
     x-project-icon="@js(['hover' => "/images/ico/project-{$name}H.svg", 'tip' => $tip, 'copy' => $copy])">
