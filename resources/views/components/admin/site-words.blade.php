@props(['words', 'current', 'changedOnly'])

{{-- The Words half of Site content: every line of text on a public page, each one a field to rewrite. Used inside pages::admin.site, whose methods it calls. --}}
<div class="stack-s">
    <div class="sc-words-bar">
        <div class="inwrap" style="flex: 1; min-width: 12rem">
            <x-admin.icon name="search" class="i16" />
            <input type="search" class="inp" wire:model.live.debounce.250ms="search" placeholder="Find words on this page" aria-label="Find words on this page">
        </div>
        <label class="row-s" style="gap: 6px"><input type="checkbox" class="cb" wire:model.live="changedOnly"> Rewritten only</label>
        <button type="button" class="btn btn-o s" wire:click="rescan" wire:loading.attr="disabled" wire:target="rescan"><x-admin.icon name="log" class="i16" /><span wire:loading.remove wire:target="rescan">Read page again</span><span wire:loading wire:target="rescan">Reading…</span></button>
    </div>
    <p class="muted" style="margin: 0">{{ $current['blurb'] }} Each line below is a stretch of text the visitor reads. A sentence broken by bold words or a line break shows as separate lines.</p>

    @if ($words['failed'])
        <x-admin.empty-state icon="warn" title="This page could not be read">The page did not load when the dashboard asked for it. Try “Read page again”, or open the page to see what is wrong.@if ($words['error'])<div class="muted" style="margin-top: 10px; font-size: 12px; max-width: 46rem; word-break: break-word">Detail: {{ $words['error'] }}</div>@endif</x-admin.empty-state>
    @elseif ($words['shown'] === 0)
        <x-admin.empty-state icon="doc" title="No words found">This page has no text of its own.</x-admin.empty-state>
    @elseif ($words['rows'] === [])
        <x-admin.empty-state icon="search" title="No line matches">Try another search.</x-admin.empty-state>
    @else
        <div class="sc-lines">
            @foreach ($words['rows'] as $row)
                <article wire:key="line-{{ $row['hash'] }}" @class(['sc-line', 'is-changed' => $row['changed']])>
                    @if ($row['changed'])<div class="was"><span class="pill p-warn"><i></i>Rewritten</span> Original: <q>{{ $row['original'] }}</q></div>@endif
                    <textarea class="ta" rows="{{ $row['long'] ? 3 : 1 }}" maxlength="1500" wire:model="edits.{{ $row['hash'] }}" aria-label="Words: {{ \Illuminate\Support\Str::limit($row['original'], 60) }}"></textarea>
                    <div class="act">
                        <button type="button" class="btn btn-p s" wire:click="saveLine('{{ $row['hash'] }}')" wire:loading.attr="disabled" wire:target="saveLine('{{ $row['hash'] }}')" x-bind:disabled="$wire.edits['{{ $row['hash'] }}'] === @js($row['changed'] ? null : $row['original'])"><x-admin.icon name="check" class="i16" />Save</button>
                        @if ($row['changed'])<button type="button" class="btn btn-g s" wire:click="resetLine('{{ $row['hash'] }}')"><x-admin.icon name="out" class="i16" />Back to the original</button>@endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    @if ($words['gone'] !== [])
        <x-admin.panel title="No longer on the page" :count="count($words['gone'])">
            <p class="muted" style="margin: 0; padding: 0 16px 8px">These were rewritten, but the original words are no longer on the page (the page was changed). They have no effect. Remove them if they are not needed.</p>
            @foreach ($words['gone'] as $gone)
                <div class="sc-line" wire:key="gone-{{ $gone['hash'] }}" style="padding: 10px 16px">
                    <div class="was">Was: <q>{{ $gone['original'] }}</q> → <q>{{ $gone['value'] }}</q></div>
                    <div class="act"><button type="button" class="btn btn-g s" wire:click="resetLine('{{ $gone['hash'] }}')"><x-admin.icon name="trash" class="i16" />Remove</button></div>
                </div>
            @endforeach
        </x-admin.panel>
    @endif
</div>
