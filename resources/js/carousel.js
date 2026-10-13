/*
 * Carousels: the Bootstrap carousel the legacy pages used, as one Alpine
 * component (12 on the pages to port: the cover photo, the values, the
 * vision text and photos, project photos).
 *
 * What it keeps from Bootstrap: slides advance on a timer; hovering pauses
 * the timer (unless `pause` is off); previous / next / dots change slide
 * and restart the timer; the first dot is active at the start. Whether a
 * change slides sideways (0.6s) or simply switches is decided by each item
 * (see resources/css/ui/carousel.css), as it was by each .carousel-item.
 *
 * Several panes can share one scope, which keeps them on the same slide —
 * how the vision text and its photos were linked with a script before.
 * `keys` also moves the slide on the left / right arrow keys, which two of
 * the legacy carousels did page-wide.
 */

const SLIDE_MS = 600;

export default ({ count, interval = 5000, pause = true, keys = false } = {}) => ({
    count,
    index: 0,
    leaving: null,
    direction: 1,
    moved: false,
    hovered: false,
    timer: null,
    leaveTimer: null,

    init() {
        // Bootstrap's data-api started the timer when the window had loaded, not before.
        if (document.readyState === 'complete') {
            this.restart();
        } else {
            addEventListener('load', () => this.restart(), { once: true });
        }
    },

    restart() {
        clearInterval(this.timer);

        if (this.count > 1 && interval) {
            this.timer = setInterval(() => {
                if (! (pause && this.hovered)) this.next();
            }, interval);
        }
    },

    go(target, direction) {
        if (target === this.index || this.count < 2) return;

        this.direction = direction ?? (target > this.index ? 1 : -1);
        this.leaving = this.index;
        this.index = target;
        this.moved = true;

        clearTimeout(this.leaveTimer);
        this.leaveTimer = setTimeout(() => { this.leaving = null; }, SLIDE_MS);

        this.restart();
    },

    next() {
        this.go((this.index + 1) % this.count, 1);
    },

    prev() {
        this.go((this.index - 1 + this.count) % this.count, -1);
    },

    hover(on) {
        this.hovered = on;
    },

    arrow(key) {
        if (! keys) return;

        if (key === 'ArrowRight') this.next();
        else if (key === 'ArrowLeft') this.prev();
    },

    // Classes for one item; the animation classes only apply once something has changed.
    item(i) {
        return {
            'is-active': this.index === i,
            'is-leaving': this.leaving === i,
            'is-moving': this.moved,
            'from-next': this.direction > 0,
            'from-prev': this.direction < 0,
        };
    },
});
