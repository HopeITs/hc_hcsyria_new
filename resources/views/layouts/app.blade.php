<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    {{-- Body text on every page; the other faces are found in the stylesheet. --}}
    <link rel="preload" href="{{ Vite::asset('resources/fonts/lato/lato-400-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite('resources/css/app.css')
    @livewireStyles
</head>
<body class="flex min-h-full flex-col">
    {{-- Lets keyboard and screen-reader users jump past the navigation. --}}
    <a href="#main" class="skip-link">Skip to content</a>

    <x-site.header/>

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-site.footer/>

    @livewireScripts
</body>
</html>
