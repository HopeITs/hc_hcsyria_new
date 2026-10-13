@props(['entries'])

{{--
    The map of Syria in the Study Zone statistics: the five governorates with
    centres in a darker grey, and beside each a label saying how many
    students entered the Study Zones there, joined to it by a dotted line
    (resources/css/ui/zone-map.css). The labels and lines are hidden until the
    page has been scrolled past the zone buttons, then fade and slide in, as
    on the legacy page (resources/js/zone-map.js).

    :entries is the entries per city, keyed by lower-case name (aleppo ...).
--}}
@php
    // Each city: its label, and the dotted line from the label to the map (a 10.8-high svg).
    $cities = [
        'aleppo' => ['name' => 'Aleppo', 'viewBox' => '0 0 208.1 10.8', 'path' => 'M1,4.9h2.5v1H1v-1ZM18,4.9h4.9v1h-4.9v-1ZM8.3,4.9h4.9v1h-4.9v-1ZM66.6,4.9h4.9v1h-4.9v-1ZM56.9,4.9h4.9v1h-4.9v-1ZM47.2,4.9h4.9v1h-4.9v-1ZM76.4,4.9h4.9v1h-4.9v-1ZM27.8,4.9h4.9v1h-4.9v-1ZM183.3,4.9h4.9v1h-4.9v-1ZM193,4.9h4.9v1h-4.9v-1ZM86.1,4.9h4.9v1h-4.9v-1ZM173.6,4.9h4.9v1h-4.9v-1ZM37.5,4.9h4.9v1h-4.9v-1ZM134.7,4.9h4.9v1h-4.9v-1ZM144.4,4.9h4.9v1h-4.9v-1ZM154.1,4.9h4.9v1h-4.9v-1ZM163.9,4.9h4.9v1h-4.9v-1ZM125,4.9h4.9v1h-4.9v-1ZM105.5,4.9h4.9v1h-4.9v-1ZM95.8,4.9h4.9v1h-4.9v-1ZM115.3,4.9h4.9v1h-4.9v-1ZM205.1,2.4c1.7,0,3,1.3,3,3s-1.3,3-3,3-3-1.3-3-3,1.3-3,3-3Z'],
        'homs' => ['name' => 'Homs', 'viewBox' => '0 0 269.8 10.8', 'path' => 'M0,4.9h2.5v1H0v-1ZM17.3,4.9h4.9v1h-4.9v-1ZM7.4,4.9h4.9v1h-4.9v-1ZM116.1,4.9h4.9v1h-4.9v-1ZM96.4,4.9h4.9v1h-4.9v-1ZM126,4.9h4.9v1h-4.9v-1ZM76.6,4.9h4.9v1h-4.9v-1ZM106.3,4.9h4.9v1h-4.9v-1ZM86.5,4.9h4.9v1h-4.9v-1ZM27.2,4.9h4.9v1h-4.9v-1ZM46.9,4.9h4.9v1h-4.9v-1ZM56.8,4.9h4.9v1h-4.9v-1ZM66.7,4.9h4.9v1h-4.9v-1ZM37,4.9h4.9v1h-4.9v-1ZM135.9,4.9h4.9v1h-4.9v-1ZM234.8,4.9h4.9v1h-4.9v-1ZM215,4.9h4.9v1h-4.9v-1ZM205.1,4.9h4.9v1h-4.9v-1ZM244.7,4.9h4.9v1h-4.9v-1ZM254.6,4.9h4.9v1h-4.9v-1ZM224.9,4.9h4.9v1h-4.9v-1ZM155.7,4.9h4.9v1h-4.9v-1ZM145.8,4.9h4.9v1h-4.9v-1ZM165.6,4.9h4.9v1h-4.9v-1ZM185.4,4.9h4.9v1h-4.9v-1ZM195.2,4.9h4.9v1h-4.9v-1ZM175.5,4.9h4.9v1h-4.9v-1ZM266.8,2.4c-1.7,0-3,1.3-3,3s1.4,3,3,3,3-1.4,3-3-1.4-3-3-3Z'],
        'tartus' => ['name' => 'Tartus', 'viewBox' => '0 0 103.7 10.8', 'path' => 'M19.6,4.9h4.9v1h-4.9v-1ZM29.5,4.9h4.9v1h-4.9v-1ZM39.3,4.9h4.9v1h-4.9v-1ZM0,4.9h4.9v1H0v-1ZM9.8,4.9h4.9v1h-4.9v-1ZM49.2,4.9h4.9v1h-4.9v-1ZM78.7,4.9h4.9v1h-4.9v-1ZM88.5,4.9h4.9v1h-4.9v-1ZM59,4.9h4.9v1h-4.9v-1ZM68.8,4.9h4.9v1h-4.9v-1ZM100.7,2.4c-1.7,0-3,1.3-3,3s1.4,3,3,3,3-1.4,3-3-1.4-3-3-3Z'],
        'latakia' => ['name' => 'Latakia', 'viewBox' => '0 0 81.6 10.8', 'path' => 'M27.1,4.9h4.9v1h-4.9v-1ZM17.3,4.9h4.9v1h-4.9v-1ZM36.9,4.9h4.9v1h-4.9v-1ZM7.4,4.9h4.9v1h-4.9v-1ZM66.5,4.9h4.9v1h-4.9v-1ZM46.8,4.9h4.9v1h-4.9v-1ZM56.6,4.9h4.9v1h-4.9v-1ZM78.6,2.4c-1.7,0-3,1.3-3,3s1.4,3,3,3,3-1.4,3-3-1.4-3-3-3ZM0,5.9h2.5v-1H0'],
        'damascus' => ['name' => 'Damascus', 'viewBox' => '0 0 172.8 10.8', 'path' => 'M10.1,4.9h4.9v1h-4.9v-1ZM69.1,4.9h4.9v1h-4.9v-1ZM78.9,4.9h4.9v1h-4.9v-1ZM49.4,4.9h4.9v1h-4.9v-1ZM19.9,4.9h4.9v1h-4.9v-1ZM88.8,4.9h4.9v1h-4.9v-1ZM29.8,4.9h4.9v1h-4.9v-1ZM39.6,4.9h4.9v1h-4.9v-1ZM59.3,4.9h4.9v1h-4.9v-1ZM138,4.9h4.9v1h-4.9v-1ZM147.8,4.9h4.9v1h-4.9v-1ZM157.6,4.9h4.9v1h-4.9v-1ZM118.3,4.9h4.9v1h-4.9v-1ZM108.4,4.9h4.9v1h-4.9v-1ZM128.1,4.9h4.9v1h-4.9v-1ZM98.6,4.9h4.9v1h-4.9v-1ZM169.8,2.4c1.7,0,3,1.3,3,3s-1.3,3-3,3-3-1.3-3-3,1.3-3,3-3ZM.8,4.9v1h4.3v-1H.8Z'],
    ];

    $governorates = ['SY-HL' => 0, 'SY-HI' => 0, 'SY-DI' => 0, 'SY-LA' => 0, 'SY-TA' => 0, 'SY-RD' => 0];
@endphp

<div {{ $attributes->class('zone-map') }} x-data="zoneMap" id="citiesMap">
    <x-ui.syria-map light :cities="$governorates" :interactive="false"/>

    @foreach ($cities as $key => $city)
        <div class="zone-map__label zone-map__label--{{ $key }}" :class="{ 'is-shown': shown }">{{ $city['name'] }} | {{ number_format($entries[$key] ?? 0) }} Students</div>
        <svg class="zone-map__line zone-map__line--{{ $key }}" :class="{ 'is-shown': shown }" viewBox="{{ $city['viewBox'] }}" aria-hidden="true">
            <path d="{{ $city['path'] }}"/>
        </svg>
    @endforeach
</div>
