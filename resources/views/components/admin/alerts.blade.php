{{-- Where toasts appear. Pages send them with SendsAlerts; a redirect carries one in the session. --}}
<div class="alerts" x-data="adminAlerts(@js(session('admin-alert')))" x-on:admin-alert.window="add($event.detail)" aria-live="polite">
    <template x-for="item in items" x-bind:key="item.id">
        <div class="alert alert-enter" x-bind:class="item.type" x-bind:role="item.type === 'error' ? 'alert' : 'status'">
            <p x-text="item.message"></p>
            <button type="button" x-show="item.undo" x-on:click="undo(item)">Undo</button>
            <button type="button" class="x" x-on:click="remove(item.id)" aria-label="Dismiss"><x-admin.icon name="x" /></button>
        </div>
    </template>
</div>
