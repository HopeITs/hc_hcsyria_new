/*
 * A search box with suggestions (the typeahead.js box the legacy activity
 * lists had). Typing shows the titles that contain what is typed, with the
 * match in bold; choosing one, or pressing Enter or the button, reloads the
 * page with ?search=... (and the list's page numbers dropped, so the results
 * start at their first page).
 */
const PAGE_PARAMS = ['ini_A', 'ini_H', 'ini_D', 'ini_L', 'ini_T'];

export default ({ suggestions, value = '' }) => ({
    query: value,
    open: false,
    cursor: -1,

    get matches() {
        const query = this.query.trim().toLowerCase();

        if (query === '') return [];

        return suggestions.filter((title) => title.toLowerCase().includes(query));
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
        const url = new URL(window.location.href);

        PAGE_PARAMS.forEach((param) => url.searchParams.delete(param));
        url.searchParams.set('search', term);
        window.location.href = url.href;
    },

    enter() {
        this.go(this.cursor >= 0 && this.matches[this.cursor] ? this.matches[this.cursor] : this.query);
    },
});
