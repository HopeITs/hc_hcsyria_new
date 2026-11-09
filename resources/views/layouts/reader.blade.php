@props(['title' => null])

{{-- A bare layout for the flipbook reader: the book fills the window, no header or footer. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#2b2b2b">
    <title>{{ $title ? __($title) : config('app.name') }}</title>
    <link rel="icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flipbook-body">
    {{ $slot }}

    @livewireScripts
</body>
</html>
