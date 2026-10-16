/*
 * The news page's two small scripts (resources/views/pages/⚡our-news.blade.php).
 *
 *  - newsColumns: how many stories a row of the list holds follows the window
 *    (4 on phones, 6 on tablets, 5 beyond), which the list needs to know to
 *    cut its rows, so the page is told at once and whenever the window is
 *    resized (legacy: an inline script on the Livewire component).
 *  - clock: the time and date on the cover photo, kept to the minute.
 */
const RESIZE_WAIT_MS = 50;
const MINUTE_MS = 60000;

const columnsFor = (width) => (width < 480 ? 4 : width < 992 ? 6 : 5);

export const newsColumns = () => ({
    timer: null,
    onResize: null,

    init() {
        this.onResize = () => {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.sync(), RESIZE_WAIT_MS);
        };

        this.sync();
        addEventListener('resize', this.onResize);
    },

    destroy() {
        clearTimeout(this.timer);
        removeEventListener('resize', this.onResize);
    },

    sync() {
        const size = columnsFor(innerWidth);

        if (this.$wire.chunkSize !== size) this.$wire.$set('chunkSize', size);
    },
});

export const clock = () => ({
    ticking: null,

    init() {
        this.show();
        this.ticking = setInterval(() => this.show(), MINUTE_MS);
    },

    destroy() {
        clearInterval(this.ticking);
    },

    show() {
        const now = new Date();
        const pad = (number) => String(number).padStart(2, '0');
        const month = now.toLocaleString('en-GB', { month: 'short' });

        this.$el.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())} | ${now.getDate()} ${month} ${now.getFullYear()}`;
    },
});
