@props(['title' => null])

@php
    /*
     * Ported from resources/views/new/public/footer-micro.blade.php.
     * Addresses, phone numbers, map links and copy are exactly as they are
     * there; the strings come from lang/en/about.php and lang/en/main.php,
     * the same keys the legacy Blade used.
     *
     * NOTE: the Latakia column uses about.address_d, which is the Damascus
     * address. That is what the live site renders — reproduced here rather
     * than corrected, because it is a content decision, not a port decision.
     */
    $centers = [
        [
            'city'    => __('about.contactAleppo'),
            'address' => __('about.address'),
            'phone'   => '+963 993 310 227',
            'tel'     => '+963993310227',
            'map'     => "https://www.google.com/maps/place/36%C2%B013'01.2%22N+37%C2%B009'14.6%22E/@36.2169084,37.1533868,18.25z/data=!4m5!3m4!1s0x0:0x0!8m2!3d36.2170018!4d37.1540679?hl=en",
        ],
        [
            'city'    => __('about.contactHoms'),
            'address' => __('about.address_h'),
            'phone'   => '+963 950 018 430',
            'tel'     => '+963950018430',
            'map'     => "https://www.google.com/maps/place/34%C2%B043'56.9%22N+36%C2%B043'07.2%22E/@34.7325406,36.7184115,19z/data=!4m5!3m4!1s0x0:0x0!8m2!3d34.732469!4d36.718669",
        ],
        [
            'city'    => __('about.contactDamascus'),
            'address' => __('about.address_d'),
            'phone'   => '+963 989 162 108',
            'tel'     => '+963989162108',
            'map'     => "https://www.google.com/maps/place/33%C2%B030'38.4%22N+36%C2%B018'60.0%22E/@33.5107083,36.3164944,17.88z/data=!4m5!3m4!1s0x0:0xb7b7dc446ddf0cfc!8m2!3d33.510666!4d36.316655",
        ],
        [
            'city'    => 'Latakia',
            'address' => __('about.address_d'),
            'phone'   => '+963 950 018 484',
            'tel'     => '+963950018484',
            'map'     => 'https://www.google.com/maps/place/Al+Sleibeh,+Latakia,+Syria/@35.5133136,35.7753752,13.25z/data=!4m6!3m5!1s0x1526adb6a0e88041:0x3cdf33987cbbeb16!8m2!3d35.5034238!4d35.7737614!16s%2Fg%2F11gzmqfhh',
        ],
    ];
@endphp

@if ($title)
    <div class="mx-auto max-w-[1400px] px-4 pt-10 text-center sm:px-6">
        <h2 class="section-title">{{ $title }}</h2>
    </div>
@endif

<footer id="footer" class="mt-10 border-t border-ink-100 bg-white">
    <div class="mx-auto max-w-[1400px] px-4 py-10 sm:px-6">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($centers as $center)
                <div class="border-l border-ink-200 pl-4">
                    <a href="{{ $center['map'] }}" target="_blank" rel="noopener noreferrer"
                       class="flex items-center gap-2 text-[#616262] hover:text-brand-600">
                        <svg class="size-4 shrink-0" viewBox="0 0 14 19.98" fill="currentColor" aria-hidden="true">
                            <path d="M13.92,5.8c-.25-1.76-1.35-3.42-2.77-4.48C9.72.26,7.79-.18,6.04.07c-1.87.33-3.61,1.17-4.73,2.75-1.12,1.58-1.4,3.6-1.06,5.46.56,3.02,4.84,9.39,6.9,11.87l.44-.58c2.03-2.75,5.48-7.39,6.34-10.62.25-.96.29-2.01.14-2.99ZM7,10.9c-2.13,0-3.86-1.73-3.86-3.86s1.73-3.86,3.86-3.86,3.86,1.73,3.86,3.86-1.73,3.86-3.86,3.86Z"/>
                        </svg>
                        <span class="font-semibold uppercase">{{ $center['city'] }}</span>
                    </a>

                    <p class="mt-2 text-sm capitalize leading-relaxed text-[#616262]">{{ $center['address'] }}</p>

                    <a href="tel:{{ $center['tel'] }}" dir="ltr"
                       class="mt-2 block text-sm text-[#616262] hover:text-brand-600">{{ $center['phone'] }}</a>

                    <p class="mt-2 text-sm leading-relaxed text-[#616262]">{!! __('about.workingHours') !!}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 grid gap-8 border-t border-ink-100 pt-8 sm:grid-cols-2">
            <div>
                <p class="text-sm uppercase text-[#616262]">contact us:</p>
                <p class="mt-1 font-semibold uppercase text-brand-600">hope center</p>
                <p class="text-sm uppercase text-[#616262]">@lang('about.hopeAddressTitle')</p>
                <p class="mt-1 text-sm capitalize text-[#616262]">@lang('about.address')</p>
                <a href="mailto:info@hcsyria.org" class="mt-1 block text-sm text-[#616262] hover:text-brand-600">
                    info@hcsyria.org
                </a>
                <a href="https://hcsyria.org" class="mt-1 block text-sm text-[#616262] hover:text-brand-600">
                    hcsyria.org
                </a>
            </div>

            <div class="sm:text-right">
                <a href="/newsletter/10/1" class="text-sm text-[#616262] underline hover:text-brand-600">
                    Visit our Newsletter
                </a>

                <div class="mt-4 flex gap-4 sm:justify-end">
                    <a href="https://www.linkedin.com/company/christian-hope-center/?viewAsMember=true"
                       target="_blank" rel="noopener noreferrer" class="text-[#616262] hover:text-brand-600">
                        <span class="sr-only">LinkedIn</span>
                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M4.98 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM2.4 21.5h5.16V9.75H2.4V21.5Zm7.74-11.75V21.5h5.16v-6.2c0-3.27 4.26-3.54 4.26 0v6.2h5.16v-7.98c0-8.06-9.22-7.76-9.42-3.8v-.97h-5.16Z"/>
                        </svg>
                    </a>
                    <a href="https://www.youtube.com/channel/UC777Xf3lHtQufwaYjgxsiRA/videos"
                       target="_blank" rel="noopener noreferrer" class="text-[#616262] hover:text-brand-600">
                        <span class="sr-only">YouTube</span>
                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M23.5 6.9a3 3 0 0 0-2.1-2.1C19.5 4.3 12 4.3 12 4.3s-7.5 0-9.4.5A3 3 0 0 0 .5 6.9C0 8.8 0 12 0 12s0 3.2.5 5.1a3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1c.5-1.9.5-5.1.5-5.1s0-3.2-.5-5.1ZM9.6 15.6V8.4l6.2 3.6-6.2 3.6Z"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-ink-100 pt-6">
            <p class="text-sm text-[#616262]">{!! __('main.footer') !!}</p>
            <a href="#" class="text-sm text-[#616262] hover:text-brand-600">Back to Top</a>
        </div>
    </div>
</footer>
