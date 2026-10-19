@props(['project', 'size' => 's'])

{{--
    What the signed-in account may do to one project, as buttons: the main move
    (Publish, or Mark English checked for whoever checks English), Edit, and a
    menu for the rest. The rules are ProjectActions (the same as ProjectPolicy);
    every button only calls the page's ManagesProjects methods, which check again.
    Used by the project list, the dashboard queue and the project's own page.
--}}
@php
    use App\Enums\ProjectTransition as Move;

    $can = \App\Support\Admin\ProjectActions::for(auth()->user(), $project);
    $primary = collect([Move::Publish, Move::CheckEnglish])->first(fn (Move $move) => in_array($move, $can['transitions'], true));
    $menu = array_values(array_filter($can['transitions'], fn (Move $move) => $move !== $primary));
    $blockedReason = $primary ? ($can['blocked'][$primary->value] ?? null) : null;
    $size = $size === 's' ? 's' : '';
@endphp

<div class="acts" x-on:click.stop>
    @if ($primary)
        <button type="button" @class(['btn btn-p', $size]) wire:click="moveProject({{ $project->id }}, '{{ $primary->value }}')" wire:loading.attr="disabled" wire:target="moveProject" @disabled($blockedReason) @if ($blockedReason) title="{{ __('admin.refusals.not_ready', ['number' => $project->number, 'missing' => $blockedReason]) }}" @endif>
            <x-admin.icon :name="$primary->icon()" />{{ $primary->label() }}
        </button>
    @endif

    @if ($can['edit'])
        <a href="{{ route('admin.projects.edit', $project->id) }}" wire:navigate @class(['btn btn-o', $size])><x-admin.icon name="edit" />Edit</a>
    @endif

    @if ($menu !== [] || $can['delete'])
        <x-admin.popover label="More actions">
            <x-slot:trigger class="btn btn-g {{ $size }}" style="width: 2rem; padding: 0"><x-admin.icon name="dots" /></x-slot:trigger>
            @foreach ($menu as $move)
                <button type="button" class="mi" wire:click="moveProject({{ $project->id }}, '{{ $move->value }}')"><x-admin.icon :name="$move->icon()" />{{ $move->label() }}</button>
            @endforeach
            @if ($can['delete'])
                @if ($menu !== [])<div class="sep"></div>@endif
                <button type="button" class="mi danger"
                    x-on:click="$store.confirm.ask({ title: @js(__('admin.confirm.delete_title', ['number' => $project->number])), body: @js(__('admin.confirm.delete_body')), action: @js(__('admin.confirm.delete_action')), cancel: @js(__('admin.confirm.cancel')), run: () => $wire.deleteProject({{ $project->id }}) })">
                    <x-admin.icon name="trash" />{{ __('admin.confirm.delete_action') }}
                </button>
            @endif
        </x-admin.popover>
    @endif
</div>
