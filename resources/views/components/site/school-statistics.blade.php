{{--
    School to School's statistics: a drawn scene of a school's street, wide
    screens only (resources/css/ui/school-stats.css). Passing over a character
    opens a card with a figure counting up beside its picture; a card stays
    once opened, as in legacy, and the scene itself is the background, so
    anywhere on the landscape opens the elementary-school card. Phones get
    only the heading: legacy's list of the same figures for them sat inside
    the scene's own hidden box and was never shown, so it is not ported.

    The scene is public/images/home/school-statistics.svg, and the shapes that
    answer the pointer are the outlines of the five characters in it (their
    convex hulls, in the scene's own coordinates), so the page does not carry
    the scene's thousands of paths twice. The figures are the ones legacy
    printed by hand; there is no table behind them.
--}}
@php
    $cards = [
        'students' => ['number' => 4098, 'unit' => '', 'label' => 'students', 'picture' => 'Statistics Layout-03.svg', 'hull' => '657.2,307.9 658.7,294.2 663.1,264.5 664.8,256.5 671.8,255.6 685.5,255.3 723.1,260.8 770.3,269.1 781.2,271.7 808.4,301.1 809,310.3 682.7,328.8 672.1,327.8'],
        'schools' => ['number' => 14, 'unit' => '', 'label' => 'schools', 'picture' => 'Statistics Layout-02.svg', 'hull' => '1085.3,325 1111.1,260.7 1114.5,252.5 1118.6,244.3 1125.1,233.7 1125.4,233.5 1193.7,219.4 1201.7,217.8 1238.3,218.2 1250.2,219.1 1253.2,219.4 1270.1,243.9 1273,249.8 1280.6,267.2 1275.2,331.2 1101.7,325.8'],
        'high' => ['number' => 15, 'unit' => '%', 'label' => 'High School', 'picture' => 'Statistics2.svg', 'hull' => '250.7,530.4 253,515.1 254.1,511.3 315.4,470.9 402.7,518.9 413.9,535.1 415.3,560.4 414.2,562.6 382.1,575 347,586.8 322.1,576.6 286.9,556.3 255.6,538'],
        'kindergarten' => ['number' => 19, 'unit' => '%', 'label' => 'Kindergarten', 'picture' => 'Statistics6.svg', 'hull' => '887.5,474.3 889.2,472 906.7,449.8 913.9,448.1 1005.2,439.3 1015.8,445.3 1023.2,451.7 1025.1,457.6 1023.1,580 1009.4,609.6 935.9,618.6 912.6,606.3 910.7,599.5'],
        'elementary' => ['number' => 44, 'unit' => '%', 'label' => 'Elementry', 'picture' => 'Statistics5.svg', 'hull' => '1,112.8 42.7,74.6 195.7,54.7 441,43.9 1156.9,27.6 1651.9,18.1 1816.5,38.5 1899.9,53 1900,137.5 1900,645.4 1826.7,656.8 1742.1,656.9 387.6,657.4 49,657.4 1,620.7'],
    ];

    // The scene answers in this order: the landscape first, the characters over it.
    $hitOrder = ['elementary', 'high', 'students', 'kindergarten', 'schools'];
@endphp

<div id="statistic-section" class="school-stats section-gap">
    <div class="px-3">
        <x-ui.reveal :ms="600" class="-mx-3 flex">
            <div class="flex w-full flex-col items-center justify-center px-3">
                <x-ui.section-title class="pb-0">Statistics</x-ui.section-title>
            </div>
        </x-ui.reveal>
    </div>

    <x-ui.reveal :ms="800" class="school-stats__scene" x-data="{ open: {} }">
        <img class="school-stats__art" src="/images/home/school-statistics.svg" alt="">
        <svg class="school-stats__hits" viewBox="0 0 1900 658.6" aria-hidden="true">
            @foreach ($hitOrder as $key)
                <polygon points="{{ $cards[$key]['hull'] }}" @mouseenter="open.{{ $key }} = true"/>
            @endforeach
        </svg>

        @foreach ($cards as $key => $card)
            <div class="school-stats__card school-stats__card--{{ $key }}" x-show="open.{{ $key }}" x-cloak style="--stat-num: {{ $card['number'] }}; --stat-to: {{ $card['number'] }}">
                <div class="school-stats__figure">{{ $card['unit'] }}<span> {{ $card['label'] }}</span></div>
                <img class="school-stats__picture" src="/images/home/{{ $card['picture'] }}" alt="">
            </div>
        @endforeach
    </x-ui.reveal>
</div>
