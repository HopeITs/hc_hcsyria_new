@props(['title', 'count', 'rows', 'section', 'tab', 'icon' => null])

{{--
    A small queue for one section on the dashboard (initiatives, successes,
    newsletter, Roots of Hope): what waits for this person, the first few rows as
    links, and the list opened on the same tab. Links only: the buttons of the
    shared actions belong to the page that manages the kind. $section is the route
    name without its page ("admin.news"); each row has id, number, name, and may
    have category, place and type.
--}}
<x-admin.panel :title="$title" :count="number_format($count)" :icon="$icon">
    @forelse ($rows as $row)
        <div class="q" wire:key="{{ $section }}-queue-{{ $row->id }}" style="grid-template-columns: minmax(0, 1fr) auto">
            <a href="{{ route($section.'.show', $row->id) }}" wire:navigate>
                <div class="nm">{{ $row->name }}</div>
                <div class="mt">
                    <span class="tnum">#{{ $row->number }}</span>
                    @if (! empty($row->category))<span>{{ $row->category }}</span>@endif
                    @if (! empty($row->place) && $row->place !== 'All')<span>{{ $row->place }}</span>@endif
                </div>
            </a>
            <a href="{{ route($section.'.show', $row->id) }}" wire:navigate class="btn btn-o s">Open</a>
        </div>
    @empty
        <x-admin.empty-state icon="check" title="All caught up">Nothing is waiting here.</x-admin.empty-state>
    @endforelse

    <div class="foot">
        <a href="{{ route($section.'.index', ['tab' => $tab]) }}" wire:navigate class="q-more">
            Open the list<x-admin.icon name="chev" class="i16" />
        </a>
    </div>
</x-admin.panel>
