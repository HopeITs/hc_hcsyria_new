{{--
    The "Load More" button under a list that grows (resources/css/ui/card-grid.css):
    calls loadMore() on the Livewire component around it, or the :method it
    names (a page with two lists that grow separately).
--}}
@props(['method' => 'loadMore'])

<div {{ $attributes->class('load-more') }}>
    <button type="button" class="load-more__button" wire:click="{{ $method }}">{{ $slot->isEmpty() ? 'Load More' : $slot }}</button>
</div>
