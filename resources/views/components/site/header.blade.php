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
    class="sticky top-0 z-40 bg-white"
>
    <div class="mx-auto flex max-w-[1400px] items-center gap-6 px-4 py-3 sm:px-6">
        <a href="/home" class="navbar-brand shrink-0">
            <img src="/images/logos/hc.svg" alt="Christian Hope Center" class="h-11 w-auto sm:h-14">
        </a>

        {{-- Desktop --}}
        <nav aria-label="Main" class="hidden flex-1 items-center gap-5 lg:flex">
            <a href="/home" class="nav-link">@lang('navbar.home')</a>
            <a href="/who-we-are" class="nav-link">@lang('navbar.whoWeAre')</a>

            <div class="relative" @mouseenter="openMenu = 'what'" @mouseleave="openMenu = null">
                <button type="button" class="nav-link" @click="openMenu = openMenu === 'what' ? null : 'what'"
                        :aria-expanded="(openMenu === 'what').toString()" aria-haspopup="true">
                    @lang('navbar.whatWeDo')
                </button>

                <div x-show="openMenu === 'what'" x-transition.opacity.duration.150ms x-cloak
                     class="absolute left-0 top-full w-[38rem] border border-ink-100 bg-white p-6 shadow-lg">
                    <div class="grid grid-cols-2 gap-x-8">
                        @foreach ($whatWeDo as $column)
                            <ul class="space-y-1">
                                @foreach ($column as $item)
                                    @isset($item['heading'])
                                        <li class="pt-3 first:pt-0">
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

            <div class="relative" @mouseenter="openMenu = 'pub'" @mouseleave="openMenu = null">
                <button type="button" class="nav-link" @click="openMenu = openMenu === 'pub' ? null : 'pub'"
                        :aria-expanded="(openMenu === 'pub').toString()" aria-haspopup="true">
                    Publications
                </button>

                <div x-show="openMenu === 'pub'" x-transition.opacity.duration.150ms x-cloak
                     class="absolute left-0 top-full w-64 border border-ink-100 bg-white p-4 shadow-lg">
                    <ul class="space-y-1">
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

            {{-- Scrolls to the footer, as the legacy #contact-us handler did. --}}
            <a href="#footer" class="nav-link">@lang('navbar.contactUs')</a>

            <form method="GET" action="/search" class="ml-auto flex items-center" role="search">
                <label for="site-search" class="sr-only">@lang('navbar.contactUs')</label>
                <input id="site-search" type="text" name="search" value="{{ request()->get('search') }}"
                       placeholder="search..." aria-label="Search"
                       class="w-40 border border-ink-200 px-3 py-1.5 text-sm focus:border-brand-600 focus:outline-none xl:w-52">
                <button type="submit" aria-label="Search"
                        class="border border-l-0 border-ink-200 px-3 py-1.5 text-ink-600 hover:text-brand-600">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
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
                        class="border border-l-0 border-ink-200 px-3 py-2 text-ink-600">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/>
                    </svg>
                </button>
            </form>
        </div>
    </nav>
</header>
