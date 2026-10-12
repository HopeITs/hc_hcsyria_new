@props(['centers'])

{{--
    The grey strip above the red bands of the programme footers: where the
    programme's centres are and how to ring them (resources/css/site/footer.css).
    Four across from 576px; on phones one under another with a short rule
    between. Each centre is ['city', 'phone', 'map'], the phone as written.
--}}
@php
    $last = count($centers) - 1;
    // Where each of four columns puts its content on a wide screen.
    $align = ['', 'justify-center', 'justify-end', 'justify-end'];
@endphp

<div {{ $attributes->class('site-footer__top') }}>
    <div class="site-footer__inner">
        {{-- From 576px: a row of columns --}}
        <div class="site-footer__row hidden items-baseline sm:flex">
            @foreach ($centers as $i => $center)
                <div @class(['site-footer__col sm:w-1/4', 'flex '.$align[$i] => $align[$i] !== ''])>
                    <div class="site-footer__center">
                        <x-site.footer.center :center="$center"/>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Phones: one under another, a short rule between --}}
        <div class="site-footer__row flex items-baseline sm:hidden">
            @foreach ($centers as $i => $center)
                <div class="site-footer__col text-center">
                    <x-site.footer.center :center="$center"/>
                    @if ($i < $last)
                        <div class="-mx-3 mb-2 flex items-center justify-center">
                            <div class="w-1/3 px-3"><img class="site-footer__rule" src="/images/home/footerline.svg" alt=""></div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
