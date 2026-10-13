/*
 * The photo with round buttons under it (DISC's four programmes, and the
 * same on the other programme pages): pointing at or tapping a button swaps
 * the photo and, in the dark panel that fades over the photo while a button
 * is pointed at, the title and text.
 *
 * Until a button is first used, the panel shows `intro`, a title and text
 * the legacy pages wrote into the markup (separately for wide and narrow
 * screens) that differ from what the buttons then show. The first category
 * is the one marked as selected.
 */
export default ({ categories, intro }) => ({
    categories,
    intro,
    selected: 0,
    used: false,

    select(index) {
        this.selected = index;
        this.used = true;
    },

    /** What the panel shows: `size` is 'wide' or 'narrow', the two layouts the legacy markup had. */
    panel(size) {
        return this.used ? this.categories[this.selected] : this.intro[size];
    },
});
