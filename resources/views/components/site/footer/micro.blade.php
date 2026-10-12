{{--
    The micro-projects footer (legacy new/public/footer-micro.blade.php): a
    "Micro Projects Centers" heading and the strip of the four centres above
    the shared contact and copyright bands. The Latakia centre's town is
    written out in the legacy markup rather than taken from the language file.
--}}
@php
    $centers = [
        ['city' => __('about.contactAleppo'), 'phone' => '+963 993 310 227', 'map' => "https://www.google.com/maps/place/36%C2%B013'01.2%22N+37%C2%B009'14.6%22E/@36.2169084,37.1533868,18.25z/data=!4m5!3m4!1s0x0:0x0!8m2!3d36.2170018!4d37.1540679?hl=en"],
        ['city' => __('about.contactHoms'), 'phone' => '+963 950 018 430', 'map' => "https://www.google.com/maps/place/34%C2%B043'56.9%22N+36%C2%B043'07.2%22E/@34.7325406,36.7184115,19z/data=!4m5!3m4!1s0x0:0x0!8m2!3d34.732469!4d36.718669"],
        ['city' => __('about.contactDamascus'), 'phone' => '+963 989 162 108', 'map' => "https://www.google.com/maps/place/33%C2%B030'38.4%22N+36%C2%B018'60.0%22E/@33.5107083,36.3164944,17.88z/data=!4m5!3m4!1s0x0:0xb7b7dc446ddf0cfc!8m2!3d33.510666!4d36.316655"],
        ['city' => 'Latakia', 'phone' => '+963 950 018 484', 'map' => 'https://www.google.com/maps/place/Al+Sleibeh,+Latakia,+Syria/@35.5133136,35.7753752,13.25z/data=!4m6!3m5!1s0x1526adb6a0e88041:0x3cdf33987cbbeb16!8m2!3d35.5034238!4d35.7737614!16s%2Fg%2F11gzmqfhh?entry=ttu&g_ep=EgoyMDI1MDEyOC4wIKXMDSoASAFQAw%3D%3D'],
    ];
@endphp

<div class="mt-[5vw]">
    <div class="flex justify-center px-3">
        <x-ui.reveal :ms="200" class="section-title pb-4 pt-2">Micro Projects Centers</x-ui.reveal>
    </div>

    <footer id="footer" class="site-footer site-footer--flush">
        <x-site.footer.centers :centers="$centers"/>
        <x-site.footer.bar/>
    </footer>
</div>
