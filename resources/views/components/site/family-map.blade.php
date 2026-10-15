@props(['places', 'tiles'])

{{--
    "Distribution Locations": a map of Aleppo with a marker for each place
    the programme gives out aid (resources/js/family-map.js,
    resources/css/ui/family-map.css). $places are FamilyPartner rows (name and
    coordinates); a marker's popup is its name, built from text.
--}}
@php
    $markers = $places->map(fn ($place) => [
        'name' => (string) $place->name,
        'lat' => (float) $place->location_latitude,
        'lng' => (float) $place->location_longtitude,
    ])->values();
@endphp

<div class="family-map-section">
    <div class="px-3">
        <x-ui.reveal :ms="600" id="map-section" class="family-map section-gap">
            <div class="flex justify-center">
                <x-ui.section-title>Distribution Locations</x-ui.section-title>
            </div>
            <x-ui.red-rule short/>

            <x-ui.reveal :ms="600">
                <div class="family-map__map" x-data="familyMap(@js(['markers' => $markers, 'tiles' => $tiles]))"></div>
            </x-ui.reveal>
        </x-ui.reveal>
    </div>
</div>
