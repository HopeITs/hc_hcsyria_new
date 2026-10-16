{{--
    The LEAP footer (legacy new/public/footer-leap.blade.php): a "LEAP Centers"
    heading and the strip of the two centres, with their e-mail addresses,
    above the shared contact and copyright bands, with the town names in the
    dark blue of the programme (the heading stays red, as legacy drew it).
--}}
@php
    $centers = [
        ['city' => __('about.contactAleppo'), 'email' => 'leap.aleppo@hcsyria.org', 'phone' => '+963 930 306 688', 'map' => "https://www.google.com/maps/place/36%C2%B013'00.3%22N+37%C2%B009'14.7%22E/@36.2167604,37.1515114,17z/data=!3m1!4b1!4m4!3m3!8m2!3d36.2167604!4d37.1540863?entry=ttu&g_ep=EgoyMDI1MDgxOC4wIKXMDSoASAFQAw%3D%3D"],
        ['city' => __('about.contactHoms'), 'email' => 'leap.homs@hcsyria.org', 'phone' => '+963 950 018 463', 'map' => "https://www.google.com/maps/place/34%C2%B043'56.8%22N+36%C2%B043'17.3%22E/@34.7324444,36.7214722,17z/data=!3m1!4b1!4m4!3m3!8m2!3d34.7324444!4d36.7214722?hl=en&entry=ttu"],
    ];
@endphp

<div class="mt-[5vw]">
    <div class="flex justify-center px-3">
        <x-ui.reveal :ms="200" class="section-title pb-4 pt-2">LEAP Centers</x-ui.reveal>
    </div>

    <footer id="footer" class="site-footer site-footer--flush accent-disc">
        <x-site.footer.centers :centers="$centers" :columns="['sm:w-5/12', 'sm:w-1/2']" :align="['', '']" closed/>
        <x-site.footer.bar/>
    </footer>
</div>
