{{--
    Family to Family's survey results (resources/css/ui/family-survey.css): a
    sentence, then the findings as drawn figures. Wide screens get three
    figures, phones four narrower ones; legacy kept each set as image files
    under images/initiatives/Family to Family, which are not in the database.
--}}
@php
    $wide = ['s1.svg' => 700, 's2.svg' => 800, 's3.svg' => 900];
    $narrow = ['s1.svg' => 700, 's2.svg' => 800, 's3.svg' => 900, 's4.svg' => 1100];
    $intro = 'In 2022, A Survey was Conducted to Assess the Accurate Needs of Christian Families in ALEPPO, the Assessment Included…';
@endphp

<x-ui.reveal :ms="600" id="statistic-section" class="family-survey section-gap">
    <div class="px-3">
        <div class="-mx-3 flex">
            <div class="flex w-full flex-col items-center justify-center px-3">
                <x-ui.section-title class="pb-0">Statistics</x-ui.section-title>
            </div>
        </div>

        <div class="family-survey__band max-sm:hidden">
            <div class="px-3">
                <div class="page-margin">
                    <div class="family-survey__intro">{{ $intro }}</div>
                    @foreach ($wide as $file => $ms)
                        <x-ui.reveal :ms="$ms" class="family-survey__figure">
                            <img src="/images/initiatives/Family to Family/{{ $file }}" alt="">
                        </x-ui.reveal>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="family-survey__band sm:hidden">
            <div class="px-3">
                <div class="page-margin">
                    <div class="family-survey__intro">{{ $intro }}</div>
                    @foreach ($narrow as $file => $ms)
                        <x-ui.reveal :ms="$ms" class="family-survey__figure">
                            <img src="/images/initiatives/Family to Family/statis-mobile/{{ $file }}" alt="">
                        </x-ui.reveal>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-ui.reveal>
