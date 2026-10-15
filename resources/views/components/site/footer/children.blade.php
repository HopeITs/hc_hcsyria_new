{{--
    The Children University footer (legacy new/public/footer-children.blade.php):
    a "Children University Centers" heading and the strip of the two centres,
    with their e-mail addresses, above the shared contact and copyright bands,
    with the town names in the dark blue of the programme (the heading stays red, as legacy drew it).

    The wide layout's Aleppo number was linked as "++963993341399" in the
    legacy markup, and the narrow layout's as +963993341399; the number on
    screen is used for both.
--}}
@php
    $centers = [
        ['city' => __('about.contactAleppo'), 'email' => 'info-cualeppo@hcsyria.org', 'phone' => '+963 99 33 41 399', 'map' => "https://www.google.com/maps/place/36%C2%B013'01.2%22N+37%C2%B009'14.6%22E/@36.2169084,37.1533868,18.25z/data=!4m5!3m4!1s0x0:0x0!8m2!3d36.2170018!4d37.1540679?hl=en"],
        ['city' => __('about.contactHoms'), 'email' => 'info-cuhoms@hcsyria.org', 'phone' => '+963 950 018 440', 'map' => "https://www.google.com/maps/place/34%C2%B043'56.9%22N+36%C2%B043'07.2%22E/@34.7325406,36.7184115,19z/data=!4m5!3m4!1s0x0:0x0!8m2!3d34.732469!4d36.718669"],
    ];
@endphp

<div class="mt-[5vw]">
    <div class="flex justify-center px-3">
        <x-ui.reveal :ms="200" class="section-title pb-4 pt-2">Children University Centers</x-ui.reveal>
    </div>

    <footer id="footer" class="site-footer site-footer--flush accent-disc">
        <x-site.footer.centers :centers="$centers" :columns="['sm:w-5/12', 'sm:w-1/2']" :align="['', '']"/>
        <x-site.footer.bar/>
    </footer>
</div>
