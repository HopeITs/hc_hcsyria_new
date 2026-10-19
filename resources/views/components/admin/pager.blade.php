@props(['paginator', 'noun' => 'projects'])

{{-- The footer under a list: how many are shown and page buttons that drive Livewire's WithPagination. --}}
@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = collect(range(max(1, $current - 2), min($last, $current + 2)));
@endphp
<div class="foot">
    <span>
        @if ($paginator->total() === 0)
            No {{ $noun }}
        @else
            Showing {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }} of {{ number_format($paginator->total()) }} {{ $noun }}
        @endif
    </span>
    @if ($last > 1)
        <nav class="pager" aria-label="Pages">
            <button type="button" wire:click="previousPage" @disabled($current === 1) aria-label="Previous page"><x-admin.icon name="chev" class="i16" /></button>
            @if ($pages->first() > 1)<button type="button" wire:click="gotoPage(1)">1</button>@if ($pages->first() > 2)<span class="faint">…</span>@endif @endif
            @foreach ($pages as $page)
                <button type="button" wire:click="gotoPage({{ $page }})" @if ($page === $current) aria-current="page" @endif>{{ $page }}</button>
            @endforeach
            @if ($pages->last() < $last)@if ($pages->last() < $last - 1)<span class="faint">…</span>@endif<button type="button" wire:click="gotoPage({{ $last }})">{{ number_format($last) }}</button>@endif
            <button type="button" wire:click="nextPage" @disabled($current === $last) aria-label="Next page"><x-admin.icon name="chev" class="i16" style="transform: scaleX(-1)" /></button>
        </nav>
    @endif
</div>
