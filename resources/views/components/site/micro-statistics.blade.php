@props(['cities', 'rate', 'ages', 'categories'])

{{--
    The statistics band of /projects/micro-projects (resources/css/pages/micro-projects.css):
    a photo carousel, the project total and sustainability rate, the
    beneficiaries' ages as ring gauges, the five biggest categories and a
    tile per city. Phones get their own arrangement of the same figures.

    :cities is the project count per city id (MicroProjectStatsQuery::countsByCity).
    Idlib reads "SOON" until it has a project, as on the live site.
--}}
@php
    use App\Support\Percentages;

    $count = fn (int $id) => $cities[$id] ?? 0;
    $total = (int) collect($cities)->sum();
    $idlib = $cities[8] ?? 'SOON';
    $figure = fn ($n) => is_numeric($n) ? number_format((int) $n) : $n;

    $topRow = ['Aleppo' => $count(3), 'Homs' => $count(1), 'Damascus' => $count(2)];
    $bottomRow = ['Latakia' => $count(4), 'Al Wadi' => $count(7), 'Idlib' => $idlib, 'Tartus' => $count(6)];
    $phoneCities = ['Homs' => $count(1), 'Damascus' => $count(2), 'Latakia' => $count(4), 'Wadi' => $count(7), 'Idlib' => $idlib, 'Tartus' => $count(6)];

    // The desktop gauges read as shares of the age total, made to add up to 100;
    // the phone ones as shares of all projects, each rounded on its own.
    $ageShares = Percentages::largestRemainder($ages->pluck('project_count')->map(fn ($n) => (int) $n)->all());
@endphp

{{-- Tablets and desktops --}}
<x-ui.reveal :ms="800" class="micro-stats">
    <div class="px-3">
        <x-ui.reveal :ms="600" class="-mx-3 flex justify-center px-3">
            <h2 class="micro-stats__title">Statistics</h2>
        </x-ui.reveal>

        <div class="micro-stats__body">
            <div class="-mx-3 flex flex-wrap">
                <div class="w-full px-3 md:mb-6 xl:mb-0 xl:w-1/2">
                    <x-ui.carousel :count="8" :interval="5500" class="micro-stats__photos">
                        <x-ui.carousel.track class="micro-stats__track">
                            @for ($i = 0; $i < 8; $i++)
                                <x-ui.carousel.item :i="$i" slide>
                                    <img class="micro-stats__photo" src="/images/home/Micro/new-s{{ $i + 1 }}.jpg" alt="" @contextmenu.prevent>
                                </x-ui.carousel.item>
                            @endfor
                        </x-ui.carousel.track>
                    </x-ui.carousel>
                </div>

                <div class="flex w-full flex-col items-center justify-end gap-4 px-3 md:gap-6 xl:w-1/2">
                    <div class="flex w-full gap-4 md:gap-6">
                        <div class="micro-stats__card micro-stats__card--red">
                            <div class="micro-stats__figure">{{ number_format($total) }}</div>
                            <div class="micro-stats__label">Total Projects in <strong>Syria</strong></div>
                        </div>
                        <div class="micro-stats__card">
                            <div class="micro-stats__figure micro-stats__figure--red">{{ $rate }}%</div>
                            <div class="micro-stats__label micro-stats__label--grey">Project Sustainability rate</div>
                        </div>
                    </div>

                    <div class="micro-stats__ages">
                        <div class="micro-stats__ages-title">Beneficiaries' Age Classification</div>
                        <div class="mt-1 flex w-full justify-around">
                            @foreach ($ages as $i => $age)
                                <div class="flex grow flex-col items-center text-center">
                                    <div class="micro-stats__gauge">
                                        <x-ui.radial-gauge :value="$age->project_count" :of="$total"/>
                                        <div class="micro-stats__gauge-share">
                                            <span class="micro-stats__gauge-number">{{ $ageShares[$i] }}</span>
                                            <span class="micro-stats__gauge-sign">%</span>
                                        </div>
                                    </div>
                                    <div class="micro-stats__gauge-range">{{ $age->Name }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="-mx-3 flex flex-wrap items-center justify-center md:mt-6">
                <div class="w-full px-3 md:w-3/4">
                    <div class="micro-stats__cities micro-stats__cities--three">
                        @foreach ($topRow as $city => $projects)
                            <div class="micro-stats__city">
                                <div class="micro-stats__city-name">{{ $city }}</div>
                                <div class="micro-stats__city-rule"></div>
                                <div class="micro-stats__city-number">{{ $figure($projects) }}</div>
                                <div class="micro-stats__city-projects">Projects</div>
                                @unless ($loop->last)
                                    <div class="micro-stats__city-divider"></div>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="w-1/4 px-3">
                    <div class="micro-stats__categories">
                        <div class="micro-stats__categories-title">Top 5 Project Categories</div>
                        <ol class="micro-stats__categories-list">
                            @foreach ($categories as $category)
                                <li>{{ $category->Name }}</li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </div>

            <div class="-mx-3 flex flex-wrap md:mt-6">
                <div class="micro-stats__cities micro-stats__cities--four w-full px-3">
                    @foreach ($bottomRow as $city => $projects)
                        <div class="micro-stats__city">
                            <div class="micro-stats__city-name">{{ $city }}</div>
                            <div class="micro-stats__city-rule"></div>
                            <div class="micro-stats__city-number">{{ $figure($projects) }}</div>
                            <div class="micro-stats__city-projects">Projects</div>
                            @unless ($loop->last)
                                <div class="micro-stats__city-divider"></div>
                            @endunless
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-ui.reveal>

{{-- Phones --}}
<x-ui.reveal :ms="400" class="micro-stats-phone">
    <div class="-mx-3 flex justify-center px-3">
        <h2 class="micro-stats-phone__title">Statistics</h2>
    </div>

    <div class="micro-stats-phone__grid">
        <x-ui.carousel :count="8" :interval="5000" class="micro-stats__photos">
            <x-ui.carousel.track class="h-[220px]">
                @for ($i = 0; $i < 8; $i++)
                    <x-ui.carousel.item :i="$i" slide>
                        <img class="micro-stats__photo micro-stats__photo--natural" src="/images/home/Micro/new-s{{ $i + 1 }}.jpg" alt="" @contextmenu.prevent>
                    </x-ui.carousel.item>
                @endfor
            </x-ui.carousel.track>
        </x-ui.carousel>

        <div class="micro-stats-phone__pair pt-2 pb-2">
            <div class="micro-stats-phone__card micro-stats__card--red">
                <div class="micro-stats-phone__figure">{{ number_format($total) }}</div>
                <div class="micro-stats-phone__label">Total Projects in <strong>Syria</strong></div>
            </div>
            <div class="micro-stats-phone__card min-h-[30vw] bg-[#f1f1f1]">
                <div class="micro-stats-phone__figure text-brand-600">{{ $rate }}%</div>
                <div class="micro-stats-phone__label text-ink-500">Project Sustainability rate</div>
            </div>
        </div>

        <div class="micro-stats-phone__ages">
            <div class="micro-stats-phone__ages-title">Beneficiaries' Age Classification</div>
            <div class="flex justify-around">
                @foreach ($ages as $age)
                    <div class="text-center">
                        <div class="micro-stats-phone__gauge">
                            <x-ui.radial-gauge :value="$age->project_count" :of="$total"/>
                            <div class="micro-stats-phone__gauge-share">{{ round($total > 0 ? $age->project_count / $total * 100 : 0) }}%</div>
                        </div>
                        <div class="micro-stats-phone__small mt-2">{{ $age->Name }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="micro-stats-phone__pair my-2">
            <div class="micro-stats-phone__city">
                <div class="micro-stats-phone__city-name">Aleppo</div>
                <div class="micro-stats-phone__city-rule"></div>
                <div class="micro-stats-phone__city-number">{{ number_format($count(3)) }}</div>
                <div class="micro-stats-phone__city-projects">Projects</div>
            </div>
            <div class="micro-stats-phone__categories">
                <div class="micro-stats-phone__small mb-2">Top 5 Categories</div>
                <ol class="micro-stats-phone__list">
                    @foreach ($categories as $category)
                        <li>{{ $category->Name }}</li>
                    @endforeach
                </ol>
            </div>
        </div>

        <div class="micro-stats-phone__pair micro-stats-phone__pair--cities">
            @foreach ($phoneCities as $city => $projects)
                <div class="micro-stats-phone__city">
                    <div class="micro-stats-phone__city-name">{{ $city }}</div>
                    <div class="micro-stats-phone__city-rule"></div>
                    <div class="micro-stats-phone__city-number">{{ $figure($projects) }}</div>
                    <div class="micro-stats-phone__city-projects">Projects</div>
                </div>
            @endforeach
        </div>
    </div>
</x-ui.reveal>
