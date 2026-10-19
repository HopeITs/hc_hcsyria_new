@props(['entries'])

{{-- The last things done to an item: what, who and when, newest first. --}}
<ol class="tl">
    @forelse ($entries as $entry)
        <li @class(['now' => $loop->first])>{{ $entry->operation }}<small>{{ $entry->by ?? 'Unknown' }} · {{ \Illuminate\Support\Carbon::parse($entry->at)->format('j M Y, H:i') }}</small></li>
    @empty
        <li class="muted">Nothing recorded yet.</li>
    @endforelse
</ol>
