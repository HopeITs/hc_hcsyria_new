@props(['moves'])

{{-- Appears over a list while rows are selected (the list's `selected` array); each button makes one move on all of them. $moves: ContentTransition values the account may make. --}}
<div class="bulk" x-bind:class="{ on: selected.length > 0 }" role="region" aria-label="Actions for the selection">
    <b x-text="selected.length + ' selected'"></b><span class="sp"></span>
    <button type="button" class="btn btn-g s" x-on:click="selected = []">Clear</button>
    @foreach ($moves as $move)
        @php $transition = \App\Enums\ContentTransition::from($move); @endphp
        <button type="button" @class(['btn s', 'btn-p' => $loop->first, 'btn-g' => ! $loop->first]) x-on:click="$wire.transitionMany(selected, '{{ $move }}').then(() => selected = [])">
            <x-admin.icon :name="$transition->icon()" />{{ $transition->label() }}
        </button>
    @endforeach
</div>
