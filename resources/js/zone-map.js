/*
 * The labels and dotted lines on the Study Zone map appear once the page has
 * been scrolled past the row of zone buttons above it (legacy: a scroll
 * handler that waited until the Social button's top reached the top of the
 * window). They stay once shown.
 */
export default () => ({
    shown: false,

    init() {
        const zones = document.querySelector('.category-showcase__item');
        const check = () => {
            if (zones && zones.getBoundingClientRect().top <= 0) {
                this.shown = true;
                removeEventListener('scroll', check);
            }
        };

        addEventListener('scroll', check, { passive: true });
        check();
    },
});
