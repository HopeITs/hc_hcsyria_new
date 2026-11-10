@props(['title', 'count', 'rows', 'section', 'tab', 'icon' => null, 'caption' => null])

{{--
    A small queue for one section on the dashboard (initiatives, successes,
    newsletter, Roots of Hope): what waits for this person, the first few rows as
    links, and the list opened on the same tab. Links only: the buttons of the
    shared actions belong to the page that manages the kind. $section is the route
    name without its page ("admin.news"); each row has id, number, name, and may
    have category, place and type. The title is the section's short name and the
    caption says what the count counts, so the four cards line up whatever the words.
--}}
<section class="panel sq">
    <div class="sq-h">
        @if ($icon)<span class="sq-ic"><x-admin.icon :name="$icon" /></span>@endif
        <div class="sq-t">
            <h3>{{ $title }}</h3>
            @if ($caption)<p>{{ $caption }}</p>@endif
        </div>
        <span class="sq-n{{ $count > 0 ? ' on' : '' }}">{{ number_format($count) }}</span>
    </div>

    <div class="sq-body">
        @forelse ($rows as $row)
            <a href="{{ route($section.'.show', $row->id) }}" wire:navigate class="sq-row" wire:key="{{ $section }}-queue-{{ $row->id }}">
                <span class="nm">{{ $row->name }}</span>
                <span class="mt">
                    <span class="tnum">#{{ $row->number }}</span>
                    @if (! empty($row->category))<span>{{ $row->category }}</span>@endif
                    @if (! empty($row->place) && $row->place !== 'All')<span>{{ $row->place }}</span>@endif
                </span>
            </a>
        @empty
            <div class="sq-empty"><x-admin.icon name="check" />All caught up</div>
        @endforelse
    </div>

    <div class="foot">
        <a href="{{ route($section.'.index', ['tab' => $tab]) }}" wire:navigate class="q-more">
            Open the list<x-admin.icon name="chev" class="i16" />
        </a>
    </div>
</section>
