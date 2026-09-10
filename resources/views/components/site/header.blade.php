@php
    /*
     * Ported from resources/views/new/public/navbar.blade.php in the legacy
     * application. Links, labels and grouping are exactly as they are there —
     * labels come from lang/en/navbar.php, the same keys the legacy Blade used.
     *
     * The legacy file repeated the whole "What We Do" menu twice, once for
     * .dropdown_big_screen and once for .dropdown_small_screen. It is defined
     * once here and rendered for both.
     */
    $whatWeDo = [
        [
            ['heading' => __('navbar.whatWeDo.development')],
            ['label' => __('navbar.whatWeDo.micro'),  'href' => '/projects/micro-projects'],
            ['label' => __('navbar.whatWeDo.Disc'),   'href' => '/projects/disc'],
            ['label' => __('navbar.whatWeDo.study'),  'href' => '/projects/study-zone'],
            ['label' => __('navbar.whatWeDo.school'), 'href' => '/projects/school-to-school'],
            ['label' => 'Children University',        'href' => '/projects/childrenuni'],
            ['label' => 'LEAP',                       'href' => '/projects/leap'],
            ['heading' => __('navbar.whatWeDo.organizations')],
            ['label' => __('navbar.whatWeDo.youth'),   'href' => '/christianyouth'],
            ['label' => __('navbar.whatWeDo.preview'), 'href' => '/projects/social-activities'],
        ],
        [
            ['heading' => __('navbar.whatWeDo.relief')],
            ['label' => __('navbar.whatWeDo.family'),  'href' => '/projects/family-to-family'],
            ['label' => 'Cash Aid / Earthquake',       'href' => '/projects/relief/289-multi-purpose-cash-aid-for-families-affected-by-the-earthquake'],
            ['label' => __('navbar.whatWeDo.preview'), 'href' => '/projects/relief'],
            ['heading' => __('navbar.whatWeDo.rehabilitation')],
            ['label' => __('navbar.whatWeDo.preview'), 'href' => '/projects/rehabilitation'],
            ['heading' => 'Earthquake Response'],
            ['label' => __('navbar.whatWeDo.preview'), 'href' => '/projects/earthquake-response'],
        ],
    ];
@endphp

<header
    x-data="{ mobileOpen: false, openMenu: null }"
    @keydown.escape.window="mobileOpen = false; openMenu = null"
    {{--
        The legacy nav is `position: fixed`, cleared by a `.page_start{padding-top:4vw}`
        wrapper on the page content plus that content's own 1% top margin, which
        land flush with the nav's bottom edge because the two values happen to sum
        to ~72px at 1440px -- not a single deliberate offset. Reproducing that
        compound, page-specific spacer is out of scope for the navbar component
        itself; `sticky` gives the same "always visible, never overlapped" result
        without requiring every page to carry a matching magic-number spacer.
        Documented here as a deliberate difference, not an invented one.
    --}}
    class="sticky top-0 z-40 bg-white"
>
    {{--
        Legacy structure: <nav class="page-padding"><div class="container-fluid">...
        page-padding gives 1vw/4.1vw; the nested container-fluid adds a further
        fixed 12px each side. Two wrappers, not one, so the paddings add rather
        than one overriding the other.
    --}}
    <div class="page-padding">
    <div class="navbar-shell flex items-center gap-6 px-3">
        <a href="/home" class="navbar-brand shrink-0">
            {{-- 8.5vw, matching #nav_logo in the legacy public/css/navbar.css --}}
            <img src="/images/logos/hc.svg" alt="Christian Hope Center" class="h-auto w-[8.5vw]">
        </a>

        {{-- Desktop --}}
        <nav aria-label="Main" class="hidden flex-1 items-center gap-0 lg:flex">
            <a href="/home" class="nav-link px-[1vw]">@lang('navbar.home')</a>
            <span class="nav-divider" aria-hidden="true"></span>

            <a href="/who-we-are" class="nav-link px-[1vw]">@lang('navbar.whoWeAre')</a>
            <span class="nav-divider" aria-hidden="true"></span>

            <div class="relative px-[1vw]" @mouseenter="openMenu = 'what'" @mouseleave="openMenu = null">
                <button type="button" class="nav-link" @click="openMenu = openMenu === 'what' ? null : 'what'"
                        :aria-expanded="(openMenu === 'what').toString()" aria-haspopup="true">
                    @lang('navbar.whatWeDo')
                </button>

                {{--
                    `.navbar .dropdown-menu { left: -12.5vw !important; }` in the
                    legacy CSS -- the panel sits shifted left of its trigger, not
                    left-aligned under it. Width, border, radius and padding are
                    all measured off the open menu on the running legacy page.
                --}}
                <div x-show="openMenu === 'what'" x-transition.opacity.duration.150ms x-cloak
                     class="dropdown-panel dropdown-shift absolute top-full">
                    <div class="grid grid-cols-2 gap-x-[1vw]">
                        @foreach ($whatWeDo as $column)
                            <ul>
                                @foreach ($column as $item)
                                    @isset($item['heading'])
                                        <li class="pt-[0.5vw] first:pt-0">
                                            <h2 class="section-heading">{{ $item['heading'] }}</h2>
                                        </li>
                                    @else
                                        <li>
                                            <a href="{{ $item['href'] }}" class="dropdown-link">{{ $item['label'] }}</a>
                                        </li>
                                    @endisset
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
            <span class="nav-divider" aria-hidden="true"></span>

            <div class="relative px-[1vw]" @mouseenter="openMenu = 'pub'" @mouseleave="openMenu = null">
                <button type="button" class="nav-link" @click="openMenu = openMenu === 'pub' ? null : 'pub'"
                        :aria-expanded="(openMenu === 'pub').toString()" aria-haspopup="true">
                    Publications
                </button>

                <div x-show="openMenu === 'pub'" x-transition.opacity.duration.150ms x-cloak
                     class="dropdown-panel absolute left-0 top-full min-w-64">
                    <ul>
                        <li>
                            <a href="/rootsofhope" class="dropdown-link">
                                Roots Of Hope <span class="newest">new</span>
                            </a>
                        </li>
                        @isset($edition)
                            <li>
                                <a href="/newsletter/{{ $edition['id'] }}/1" class="dropdown-link">
                                    @lang('navbar.newsletter')
                                </a>
                            </li>
                        @endisset
                    </ul>
                </div>
            </div>
            <span class="nav-divider" aria-hidden="true"></span>

            {{-- Scrolls to the footer, as the legacy #contact-us handler did. --}}
            <a href="#footer" class="nav-link px-[1vw]">@lang('navbar.contactUs')</a>

            {{--
                One pill: input rounded on the left, button rounded on the right,
                flush in the middle -- exact sizes from `.navbar input` and
                `#SearchBtn` (9vw / 3vw wide, 2.5vw tall, red button, uppercase).
            --}}
            <form method="GET" action="/search" class="ml-auto flex items-center" role="search">
                <label for="site-search" class="sr-only">@lang('navbar.contactUs')</label>
                <input id="site-search" type="text" name="search" value="{{ request()->get('search') }}"
                       placeholder="search..." aria-label="Search"
                       class="search-input border border-r-0 border-ink-200 px-[0.5vw] focus:outline-none">
                <button type="submit" aria-label="Search" class="search-btn flex items-center justify-center uppercase">
                    <svg class="size-[1vw]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                    </svg>
                </button>
            </form>
        </nav>

        <button type="button" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen.toString()"
                aria-controls="mobile-nav" class="ml-auto p-2 text-ink-600 lg:hidden">
            <span class="sr-only">Toggle navigation</span>
            <svg x-show="!mobileOpen" class="size-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5"/>
            </svg>
            <svg x-show="mobileOpen" x-cloak class="size-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    </div>

    {{-- Mobile --}}
    <nav id="mobile-nav" x-show="mobileOpen" x-collapse x-cloak aria-label="Main"
         class="border-t border-ink-100 lg:hidden">
        <div class="space-y-4 px-4 py-4 sm:px-6">
            <a href="/home" class="nav-link block">@lang('navbar.home')</a>
            <a href="/who-we-are" class="nav-link block">@lang('navbar.whoWeAre')</a>

            <div class="grid gap-x-6 sm:grid-cols-2">
                @foreach ($whatWeDo as $column)
                    <ul class="space-y-1">
                        @foreach ($column as $item)
                            @isset($item['heading'])
                                <li class="pt-3"><h2 class="section-heading">{{ $item['heading'] }}</h2></li>
                            @else
                                <li><a href="{{ $item['href'] }}" class="dropdown-link">{{ $item['label'] }}</a></li>
                            @endisset
                        @endforeach
                    </ul>
                @endforeach
            </div>

            <ul class="space-y-1 border-t border-ink-100 pt-3">
                <li><a href="/rootsofhope" class="dropdown-link">Roots Of Hope <span class="newest">new</span></a></li>
                @isset($edition)
                    <li><a href="/newsletter/{{ $edition['id'] }}/1" class="dropdown-link">@lang('navbar.newsletter')</a></li>
                @endisset
            </ul>

            <a href="#footer" class="nav-link block border-t border-ink-100 pt-3">@lang('navbar.contactUs')</a>

            <form method="GET" action="/search" class="flex items-center" role="search">
                <input type="text" name="search" value="{{ request()->get('search') }}" placeholder="search..."
                       aria-label="Search"
                       class="w-full border border-ink-200 px-3 py-2 text-sm focus:border-brand-600 focus:outline-none">
                <button type="submit" aria-label="Search"
                        class="flex items-center justify-center bg-brand-600 px-4 py-2 text-white">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                    </svg>
                </button>
            </form>
        </div>
    </nav>
</header>
