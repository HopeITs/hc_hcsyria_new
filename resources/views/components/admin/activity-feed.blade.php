@props(['rows', 'empty' => 'Nothing has changed yet.'])

{{--
    The last changes to the micro projects, newest first: what was done, to which
    project, by whom and how long ago.
--}}
<div class="feed">
    @forelse ($rows as $row)
        <li>
            <span class="feed-ic"><x-admin.icon name="edit" /></span>
            <div class="feed-body">
                <p>{{ $row->operation }}</p>
                <small>
                    @if ($row->number)<span class="tnum">#{{ $row->number }}</span>@endif
                    @if ($row->by)<span>{{ $row->by }}</span>@endif
                    @if ($row->at)<span>{{ \Illuminate\Support\Carbon::parse($row->at)->diffForHumans() }}</span>@endif
                </small>
            </div>
        </li>
    @empty
        <li class="muted feed-empty">{{ $empty }}</li>
    @endforelse
</div>
