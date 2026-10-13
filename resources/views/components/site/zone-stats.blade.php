@props(['figures'])

{{--
    The Study Zone statistics (resources/css/ui/zone-stats.css): the map of
    Syria with the entries per city, the three most common fields of study
    as radial bars, and the split by gender as two rings. $figures is
    App\Queries\StudyZoneStatisticsQuery::figures(). The quarterly note under
    the map is legacy's: the figures cover the last three calendar months.

    The rings are labelled by the gender in the records. The legacy page
    labelled them by size, calling whichever share was larger "Male".
--}}
@php
    $education = array_pad($figures['education'], 3, ['name' => '', 'count' => 0]);

    // Innermost first: the third most common field, then the second, then the first.
    $bars = [
        ['key' => 'third', 'label' => $education[2]['name'], 'color' => '#F99D1C'],
        ['key' => 'second', 'label' => $education[1]['name'], 'color' => '#023c54'],
        ['key' => 'first', 'label' => $education[0]['name'], 'color' => '#F99D1C'],
    ];

    // The chart sizes itself to its box, which is 13rem tall on tablets and small laptops and 13.5rem on phones.
    $heights = ['tablet' => 208, 'phone' => 216];

    $sets = [['third' => $education[2]['count'], 'second' => $education[1]['count'], 'first' => $education[0]['count']]];

    $genders = [
        ['label' => 'Male', 'share' => $figures['gender']['male'], 'color' => '#F99D1C', 'class' => 'zone-stats__gender--male'],
        ['label' => 'Female', 'share' => $figures['gender']['female'], 'color' => '#063c54', 'class' => 'zone-stats__gender--female'],
    ];
@endphp

<x-ui.reveal :ms="800" id="statistics-section" class="zone-stats">
    <div class="px-3">
        <x-ui.reveal :ms="600" class="-mx-3 flex">
            <div class="flex w-full flex-col items-center justify-center px-3">
                <x-ui.section-title class="mt-4 pb-0">Statistics</x-ui.section-title>
            </div>
        </x-ui.reveal>
    </div>

    <div class="zone-stats__band">
        <div class="px-3">
            <div class="-mx-3 flex flex-wrap justify-center md:justify-center lg:justify-end">
                <div class="relative flex w-full flex-col items-end justify-center px-3 md:w-1/2">
                    <div class="zone-stats__title">Number of Students' Entries Around Syria</div>
                    <x-site.zone-map :entries="$figures['entries']"/>
                    <div class="zone-stats__note"><div><span>Notice:</span> these statistics are updated quarterly</div></div>
                </div>

                <div class="zone-stats__right flex w-full flex-col items-center justify-between px-3 sm:w-5/12 md:w-1/2">
                    <div class="zone-stats__title zone-stats__title--centred">Top 3 Education Fields</div>

                    <div x-data="radialBars(@js(['bars' => App\Support\RadialBars::variants($bars, 280, $heights), 'sets' => $sets, 'max' => 10000]))" class="zone-stats__education">
                        <x-ui.radial-bars :bars="$bars" :height="280" :$heights/>
                    </div>

                    <div class="-mx-3 flex w-full flex-wrap justify-center">
                        <div class="w-full px-3 pb-4">
                            <div class="zone-stats__title zone-stats__title--centred">Students' Distribution by Gender</div>
                        </div>

                        @foreach ($genders as $gender)
                            <div class="relative flex w-1/2 justify-center px-3 sm:w-1/4 md:w-1/3 xl:w-1/4">
                                <div class="zone-stats__gauge">
                                    <x-ui.radial-gauge :value="$gender['share']" :of="100" :color="$gender['color']" track="#e6e6e6" :thickness="30" from="top"/>
                                </div>
                                <div class="zone-stats__share {{ $gender['class'] }}">
                                    <div class="zone-stats__percent">{{ (int) $gender['share'] }} <span>{{ substr(number_format($gender['share'], 2), -3) }} %</span></div>
                                    <div class="zone-stats__label">{{ $gender['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-ui.reveal>
