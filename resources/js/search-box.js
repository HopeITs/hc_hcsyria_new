/*
 * A search box with suggestions (the typeahead.js box the legacy activity
 * lists had, and the one in the micro-projects filter bar). Typing shows the
 * titles that contain what is typed, with the match in bold, under a heading
 * for each kind of title.
 *
 * On a list page, choosing a suggestion, or pressing Enter or the button,
 * reloads the page with ?search=... (and the list's page numbers dropped, so
 * the results start at their first page). In a filter bar (`filter`) nothing
 * is sent: the text, typed or chosen, is announced as a `search-box` event
 * for the bar to take with it, the suggestions are fetched from the list on
 * first use ($wire.suggestions()), and each kind lists five at most, as
 * typeahead.js did.
 *
 * Whenever the box has `headings` its suggestions are fetched, on first use,
 * from the list around it ($wire.suggestions(), a list of titles for each
 * heading).
 */
const PAGE_PARAMS = ['ini_A', 'ini_H', 'ini_D', 'ini_L', 'ini_T'];
const FILTER_LIMIT = 5;

export default ({ suggestions = [], value = '', headings = null, filter = false } = {}) => ({
    query: value,
    open: false,
    cursor: -1,
    loaded: false,
    groups: headings ? [] : [{ heading: 'activities', titles: suggestions }],

    init() {
        if (filter) this.$watch('query', (query) => this.$dispatch('search-box', query));
    },

    /** Fetches the suggestions the first time the box is used. */
    async load() {
        if (! headings || this.loaded) return;

        this.loaded = true;

        const found = await this.$wire.suggestions();

        this.groups = headings.map((heading, i) => ({ heading, titles: found[i] ?? [] }));
    },

    /** The groups with the titles that match what is typed, each title with its place in the whole list. */
    get shown() {
        const query = this.query.trim().toLowerCase();

        if (query === '') return [];

        let index = 0;

        return this.groups
            .map(({ heading, titles }) => ({
                heading,
                items: titles
                    .filter((title) => title.toLowerCase().includes(query))
                    .slice(0, filter ? FILTER_LIMIT : undefined)
                    .map((title) => ({ title, i: index++ })),
            }))
            .filter((group) => group.items.length > 0);
    },

    get matches() {
        return this.shown.flatMap((group) => group.items.map((item) => item.title));
    },

    /** A suggestion split around the typed text, for bolding it. */
    parts(title) {
        const at = title.toLowerCase().indexOf(this.query.trim().toLowerCase());
        const length = this.query.trim().length;

        return at < 0 ? [{ text: title, match: false }] : [
            { text: title.slice(0, at), match: false },
            { text: title.slice(at, at + length), match: true },
            { text: title.slice(at + length), match: false },
        ].filter((part) => part.text !== '');
    },

    move(step) {
        const count = this.matches.length;

        if (count === 0) return;

        this.open = true;
        this.cursor = (this.cursor + step + count) % count;
    },

    go(term = this.query) {
        if (filter) {
            this.query = term;
            this.open = false;

            return;
        }

        const url = new URL(window.location.href);

        PAGE_PARAMS.forEach((param) => url.searchParams.delete(param));
        url.searchParams.set('search', term);
        window.location.href = url.href;
    },

    enter() {
        if (filter) return;

        this.go(this.cursor >= 0 && this.matches[this.cursor] ? this.matches[this.cursor] : this.query);
    },
});
