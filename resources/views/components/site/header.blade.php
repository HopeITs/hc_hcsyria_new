@props(['menu' => 'instant', 'overlayUntil' => null])

@php
    /*
     * Ported from new/public/navbar.blade.php. Links, labels and their order
     * are exactly the legacy "big screen" menus; labels come from
     * lang/en/navbar.php, the keys the legacy Blade used. Styles are in
     * resources/css/site/navbar.css.
     *
     * The legacy markup also carried a "small screen" copy of both menus
     * (.dropdown_small_screen). navbar.css hides it at every width, so it was
     * never seen and is not ported. Neither is the white logo (#nav_logo_w),
     * which is display:none everywhere.
     *
     * A menu entry is a heading, a link, or a rule (the li.div hairline).
     */
    $whatWeDo = [
        [
            ['heading' => __('navbar.whatWeDo.development')],
            ['link' => __('navbar.whatWeDo.micro'), 'href' => '/projects/micro-projects'],
            ['rule' => true],
            ['link' => __('navbar.whatWeDo.Disc'), 'href' => '/projects/disc'],
            ['rule' => true],
            ['link' => __('navbar.whatWeDo.study'), 'href' => '/projects/study-zone'],
            ['rule' => true],
            ['link' => __('navbar.whatWeDo.school'), 'href' => '/projects/school-to-school'],
            ['rule' => true],
            ['link' => 'Children University', 'href' => '/projects/childrenuni'],
            ['rule' => true],
            ['link' => 'LEAP', 'href' => '/projects/leap'],
            ['rule' => true],
            ['heading' => __('navbar.whatWeDo.organizations')],
            ['link' => __('navbar.whatWeDo.youth'), 'href' => '/christianyouth'],
            ['rule' => true],
            ['link' => __('navbar.whatWeDo.preview'), 'href' => '/projects/social-activities'],
        ],
        [
            ['heading' => __('navbar.whatWeDo.relief')],
            ['link' => __('navbar.whatWeDo.family'), 'href' => '/projects/family-to-family'],
            ['rule' => true],
            ['link' => 'Cash Aid / Earthquake', 'href' => '/projects/relief/289-multi-purpose-cash-aid-for-families-affected-by-the-earthquake'],
            ['rule' => true],
            ['link' => __('navbar.whatWeDo.preview'), 'href' => '/projects/relief'],
            ['rule' => true],
            ['heading' => __('navbar.whatWeDo.rehabilitation')],
            ['link' => __('navbar.whatWeDo.preview'), 'href' => '/projects/rehabilitation'],
            ['rule' => true],
            ['heading' => 'Earthquake Response'],
            ['link' => __('navbar.whatWeDo.preview'), 'href' => '/projects/earthquake-response'],
            ['rule' => true],
        ],
    ];

    $publications = [
        ['label' => __('navbar.newsletter'), 'href' => '/our-news'],
        ['label' => 'Roots Of Hope', 'href' => '/rootsofhope'],
    ];
@endphp

{{--
    Menu behaviour is the siteNav component (resources/js/site-nav.js). $menu
    is 'instant' (hover opens at once, as on most legacy pages) or 'slide'
    (250ms delay then a slide, as on the legacy 404); a click or tap toggles
    instantly; clicking elsewhere or Escape closes.

    $overlayUntil, a selector, makes the bar transparent with white text and
    logo over a hero image until the page has scrolled to within 300px of
    that element (the legacy navbar_reverse; home page).
--}}
<nav @class(['site-nav', 'site-nav--overlay' => $overlayUntil !== null]) aria-label="Main"
     :class="{ 'site-nav--overlay': overlay }"
     x-data="siteNav(@js(['mode' => $menu, 'overlayUntil' => $overlayUntil]))"
     @click.outside="close()" @keydown.escape.window="close(); open = false">
    <div class="site-nav__inner">
        <a class="site-nav__brand" href="/home">
            <img class="site-nav__logo" alt="Christian Hope Center"
                 src="{{ $overlayUntil !== null ? '/images/logos/hcreverse.svg' : '/images/logos/hc.svg' }}"
                 :src="overlay ? '/images/logos/hcreverse.svg' : '/images/logos/hc.svg'">
        </a>

        <button class="site-nav__toggler" type="button" @click="open = ! open"
                :aria-expanded="open.toString()" aria-expanded="false"
                aria-controls="site-nav-menu" aria-label="Toggle navigation">
            <span class="site-nav__toggler-icon"></span>
        </button>

        <div id="site-nav-menu" class="site-nav__collapse" :class="{ 'is-open': open }">
            <div class="site-nav__collapse-inner">
            <ul class="site-nav__list">
                <li class="site-nav__item"><a class="site-nav__link" href="/home">@lang('navbar.home')</a></li>
                <li class="site-nav__divider" role="separator"></li>
                <li class="site-nav__item"><a class="site-nav__link" href="/who-we-are">@lang('navbar.whoWeAre')</a></li>
                <li class="site-nav__divider" role="separator"></li>

                <li class="site-nav__item" @mouseenter="enter('what')" @mouseleave="leave()">
                    <a href="#" class="site-nav__link site-nav__link--toggle"
                       @click.prevent="toggle('what')" aria-haspopup="true"
                       :aria-expanded="(menu === 'what').toString()" aria-expanded="false">@lang('navbar.whatWeDo')</a>
                    <div class="site-nav__panel site-nav__panel--wide" :class="{ 'is-hover': hover }"
                         x-ref="what" x-show="menu === 'what'" x-cloak>
                        <div class="site-nav__columns">
                            @foreach ($whatWeDo as $column)
                                <div class="site-nav__column-wrap">
                                    <ul class="site-nav__column">
                                        @foreach ($column as $entry)
                                            @if (isset($entry['heading']))
                                                <li><span class="site-nav__heading">{{ $entry['heading'] }}</span></li>
                                            @elseif (isset($entry['link']))
                                                <li><a class="site-nav__panel-link" href="{{ $entry['href'] }}">{{ $entry['link'] }}</a></li>
                                            @else
                                                <li class="site-nav__rule" role="separator"></li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </li>
                <li class="site-nav__divider" role="separator"></li>

                <li class="site-nav__item" @mouseenter="enter('publications')" @mouseleave="leave()">
                    <a href="#" class="site-nav__link site-nav__link--toggle"
                       @click.prevent="toggle('publications')" aria-haspopup="true"
                       :aria-expanded="(menu === 'publications').toString()" aria-expanded="false">Publications</a>
                    <div class="site-nav__panel site-nav__panel--narrow" :class="{ 'is-hover': hover }"
                         x-ref="publications" x-show="menu === 'publications'" x-cloak>
                        <ul class="site-nav__column">
                            @foreach ($publications as $item)
                                <li><a class="site-nav__panel-link site-nav__panel-link--caps" href="{{ $item['href'] }}">{{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </li>
                <li class="site-nav__divider" role="separator"></li>

                {{-- Scrolls to the footer, as the legacy #contact-us handler did. --}}
                <li class="site-nav__item">
                    <button type="button" class="site-nav__link"
                            @click="open = false; document.getElementById('footer').scrollIntoView({ behavior: 'smooth' })">@lang('navbar.contactUs')</button>
                </li>

                <li class="site-nav__item">
                    <form method="GET" action="/search" role="search">
                        <div class="site-nav__search">
                            <input class="site-nav__search-input" type="text" name="search"
                                   value="{{ request()->query('search') }}" placeholder="search..." aria-label="Search">
                            <button class="site-nav__search-button" type="submit" aria-label="Search">
                                <x-ui.icon name="fa-search"/>
                            </button>
                        </div>
                    </form>
                </li>
            </ul>
            </div>
        </div>
    </div>
</nav>
