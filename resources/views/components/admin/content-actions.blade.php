@props(['item', 'kind' => 'project', 'size' => 's'])

{{--
    What the signed-in account may do to one item (a project, an initiative), as
    buttons: the main move (Publish, or Mark English checked for whoever checks
    English), Edit, and a menu for the rest. The rules are ContentActions (the same
    as the policies); every button only calls the page's ManagesContent methods,
    which check again. Used by the lists, the dashboard queue and an item's own page.
--}}
@php
    use App\Enums\ContentKind;
    use App\Enums\ContentTransition as Move;

    $kind = ContentKind::from($kind instanceof ContentKind ? $kind->value : $kind);
    $can = \App\Support\Admin\ContentActions::for(auth()->user(), $kind, $item);
    $primary = collect([Move::Publish, Move::CheckEnglish])->first(fn (Move $move) => in_array($move, $can['transitions'], true));
    $menu = array_values(array_filter($can['transitions'], fn (Move $move) => $move !== $primary));
    $blockedReason = $primary ? ($can['blocked'][$primary->value] ?? null) : null;
    $size = $size === 's' ? 's' : '';
@endphp

<div class="acts" x-on:click.stop>
    @if ($primary)
        <button type="button" @class(['btn btn-p', $size]) wire:click="moveItem({{ $item->id }}, '{{ $primary->value }}')" wire:loading.attr="disabled" wire:target="moveItem" @disabled($blockedReason) @if ($blockedReason) title="{{ __('admin.refusals.not_ready', ['number' => $item->number, 'missing' => $blockedReason, 'noun' => $kind->noun()]) }}" @endif>
            <x-admin.icon :name="$primary->icon()" />{{ $primary->label() }}
        </button>
    @endif

    @if ($can['edit'])
        <a href="{{ route($kind->route('edit'), $item->id) }}" wire:navigate @class(['btn btn-o', $size])><x-admin.icon name="edit" />Edit</a>
    @endif

    @if ($menu !== [] || $can['delete'])
        <x-admin.popover label="More actions">
            <x-slot:trigger class="btn btn-g {{ $size }}" style="width: 2rem; padding: 0"><x-admin.icon name="dots" /></x-slot:trigger>
            @foreach ($menu as $move)
                <button type="button" class="mi" wire:click="moveItem({{ $item->id }}, '{{ $move->value }}')"><x-admin.icon :name="$move->icon()" />{{ $move->label() }}</button>
            @endforeach
            @if ($can['delete'])
                @if ($menu !== [])<div class="sep"></div>@endif
                <button type="button" class="mi danger"
                    x-on:click="$store.confirm.ask({ title: @js(__('admin.confirm.delete_title', ['number' => $item->number, 'noun' => $kind->noun()])), body: @js(__('admin.confirm.delete_body', ['noun' => $kind->noun()])), action: @js(__('admin.confirm.delete_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.deleteItem({{ $item->id }}) })">
                    <x-admin.icon name="trash" />{{ __('admin.confirm.delete_action') }}
                </button>
            @endif
        </x-admin.popover>
    @endif
</div>
