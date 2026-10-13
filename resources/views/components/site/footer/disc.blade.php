{{--
    The DISC footer (legacy new/public/footer-disc.blade.php): a "DISC
    Centers" heading and the strip of the three centres, with their e-mail
    addresses, above the shared contact and copyright bands, all in DISC's
    dark blue.

    Damascus's phone number was written two ways in the legacy markup: as
    the number on screen in the narrow layout, and with a stray
    "+963963" in the link of the wide one. The number is used for both.
--}}
@php
    $centers = [
        ['city' => __('about.contactAleppo'), 'email' => 'info-adisc@hcsyria.org', 'phone' => '+963 99 33 41 388', 'map' => "https://www.google.com/maps/place/36%C2%B013'05.8%22N+37%C2%B009'26.9%22E/@36.218269,37.1552883,17z/data=!3m1!4b1!4m5!3m4!1s0x0:0xf705b9fed8d49be6!8m2!3d36.218269!4d37.157477"],
        ['city' => __('about.contactHoms'), 'email' => 'info-hdisc@hcsyria.org', 'phone' => '+963 950 018 464', 'map' => 'https://www.google.com/maps/place/Homs%E2%80%8E,+Syria/@34.7182789,36.720437,18.75z/data=!4m5!3m4!1s0x15230ee15750463d:0xe22444f17c872127!8m2!3d34.7175968!4d36.7221162'],
        ['city' => __('about.contactDamascus'), 'email' => 'info-ddisc@hcsyria.org', 'phone' => '+963 989 162 117', 'map' => "https://www.google.com/maps/place/33%C2%B030'34.2%22N+36%C2%B018'60.0%22E/@33.5095043,36.3163738,19.75z/data=!4m5!3m4!1s0x0:0x0!8m2!3d33.50951!4d36.316664"],
    ];
@endphp

<div class="mt-[5vw]">
    <div class="flex justify-center px-3">
        <x-ui.reveal :ms="200" class="section-title pb-4 pt-2">DISC Centers</x-ui.reveal>
    </div>

    <footer id="footer" class="site-footer site-footer--flush accent-disc">
        <x-site.footer.centers :centers="$centers" :columns="['sm:w-1/3', 'sm:w-1/4', 'sm:w-1/3']" upper/>
        <x-site.footer.bar/>
    </footer>
</div>
