/*
 * The site navigation's menu behaviour: public/js/main.js, a script some
 * legacy pages add, and Bootstrap's dropdown, as one Alpine component.
 *
 * There were two hover behaviours in the legacy site, depending on the page:
 *
 *  - 'instant' (home, who-we-are, the programme pages — 22 of them): a script
 *    pushed by the page adds Bootstrap's .show class the moment the pointer
 *    enters a menu, if the window was wider than 992px when the page loaded.
 *    The menu appears at once and leaves at once. Narrower than that, hover
 *    falls back to the slide.
 *
 *  - 'slide' (pages without that script, such as the 404): main.js waits
 *    250ms after the pointer enters, then jQuery slideDown()s the menu;
 *    leaving waits 100ms, then slideUp()s it. A slide is jQuery's default —
 *    400ms, "swing" easing — and animates height and the vertical padding and
 *    margin together, not just height. Moving back in mid-slide jumps the
 *    running slide to its end first (stop(true, true)).
 *
 * Clicking (touch screens, and the mobile menu) toggles a menu instantly, as
 * Bootstrap did; clicking elsewhere or pressing Escape closes it.
 *
 * Overlay mode (the legacy navbar_reverse): over a full-bleed hero the bar is
 * transparent with a white logo and white links. It stays that way until the
 * page has scrolled to 300px above the element named by `overlayUntil`
 * (measured once, when the window loads, as the legacy script did), then
 * turns solid, and back again when scrolled up.
 *
 * One departure: with prefers-reduced-motion the slides are instant. jQuery
 * ignored that setting.
 */

const OPEN_DELAY = 250;
const CLOSE_DELAY = 100;
const SLIDE_MS = 400;

// jQuery "swing" is a half cosine, 0.5 - cos(pi * p) / 2: sine ease-in-out.
const SWING = 'cubic-bezier(0.37, 0, 0.63, 1)';

const SLID = ['height', 'paddingTop', 'paddingBottom', 'marginTop', 'marginBottom'];

function slide(element, direction) {
    const style = getComputedStyle(element);
    const open = Object.fromEntries(SLID.map((property) => [property, style[property]]));
    const shut = Object.fromEntries(SLID.map((property) => [property, '0px']));
    const frames = (direction === 'down' ? [shut, open] : [open, shut]).map((frame) => ({ ...frame, overflow: 'hidden' }));

    return element.animate(frames, {
        duration: matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : SLIDE_MS,
        easing: SWING,
        fill: 'both',
    });
}

export default ({ mode = 'instant', overlayUntil = null } = {}) => ({
    open: false,
    overlay: overlayUntil !== null,
    menu: null,
    hover: false,
    pointerHovers: false,
    instant: false,
    openTimer: null,
    closeTimer: null,
    slideAnimation: null,

    init() {
        this.pointerHovers = matchMedia('(hover: hover)').matches;
        // The legacy script tested the window width once, when the page loaded.
        this.instant = mode === 'instant' && window.innerWidth > 992;

        if (overlayUntil !== null) {
            this.watchOverlay(overlayUntil);
        }
    },

    watchOverlay(selector) {
        const measure = () => {
            const target = document.querySelector(selector);
            if (target === null) return;

            const threshold = target.getBoundingClientRect().top + scrollY - 300;
            // Overlay until the first scroll decides otherwise: on a narrow
            // screen the threshold can be negative, and the legacy page did
            // not look at it until the visitor scrolled.
            addEventListener('scroll', () => { this.overlay = scrollY <= threshold; }, { passive: true });
        };

        document.readyState === 'complete' ? measure() : addEventListener('load', measure, { once: true });
    },

    enter(name) {
        if (! this.pointerHovers) return;

        clearTimeout(this.closeTimer);
        this.settle();

        if (this.menu === name) return;

        if (this.instant) {
            this.hover = true;
            this.menu = name;

            return;
        }

        this.openTimer = setTimeout(() => {
            this.hover = true;
            this.menu = name;
            this.$nextTick(() => {
                this.slideAnimation = slide(this.$refs[name], 'down');
            });
        }, OPEN_DELAY);
    },

    leave() {
        if (! this.pointerHovers) return;

        clearTimeout(this.openTimer);

        if (this.menu === null || ! this.hover) return;

        if (this.instant) {
            this.close();

            return;
        }

        this.closeTimer = setTimeout(() => {
            this.settle();

            const animation = slide(this.$refs[this.menu], 'up');
            this.slideAnimation = animation;
            animation.finished.then(() => {
                if (this.slideAnimation !== animation) return;
                this.menu = null;
                this.hover = false;
                this.slideAnimation = null;
                this.$nextTick(() => animation.cancel());
            }).catch(() => {});
        }, CLOSE_DELAY);
    },

    // stop(true, true): jump a running slide to its end.
    settle() {
        this.slideAnimation?.finish();
    },

    toggle(name) {
        clearTimeout(this.openTimer);
        clearTimeout(this.closeTimer);
        this.settle();
        this.hover = false;
        this.menu = this.menu === name ? null : name;
    },

    close() {
        clearTimeout(this.openTimer);
        clearTimeout(this.closeTimer);
        this.settle();
        this.menu = null;
        this.hover = false;
    },
});
