/*
 * The tiles / rows switch (resources/views/components/ui/view-switch.blade.php).
 * Both layouts are on the page, so choosing one is only a switch: the list's
 * `view` property changes at once without a request (it reaches the server
 * with the next one) and the address follows, as ?view=grid, nothing for
 * tiles.
 */
export default () => ({
    set(view) {
        this.$wire.$set('view', view, false);

        const url = new URL(location.href);

        if (view === 'tiles') url.searchParams.delete('view'); else url.searchParams.set('view', view);

        history.replaceState(history.state, '', url);
    },
});
