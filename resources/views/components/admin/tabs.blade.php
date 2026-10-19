@props(['tabs', 'counts', 'current'])

{{-- The state tabs of a list: each is a key of admin.tabs with its count; choosing one sets the page's `tab` and clears the selection. --}}
<div class="tabs" role="tablist" aria-label="State">
    @foreach ($tabs as $key)
        <button type="button" role="tab" class="tab" aria-selected="{{ $current === $key ? 'true' : 'false' }}" wire:click="$set('tab', '{{ $key }}')" x-on:click="selected = []">
            {{ __('admin.tabs.'.$key) }}<span class="badge">{{ number_format($counts[$key]) }}</span>
        </button>
    @endforeach
</div>
