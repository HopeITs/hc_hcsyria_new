@props(['placeholder' => 'Search', 'filtered' => false])

{{-- The search box and filters of a list, bound to the page's `search` property; the filters are the slot. --}}
<div style="padding: 14px 16px; border-bottom: 1px solid var(--line)">
    <div class="bar">
        <div class="field" style="flex: 2; min-width: 220px">
            <label for="f-search">Search</label>
            <div class="inwrap"><x-admin.icon name="search" class="i16" /><input id="f-search" type="search" class="inp" wire:model.live.debounce.300ms="search" placeholder="{{ $placeholder }}" maxlength="100"></div>
        </div>
        {{ $slot }}
        @if ($filtered)
            <button type="button" class="btn btn-g" wire:click="clearFilters">Clear filters</button>
        @endif
    </div>
</div>
