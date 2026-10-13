@props(['centres', 'cities', 'max' => 1600])

{{--
    A grey band with the map of Syria on one side: pick a centre on it (or
    leave the first) and its start date and logo show under the map and, when
    there are figures, its learners as radial bars on the other side
    (resources/css/ui/centre-statistics.css; the rings are ui.radial-bars).

    Each centre is ['title' (the heading over the bars), 'year', 'logo',
    'figures'], figures being ['applied' => n, 'completed' => n, 'employed' => n]
    or null when the centre has none. :cities says which governorates pick
    which centre (see ui.syria-map). The bars scale to :max.
--}}
@php
    $measures = [
        ['key' => 'employed', 'label' => 'Employed Learners', 'color' => '#979797'],
        ['key' => 'completed', 'label' => 'Completed Learners', 'color' => '#023c54'],
        ['key' => 'applied', 'label' => 'Applied Learners', 'color' => '#F99D1C'],
    ];

    $hasFigures = collect($centres)->contains(fn ($centre) => collect($centre['figures'] ?? [])->filter(fn ($n) => $n !== null)->isNotEmpty());

    $data = collect($centres)->map(fn ($centre) => [
        'employed' => $centre['figures']['employed'] ?? 0,
        'completed' => $centre['figures']['completed'] ?? 0,
        'applied' => $centre['figures']['applied'] ?? 0,
    ])->all();
@endphp

<x-ui.reveal :ms="800" id="statistics-section" class="centre-stats">
<div x-data="radialBars(@js(['bars' => App\Support\RadialBars::variants($measures, 296), 'sets' => $data, 'max' => $max]))">
    <div class="px-3">
        <x-ui.reveal :ms="600" class="-mx-3 flex">
            <div class="flex w-full flex-col items-center justify-center px-3">
                <x-ui.section-title class="mt-4 pb-0">Statistics</x-ui.section-title>
            </div>
        </x-ui.reveal>
    </div>

    <div class="centre-stats__band">
        <div class="page-margin">
            <div class="px-3">
                <x-ui.reveal :ms="600" class="-mx-3 flex flex-wrap items-center justify-center">
                    <div class="relative flex w-full items-center justify-center px-3 sm:w-1/3 md:w-1/2 lg:w-7/12">
                        <div class="flex min-w-0 flex-col items-center">
                        <div class="centre-stats__map">
                            <x-ui.syria-map :cities="$cities"/>
                        </div>
                @foreach ($centres as $i => $centre)
                            <div class="centre-stats__start centre-stats__start--phone" x-show="pick === {{ $i }}" @if ($i > 0) x-cloak @endif>
                                <div class="-mx-3 flex">
                                    <div class="flex-1 px-3 text-right">
                                        <div class="centre-stats__since">Start Date</div>
                                        {{ $centre['year'] }}
                                    </div>
                                    <div class="flex flex-1 px-3">
                                        <img class="centre-stats__logo" src="{{ $centre['logo'] }}" alt="">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </div>
                    </div>

                    <div class="relative w-full px-3 sm:w-1/3 md:w-1/2 lg:w-5/12">
                        @if ($hasFigures)
                            <div class="-mx-3 flex flex-wrap">
                                @foreach ($centres as $i => $centre)
                                    <div class="centre-stats__heading" x-show="pick === {{ $i }}" @if ($i > 0) x-cloak @endif>{{ $centre['title'] }}</div>
                                @endforeach
                            </div>

                            <x-ui.radial-bars class="centre-stats__chart" :bars="$measures"/>
                        @endif
                    </div>
                </x-ui.reveal>

                @foreach ($centres as $i => $centre)
                    <div class="centre-stats__start centre-stats__start--wide" x-show="pick === {{ $i }}" @if ($i > 0) x-cloak @endif>
                        <div class="-mx-3 flex">
                            <div class="flex-1 px-3 text-right">
                                <div class="centre-stats__since">Start Date</div>
                                {{ $centre['year'] }}
                            </div>
                            <div class="flex flex-1 px-3">
                                <img class="centre-stats__logo" src="{{ $centre['logo'] }}" alt="">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
</x-ui.reveal>
