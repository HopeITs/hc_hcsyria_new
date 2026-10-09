<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&family=Martel:wght@400;600;800&display=swap"
          rel="stylesheet">

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
