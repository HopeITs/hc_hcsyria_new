{{--
    "Map Locations": a map with a pin for every project of the filtered list
    that has a position (resources/css/ui/project-map.css,
    resources/js/project-map.js). The map is the browser's: Livewire leaves
    it alone and the pins are fetched from the list ($wire.markers()) when it
    starts and each time Apply changes the filters.
--}}
<x-ui.reveal :ms="800" id="map-section" class="project-map">
    <x-ui.reveal :ms="600" class="project-map__heading">
        <x-ui.section-title class="project-map__title">Map Locations</x-ui.section-title>
    </x-ui.reveal>
    <x-ui.red-rule short/>
    <div wire:ignore class="project-map__canvas" x-data="projectMap(@js(['tiles' => config('hcsyria.map_tiles.url').'?api_key='.config('hcsyria.map_tiles.key')]))"
         x-ref="map" @projects-filtered.window="load()"></div>
</x-ui.reveal>
