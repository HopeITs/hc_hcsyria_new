@props(['pages', 'searchUrl'])

{{-- Ctrl K. Opened by the search button in the top bar (it dispatches `admin-palette`). --}}
<div class="scrim" x-data="adminPalette(@js($pages), @js($searchUrl))" :class="{ open }"
    x-on:admin-palette.window="show()" x-on:keydown.window.ctrl.k.prevent="show()" x-on:keydown.window.meta.k.prevent="show()"
    x-on:keydown.escape.window="open = false" x-on:click.self="open = false">
    <div class="dlg cmd" role="dialog" aria-modal="true" aria-label="Search" x-show="open" x-cloak
        x-on:keydown.arrow-down.prevent="move(1)" x-on:keydown.arrow-up.prevent="move(-1)" x-on:keydown.enter.prevent="go()">
        <input x-ref="input" x-model="query" x-on:input="index = 0" placeholder="Jump to a page, or search projects…" autocomplete="off">
        <ul>
            <template x-for="(result, i) in results" x-bind:key="result.url">
                <li x-bind:aria-selected="i === index" x-on:click="index = i; go()" style="cursor: pointer"><span x-text="result.label"></span><small x-text="result.hint"></small></li>
            </template>
        </ul>
        <div class="ft" style="justify-content: flex-start; gap: 1rem; font-size: 12px; color: var(--ink-m)"><span><kbd>↑</kbd> <kbd>↓</kbd> move</span><span><kbd>Enter</kbd> open</span><span><kbd>Esc</kbd> close</span></div>
    </div>
</div>
