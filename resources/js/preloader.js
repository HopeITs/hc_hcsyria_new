/*
 * The loading screen. Legacy: when the window has loaded, wait two seconds,
 * then fade the overlay out over half a second and remove it (jQuery
 * fadeOut, from the script the navbar pushed on 19 pages). The two seconds
 * are deliberate branding time, not load time, and are kept.
 *
 * Fades with the same half second; resources/css/site/preloader.css owns the
 * transition, this only says when. Unlike the legacy overlay it is not left
 * covering the page when JavaScript is off (see the <noscript> in the
 * component).
 */

const HOLD_MS = 2000;

export default () => ({
    leaving: false,

    init() {
        const leave = () => setTimeout(() => {
            this.leaving = true;
            // Removed when the fade ends; a fallback in case it never fires
            // (a background tab does not run transitions).
            setTimeout(() => this.$el.remove(), 700);
        }, HOLD_MS);

        if (document.readyState === 'complete') {
            leave();
        } else {
            addEventListener('load', leave, { once: true });
        }
    },
});
