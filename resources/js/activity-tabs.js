/*
 * Tabs over lists of activities (one per town). The chosen tab is kept in
 * the address as ?tab=..., as the legacy page did, so a reload or a shared
 * link opens the same one; moving between pages of a list keeps it too
 * because the pager's links are built from that address. A page opened
 * without ?tab= gets it added on load, as before (replacing the history entry
 * rather than adding one, so Back still leaves the page).
 */
export default ({ tab }) => ({
    tab,

    init() {
        const url = new URL(window.location.href);

        if (! url.searchParams.has('tab')) {
            url.searchParams.set('tab', tab);
            history.replaceState({}, '', url);
        }
    },

    select(key) {
        this.tab = key;

        const url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        history.pushState({}, '', url);
    },
});
