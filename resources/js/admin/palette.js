/*
 * Ctrl K: jump to any page the account can open, or search the projects for
 * whatever was typed. The pages come from the server (Navigation), so the list
 * already respects roles.
 */
export default (pages = [], searchUrl = '') => ({
    open: false,
    query: '',
    index: 0,

    get results() {
        const q = this.query.trim().toLowerCase();
        const found = pages.filter((page) => page.label.toLowerCase().includes(q));

        return q === '' ? found : [...found, { label: `Search projects for “${this.query.trim()}”`, hint: 'Projects', url: `${searchUrl}?search=${encodeURIComponent(this.query.trim())}` }];
    },

    show() {
        this.open = true;
        this.query = '';
        this.index = 0;
        this.$nextTick(() => this.$refs.input.focus());
    },

    move(by) {
        const count = this.results.length;
        this.index = (this.index + by + count) % count;
    },

    go() {
        const target = this.results[this.index];
        if (target) window.location.href = target.url;
    },
});
