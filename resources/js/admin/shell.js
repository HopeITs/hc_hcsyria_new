/* The frame: the sidebar (collapsed on desktop, a drawer on a phone) and light/dark. Both remembered per browser. */
const remember = (key, value) => {
    try {
        localStorage.setItem(key, value);
    } catch {
        // Private mode: it still works for this visit.
    }
};

export default () => ({
    collapsed: document.documentElement.dataset.sidebar === 'collapsed',
    drawer: false,
    dark: document.documentElement.classList.contains('dark'),

    toggleSidebar() {
        this.collapsed = ! this.collapsed;
        remember('admin.sidebar', this.collapsed ? 'collapsed' : 'open');
    },

    toggleTheme() {
        this.dark = ! this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        remember('admin.theme', this.dark ? 'dark' : 'light');
    },
});
