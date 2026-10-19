@props(['title' => 'Dashboard'])

{{--
    The one layout of the staff dashboard (layouts::admin for a full-page
    Livewire component). Drawn in the Tracking app's design: a white sidebar with
    a red current item, a top bar, light and dark. Everything that is shared lives
    in components/admin (icons, alerts, confirm window, Ctrl K), resources/css/admin.css
    and resources/js/admin.js; a page only fills the slot.
--}}
@php
    $user = auth()->user();
    $groups = \App\Support\Admin\Navigation::groups($user);
    $roles = \App\Queries\AdminAccessQuery::roleNames($user);
    $abilities = \App\Queries\AdminAccessQuery::capabilities($user);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ config('app.name') }} Dashboard</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="preload" href="{{ Vite::asset('resources/fonts/somar-sans/SomarSans-Regular.woff2') }}" as="font" type="font/woff2" crossorigin>
    {{-- Before paint, so a dark page never flashes white. --}}
    <script>
        try { if (localStorage.getItem('admin.theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}
    </script>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
</head>
<body>
    <a href="#content" class="sr">Skip to content</a>
    <x-admin.sprite />

    <div class="app" x-data="adminShell" x-bind:class="{ collapsed, drawer }">
        <aside class="side" aria-label="Application navigation" x-on:click.outside="drawer = false">
            <button type="button" class="edge" x-on:click="toggleSidebar()" aria-label="Collapse or expand the sidebar"><x-admin.icon name="chev" class="i16" /></button>
            <div class="side-h">
                <a href="{{ route('admin.dashboard') }}" wire:navigate class="logo" aria-label="Dashboard">HC</a>
                <div class="wm">Hope Center<small>Dashboard</small></div>
            </div>
            <nav class="menu" aria-label="Primary">
                @foreach ($groups as $group)
                    <div class="grp">
                        <div class="gh">{{ $group['heading'] }}</div>
                        @foreach ($group['items'] as $item)
                            <a href="{{ route($item['route']) }}" wire:navigate class="item" title="{{ $item['label'] }}" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                                <x-admin.icon :name="$item['icon']" /><span class="t">{{ $item['label'] }}</span>
                                @if ($item['badge'])<span class="b">{{ $item['badge'] }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>
        </aside>

        <div class="main">
            <header class="top">
                <button type="button" class="ibtn mbtn" x-on:click.stop="drawer = true" aria-label="Open the menu"><x-admin.icon name="menu" /></button>
                <button type="button" class="search" x-on:click="$dispatch('admin-palette')" aria-label="Search"><x-admin.icon name="search" /><span style="flex: 1; text-align: start">Search projects, pages…</span><kbd>Ctrl K</kbd></button>
                <div class="sp"></div>
                <div class="pillgrp">
                    <button type="button" class="ibtn" x-on:click="toggleTheme()" aria-label="Switch between light and dark">
                        <x-admin.icon name="sun" x-show="dark" x-cloak /><x-admin.icon name="moon" x-show="! dark" />
                    </button>
                </div>
                <x-admin.popover label="Your account" width="19rem">
                    <x-slot:trigger class="user">
                        <span class="av">{{ \Illuminate\Support\Str::of($user->name)->explode(' ')->map(fn ($word) => mb_substr($word, 0, 1))->take(2)->implode('') }}</span>
                        <div style="text-align: start"><b>{{ $user->name }}</b><small>{{ implode(', ', $roles) }}</small></div>
                        <x-admin.icon name="chevd" class="i16" />
                    </x-slot:trigger>
                    <div class="ph"><b>{{ $user->name }}</b><div class="muted" style="font-size: 12px">{{ $user->email }}</div></div>
                    <div style="padding: 10px 14px 0; font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--ink-m)">What your role can do</div>
                    <div class="perm">
                        @foreach (\App\Enums\AdminCapability::cases() as $capability)
                            <span class="{{ in_array($capability, $abilities, true) ? 'y' : 'n' }}">{{ $capability->label() }}</span>
                        @endforeach
                    </div>
                    <div class="sep"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="mi danger"><x-admin.icon name="out" />Log out</button>
                    </form>
                </x-admin.popover>
            </header>

            <main class="page" id="content">{{ $slot }}</main>
        </div>
    </div>

    <x-admin.alerts />
    <x-admin.confirm />
    <x-admin.palette :pages="\App\Support\Admin\Navigation::pages($user)" :search-url="route('admin.projects.index')" />
    @livewireScripts
</body>
</html>
