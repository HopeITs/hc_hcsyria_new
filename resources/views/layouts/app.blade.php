@props([
    'title' => null,
    'keywords' => 'aleppo syria, hope, center',
    'alternate' => 'https://www.hcsyria.org',
    'footer' => 'default',
    'menu' => 'instant',
    'preloader' => false,
    'overlayUntil' => null,
    'meta' => [],
    'fill' => false,
])

{{--
    The one public layout. Full-page Livewire components use it as
    layouts::app; error pages as <x-layouts::app>.

    $footer picks the footer variant: the legacy site had five (default,
    micro-projects, children, DISC, LEAP), each a component under
    components/site/footer/. $menu is how the nav menus open on hover
    (see site-nav.js). $preloader shows the loading screen; $overlayUntil
    makes the navbar transparent over a hero (see the header component).
    $keywords and $alternate are head tags every page carries: the keywords
    default to the organisation's, a page names its own where legacy did, and
    the x-default hreflang link points at the main address. $meta is a page's
    own further tags (description, the share tags): rows of name or property,
    and content. $fill makes the
    content grow to the footer on a page shorter than the window (the error
    pages); on every other page the footer follows the content, as in legacy.

    Ported from the legacy page heads: favicon and theme-color. Not ported:
    Google Analytics UA-129097809-1 (Universal Analytics, shut down by Google
    in 2023-24, so it collected nothing), the html5shiv/respond.js IE8 shims,
    and the dozen stylesheet and script links each page repeated.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f6f6">
    <meta name="apple-mobile-web-app-status-bar-style" content="#f6f6f6">

    <title>{{ $title ? __($title) : config('app.name') }}</title>
    @if ($keywords)
        <meta name="keywords" content="{{ $keywords }}">
    @endif
    @foreach ($meta as $tag)
        <meta @if (isset($tag['property'])) property="{{ $tag['property'] }}" @else name="{{ $tag['name'] }}" @endif content="{{ $tag['content'] }}">
    @endforeach
    @if ($alternate)
        <link rel="alternate" hreflang="x-default" href="{{ $alternate }}">
    @endif
    <link rel="icon" href="/favicon.ico">

    {{-- Body text on every page; the other faces are found in the stylesheet. --}}
    <link rel="preload" href="{{ Vite::asset('resources/fonts/lato/lato-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    @if ($preloader)
        <x-site.preloader/>
    @endif

    {{-- Lets keyboard and screen-reader users jump past the navigation. --}}
    <a href="#main" class="skip-link">Skip to content</a>

    <x-site.header :$menu :$overlayUntil/>

    @auth
        <x-site.account-menu/>
    @endauth

    {{-- A flex item of the body column, as the legacy page content was. --}}
    <main id="main" @class(['flex shrink-0 flex-col', 'grow' => $fill])>
        {{ $slot }}
    </main>

    <x-dynamic-component :component="'site.footer.'.$footer"/>

    <x-ui.lightbox/>

    @livewireScripts
</body>
</html>
