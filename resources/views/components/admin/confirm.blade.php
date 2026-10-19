{{-- The one "are you sure" window; open it from any button with $store.confirm.ask({ title, body, action, run }). --}}
<div class="scrim" x-data :class="{ open: $store.confirm.open }" x-on:keydown.escape.window="$store.confirm.open = false" x-on:click.self="$store.confirm.open = false">
    <div class="dlg" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" style="margin-top: 8vh" x-show="$store.confirm.open" x-cloak>
        <div class="in">
            <h3 id="confirm-title" x-text="$store.confirm.title"></h3>
            <p class="muted" style="margin: 0" x-text="$store.confirm.body"></p>
        </div>
        <div class="ft">
            <button type="button" class="btn btn-o" x-on:click="$store.confirm.open = false" x-text="$store.confirm.cancel"></button>
            <button type="button" class="btn btn-p" x-on:click="$store.confirm.accept()" x-text="$store.confirm.action"></button>
        </div>
    </div>
</div>
