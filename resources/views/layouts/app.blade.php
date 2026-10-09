@props(['title' => null, 'description' => null, 'footer' => 'default', 'menu' => 'instant'])

{{--
    The one public layout. Full-page Livewire components use it as
    layouts::app; error pages as <x-layouts::app>.

    $footer picks the footer variant: the legacy site had five (default,
    micro-projects, children, DISC, LEAP), each a component under
    components/site/footer/. $menu is how the nav menus open on hover
    (see site-nav.js).

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

    <title>{{ $title ?? config('app.name') }}</title>
    @if ($description)
        <meta name="description" content="{{ $description }}">
    @endif
    <link rel="icon" href="/favicon.ico">

    {{-- Body text on every page; the other faces are found in the stylesheet. --}}
    <link rel="preload" href="{{ Vite::asset('resources/fonts/lato/lato-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    {{-- Lets keyboard and screen-reader users jump past the navigation. --}}
    <a href="#main" class="skip-link">Skip to content</a>

    <x-site.header :$menu/>

    {{-- A flex item of the body column that grows, as the legacy page content did. --}}
    <main id="main" class="flex shrink-0 grow flex-col">
        {{ $slot }}
    </main>

    <x-dynamic-component :component="'site.footer.'.$footer"/>

    @livewireScripts
</body>
</html>
