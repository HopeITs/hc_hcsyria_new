@props(['label'])

{{--
    Replaces the Bootstrap .dropdown / data-bs-toggle pattern the legacy filter
    bar used, with the same appearance: a light button showing the current
    selection, and a full-width menu beneath it.
--}}
<div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false" class="relative">
    <button type="button" @click="open = !open" :aria-expanded="open.toString()"
            class="flex w-56 items-center justify-between gap-2 border border-ink-200 bg-[#f7f7f7] px-3 py-2 text-sm text-[#616262]">
        <span class="truncate">{{ $label }}</span>
        <svg class="size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.2 7.3a1 1 0 0 1 1.4 0L10 10.7l3.4-3.4a1 1 0 1 1 1.4 1.4l-4.1 4.1a1 1 0 0 1-1.4 0L5.2 8.7a1 1 0 0 1 0-1.4Z" clip-rule="evenodd"/>
        </svg>
    </button>

    <div x-show="open" x-cloak x-transition.opacity.duration.150ms @click="open = false"
         class="absolute left-0 top-full z-30 max-h-80 w-56 overflow-y-auto border border-ink-200 bg-white shadow-lg">
        {{ $slot }}
    </div>
</div>
