{{--
    The "Load More" button under a list that grows (resources/css/ui/card-grid.css):
    calls loadMore() on the Livewire component around it.
--}}
<div {{ $attributes->class('load-more') }}>
    <button type="button" class="load-more__button" wire:click="loadMore">{{ $slot->isEmpty() ? 'Load More' : $slot }}</button>
</div>
