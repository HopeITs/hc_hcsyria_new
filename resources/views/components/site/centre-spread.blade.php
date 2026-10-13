@props(['groups', 'initial', 'tiles'])

{{--
    "Our Spread": the programme's centres by town on the left, the chosen
    centre's details and a map beside them (resources/css/ui/centre-spread.css,
    resources/js/centre-spread.js). $groups is a list of ['name', 'centres'];
    each centre is ['id', 'name', 'startDate', 'active', 'address', 'location',
    'telephone', 'email', 'facebook', 'instagram', 'hours' and 'hoursRecord' (HTML: the hours once a centre is chosen, and as the record has them), 'hoursText',
    'latitude', 'longitude', 'place']. $initial is the id of the centre shown
    first, $tiles the map tile address.
--}}
@php
    $all = collect($groups)->flatMap(fn ($group) => $group['centres'])->values();
    $first = $all->firstWhere('id', $initial) ?? $all->first();
@endphp

<div id="spread-section" class="spread" x-data="centreSpread(@js(['centres' => $all->all(), 'selected' => $first['id'], 'tiles' => $tiles]))">
    <div class="px-3">
        <div class="-mx-3 flex">
            <div class="flex grow flex-col items-center justify-center px-3">
                <x-ui.section-title class="mt-1 pb-0">Our Spread</x-ui.section-title>
            </div>
        </div>
    </div>

    <div class="px-3">
        <x-ui.reveal :ms="1000" class="pb-4">
            <div class="spread__background">
                <div class="-mx-3 flex w-full flex-wrap">
                    <div class="w-full sm:w-1/4">
                        <ul class="spread__list">
                            @foreach ($groups as $group)
                                <div class="spread__town">{{ $group['name'] }}</div>
                                @foreach ($group['centres'] as $centre)
                                    <li :class="{ 'is-selected': selected === {{ $centre['id'] }} }" @if ($centre['active']) @click="choose(centres.find((c) => c.id === {{ $centre['id'] }}))" @endif>
                                        <div @class(['spread__name flex justify-between', 'is-closed' => ! $centre['active']])>{{ $centre['name'] }}
                                            <span @class(['spread__status', 'is-closed' => ! $centre['active']])>( {{ $centre['startDate'] }} - {{ $centre['active'] ? 'Present )' : 'Closed )' }}</span>
                                        </div>
                                    </li>
                                @endforeach
                            @endforeach
                            <div class="spread__socials">
                                <a href="https://www.facebook.com/syriasz" target="_blank" rel="noopener" aria-label="Facebook"><span class="site-footer__social spread__social"><x-ui.icon name="fa-facebook"/></span></a>
                                <a href="https://www.instagram.com/study.zone.syria" aria-label="Instagram"><span class="site-footer__social spread__social"><x-ui.icon name="fa-instagram"/></span></a>
                            </div>
                        </ul>
                    </div>

                    <div class="w-full sm:w-3/4">
                        <div class="spread__panel">
                            <div class="-mx-3 flex flex-wrap">
                                <div class="flex w-full items-center justify-start px-3 sm:w-1/2">
                                    <div class="spread__info">
                                        <ul>
                                            <div class="spread__town spread__town--info" x-text="centre.place">{{ $first['place'] }}</div>
                                            <li><div class="spread__name" x-text="centre.name">{{ $first['name'] }}</div></li>
                                        </ul>
                                        <div class="mb-4"><span>Working Hours: </span><span class="spread__hours" x-html="hours">{!! $first['hoursRecord'] !!}</span></div>
                                        <div class="mb-4"><span>Tel: </span><a target="_blank" :href="`tel:${centre.telephone}`" x-text="centre.telephone" href="tel:{{ $first['telephone'] }}">{{ $first['telephone'] }}</a></div>
                                        <div class="mb-4"><span>Email: </span><a target="_blank" :href="`mailto:${centre.email}`" x-text="centre.email" href="mailto:{{ $first['email'] }}">{{ $first['email'] }}</a></div>
                                        <div class="mb-4"><div><a target="_blank" :href="centre.location" x-text="centre.address" href="{{ $first['location'] }}">{{ $first['address'] }}</a></div></div>
                                        <div class="mb-4 font-bold"><a target="_blank" :href="`https://${centre.facebook}`" x-text="centre.facebook" href="https://{{ $first['facebook'] }}">{{ $first['facebook'] }}</a></div>
                                        <div class="mb-4 font-bold"><a target="_blank" :href="`https://${centre.instagram}`" x-text="centre.instagram" href="https://{{ $first['instagram'] }}">{{ $first['instagram'] }}</a></div>
                                    </div>
                                </div>

                                <div class="spread__map w-full px-3 sm:w-1/2">
                                    <div class="spread__map-box" x-ref="map"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.reveal>
    </div>
</div>
