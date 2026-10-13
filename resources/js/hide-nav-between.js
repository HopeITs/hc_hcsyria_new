/*
 * Hides the navigation bar while the page is scrolled between two sections
 * (DISC: from its photo of programmes to its testimonies), so the dark photo
 * panel is not under the bar. Legacy: a scroll handler in the page that hid
 * and showed the navbar between the two sections' tops, minus 92px, on
 * windows wider than 1023px. The tops are read at each scroll here, where
 * the legacy page read them once before its images had loaded.
 *
 * Usage:  x-hide-nav-between="'#from', '#to'"
 */
const OFFSET = 92;
const MIN_WIDTH = 1024;

export default (Alpine) => {
    Alpine.directive('hide-nav-between', (element, { expression }, { evaluate, cleanup }) => {
        const [from, to] = evaluate(`[${expression}]`);
        const nav = document.querySelector('.site-nav');

        if (! nav) return;

        const update = () => {
            const start = document.querySelector(from);
            const end = document.querySelector(to);
            const top = window.scrollY;
            const hidden = window.innerWidth >= MIN_WIDTH && start && end
                && top >= start.getBoundingClientRect().top + top - OFFSET
                && top < end.getBoundingClientRect().top + top - OFFSET;

            nav.classList.toggle('site-nav--hidden', Boolean(hidden));
        };

        addEventListener('scroll', update, { passive: true });
        cleanup(() => {
            removeEventListener('scroll', update);
            nav.classList.remove('site-nav--hidden');
        });
    });
};
