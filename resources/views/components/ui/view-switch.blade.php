@props(['view' => 'tiles'])

{{--
    The "View" box that changes a list between tiles and rows
    (resources/css/ui/view-switch.css, resources/js/view-switch.js). The list
    is a Livewire component with a `view` property; both layouts are on the
    page, so the switch is instant and the address follows (?view=grid).

    The two icons are drawn here, grey and, for the open view, in the page's
    accent colour (red on micro-projects, the programme's blue on Study Zone
    and DISC), where legacy had a red and a grey picture of each.
--}}
<div {{ $attributes->class('view-switch') }} x-data="viewSwitch">
    <label>View</label>
    <svg viewBox="0 0 136 97" role="button" aria-label="Tiles" @class(['view-switch__icon', 'view-switch__tiles', 'is-active' => $view === 'tiles'])
         :class="{ 'is-active': $wire.view === 'tiles' }" @click="set('tiles')">
        @foreach ([0, 36.5, 73] as $y)
            @foreach ([0, 50, 100] as $x)
                <rect x="{{ $x }}" y="{{ $y }}" width="36" height="24"/>
            @endforeach
        @endforeach
    </svg>
    <svg viewBox="0 0 135 97" role="button" aria-label="List" @class(['view-switch__icon', 'view-switch__grid', 'is-active' => $view === 'grid'])
         :class="{ 'is-active': $wire.view === 'grid' }" @click="set('grid')">
        <rect x="0" y="0" width="27" height="24"/><rect x="41" y="0" width="94" height="24"/>
        <rect x="0" y="36.5" width="94" height="24"/><rect x="110" y="36.5" width="25" height="24"/>
        <rect x="0" y="73" width="27" height="24"/><rect x="41" y="73" width="94" height="24"/>
    </svg>
</div>
