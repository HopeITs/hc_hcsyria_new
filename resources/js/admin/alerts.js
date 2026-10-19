/*
 * The toasts (components/admin/alerts) and the "are you sure" window
 * (components/admin/confirm). Pages never draw either: the server sends an
 * `admin-alert` event (SendsAlerts), or flashes one for the next page, and a
 * button asks for a confirmation with $store.confirm.ask({...}).
 */
let next = 0;

export default (flash = null) => ({
    items: [],

    init() {
        if (flash) this.add(flash);
    },

    add({ type = 'success', message, undo = null }) {
        const item = { id: ++next, type, message, undo };
        this.items.push(item);
        // An error stays until read; the rest go by themselves.
        if (type !== 'error') setTimeout(() => this.remove(item.id), undo ? 8000 : 5000);
    },

    remove(id) {
        this.items = this.items.filter((item) => item.id !== id);
    },

    undo(item) {
        const [method, ...args] = item.undo.call;
        window.Livewire.find(item.undo.component)?.$wire[method](...args);
        this.remove(item.id);
    },
});

export const confirmStore = () => ({
    open: false,
    title: '',
    body: '',
    action: 'Confirm',
    cancel: 'Cancel',
    run: null,

    ask({ title, body = '', action = 'Confirm', cancel = 'Cancel', run }) {
        Object.assign(this, { open: true, title, body, action, cancel, run });
    },

    accept() {
        const run = this.run;
        this.open = false;
        run?.();
    },
});
