/*
 * The related-posts carousel (legacy: Owl Carousel, two items at a time, looping,
 * arrows and dots, no mouse drag). The stage holds the items with two copies of
 * the first ones after them and two of the last ones before, as Owl made them,
 * so the loop slides on instead of rewinding. Past the end it jumps back, without
 * a slide, once the move has finished. Dots stand for pages of two items.
 *
 * With two items or fewer Owl did not loop and did not move; the arrows and
 * dots were still drawn (legacy script removed their "disabled" class).
 */
const PER_VIEW = 2;
const SLIDE_MS = 250;

export default ({ count }) => ({
    count,
    position: 0,
    jumping: false,

    get offset() {
        return count > PER_VIEW ? PER_VIEW + this.position : 0;
    },

    get page() {
        return Math.floor(((this.position % count) + count) % count / PER_VIEW);
    },

    get pages() {
        return Math.ceil(count / PER_VIEW);
    },

    move(by) {
        if (count <= PER_VIEW) return;

        this.jumping = false;
        this.position += by;

        // Landed on a copy: once the slide has finished, show the real item without moving.
        setTimeout(() => {
            if (this.position < 0 || this.position >= count) {
                this.jumping = true;
                this.position = ((this.position % count) + count) % count;
            }
        }, SLIDE_MS);
    },

    to(page) {
        if (count <= PER_VIEW) return;

        this.jumping = false;
        this.position = page * PER_VIEW;
    },
});
