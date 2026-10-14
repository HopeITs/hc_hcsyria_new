@props(['view' => 'tiles'])

{{--
    The "View" box that changes a list between tiles and rows
    (resources/css/ui/view-switch.css, resources/js/view-switch.js). The list
    is a Livewire component with a `view` property; both layouts are on the
    page, so the switch is instant and the address follows (?view=grid).
--}}
<div {{ $attributes->class('view-switch') }} x-data="viewSwitch">
    <label>View</label>
    <img :src="`/images/ico/${$wire.view === 'tiles' ? 'tiles-active' : 'tiles'}.png`" src="/images/ico/{{ $view === 'tiles' ? 'tiles-active' : 'tiles' }}.png"
         alt="Tiles" role="button" class="view-switch__tiles" @click="set('tiles')">
    <img :src="`/images/ico/${$wire.view === 'grid' ? 'grid-active' : 'grid'}.png`" src="/images/ico/{{ $view === 'grid' ? 'grid-active' : 'grid' }}.png"
         alt="List" role="button" class="view-switch__grid" @click="set('grid')">
</div>
