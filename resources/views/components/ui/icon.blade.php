@props(['name'])

@php
    /*
     * The icon glyphs the legacy pages draw with icon fonts, as inline SVG:
     * only the glyphs actually used, instead of three whole icon fonts.
     *
     * FontAwesome 4.7 outlines are copied from the legacy
     * public/fonts/fontawesome-webfont.svg (units-per-em 1792, ascent 1536);
     * the house is Bootstrap Icons 1.10.5, the version the legacy 404 loads.
     *
     * An icon font draws a glyph y-up from the baseline inside a 1em box, and
     * sits on the text baseline. "font" glyphs are flipped into the same box
     * and lowered by the font's descent (256/1792 = 0.142857em), so at the
     * same font-size they cover the same pixels as the glyph did. Bootstrap
     * Icons are drawn 1em square at vertical-align -0.125em, as its CSS does.
     */
    $icons = [
        'fa-search' => ['viewBox' => '0 0 1664 1792', 'font' => true, 'd' => 'M1152 704q0 185 -131.5 316.5t-316.5 131.5t-316.5 -131.5t-131.5 -316.5t131.5 -316.5t316.5 -131.5t316.5 131.5t131.5 316.5zM1664 -128q0 -52 -38 -90t-90 -38q-54 0 -90 38l-343 342q-179 -124 -399 -124q-143 0 -273.5 55.5t-225 150t-150 225t-55.5 273.5 t55.5 273.5t150 225t225 150t273.5 55.5t273.5 -55.5t225 -150t150 -225t55.5 -273.5q0 -220 -124 -399l343 -343q37 -37 37 -90z'],
        'fa-home' => ['viewBox' => '0 0 1664 1792', 'font' => true, 'd' => 'M1408 544v-480q0 -26 -19 -45t-45 -19h-384v384h-256v-384h-384q-26 0 -45 19t-19 45v480q0 1 0.5 3t0.5 3l575 474l575 -474q1 -2 1 -6zM1631 613l-62 -74q-8 -9 -21 -11h-3q-13 0 -21 7l-692 577l-692 -577q-12 -8 -24 -7q-13 2 -21 11l-62 74q-8 10 -7 23.5t11 21.5 l719 599q32 26 76 26t76 -26l244 -204v195q0 14 9 23t23 9h192q14 0 23 -9t9 -23v-408l219 -182q10 -8 11 -21.5t-7 -23.5z'],
        'fa-youtube-play' => ['viewBox' => '0 0 1792 1792', 'font' => true, 'd' => 'M1280 640q0 37 -30 54l-512 320q-31 20 -65 2q-33 -18 -33 -56v-640q0 -38 33 -56q16 -8 31 -8q20 0 34 10l512 320q30 17 30 54zM1792 640q0 -96 -1 -150t-8.5 -136.5t-22.5 -147.5q-16 -73 -69 -123t-124 -58q-222 -25 -671 -25t-671 25q-71 8 -124.5 58t-69.5 123 q-14 65 -21.5 147.5t-8.5 136.5t-1 150t1 150t8.5 136.5t22.5 147.5q16 73 69 123t124 58q222 25 671 25t671 -25q71 -8 124.5 -58t69.5 -123q14 -65 21.5 -147.5t8.5 -136.5t1 -150z'],
        'fa-linkedin' => ['viewBox' => '0 0 1536 1792', 'font' => true, 'd' => 'M349 911v-991h-330v991h330zM370 1217q1 -73 -50.5 -122t-135.5 -49h-2q-82 0 -132 49t-50 122q0 74 51.5 122.5t134.5 48.5t133 -48.5t51 -122.5zM1536 488v-568h-329v530q0 105 -40.5 164.5t-126.5 59.5q-63 0 -105.5 -34.5t-63.5 -85.5q-11 -30 -11 -81v-553h-329 q2 399 2 647t-1 296l-1 48h329v-144h-2q20 32 41 56t56.5 52t87 43.5t114.5 15.5q171 0 275 -113.5t104 -332.5z'],
        'fa-check' => ['viewBox' => '0 0 1792 1792', 'font' => true, 'd' => 'M1671 970q0 -40 -28 -68l-724 -724l-136 -136q-28 -28 -68 -28t-68 28l-136 136l-362 362q-28 28 -28 68t28 68l136 136q28 28 68 28t68 -28l294 -295l656 657q28 28 68 28t68 -28l136 -136q28 -28 28 -68z'],
        'fa-facebook' => ['viewBox' => '0 0 768 1792', 'font' => true, 'd' => 'M511 980h257l-30 -284h-227v-824h-341v824h-170v284h170v171q0 182 86 275.5t283 93.5h227v-284h-142q-39 0 -62.5 -6.5t-34 -23.5t-13.5 -34.5t-3 -49.5v-142z'],
        'fa-instagram' => ['viewBox' => '0 0 1536 1792', 'font' => true, 'd' => 'M1362 110v648h-135q20 -63 20 -131q0 -126 -64 -232.5t-174 -168.5t-240 -62q-197 0 -337 135.5t-140 327.5q0 68 20 131h-141v-648q0 -26 17.5 -43.5t43.5 -17.5h1069q25 0 43 17.5t18 43.5zM1078 643q0 124 -90.5 211.5t-218.5 87.5q-127 0 -217.5 -87.5t-90.5 -211.5 t90.5 -211.5t217.5 -87.5q128 0 218.5 87.5t90.5 211.5zM1362 1003v165q0 28 -20 48.5t-49 20.5h-174q-29 0 -49 -20.5t-20 -48.5v-165q0 -29 20 -49t49 -20h174q29 0 49 20t20 49zM1536 1211v-1142q0 -81 -58 -139t-139 -58h-1142q-81 0 -139 58t-58 139v1142q0 81 58 139 t139 58h1142q81 0 139 -58t58 -139z'],
        'bi-house-door-fill' => ['viewBox' => '0 0 16 16', 'font' => false, 'd' => 'M6.5 14.5v-3.505c0-.245.25-.495.5-.495h2c.25 0 .5.25.5.5v3.5a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5v-7a.5.5 0 0 0-.146-.354L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.354 1.146a.5.5 0 0 0-.708 0l-6 6A.5.5 0 0 0 1.5 7.5v7a.5.5 0 0 0 .5.5h4a.5.5 0 0 0 .5-.5Z'],
    ];

    $icon = $icons[$name];
    [, , $width] = explode(' ', $icon['viewBox']);
@endphp

<svg {{ $attributes->merge(['class' => 'inline-block']) }}
     viewBox="{{ $icon['viewBox'] }}"
     width="{{ $icon['font'] ? round($width / 1792, 6) : 1 }}em" height="1em"
     style="vertical-align: {{ $icon['font'] ? '-0.142857em' : '-0.125em' }}"
     fill="currentColor" aria-hidden="true" focusable="false">
    @if ($icon['font'])
        <path transform="matrix(1 0 0 -1 0 1536)" d="{{ $icon['d'] }}"/>
    @else
        <path d="{{ $icon['d'] }}"/>
    @endif
</svg>
