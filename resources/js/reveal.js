/*
 * Scroll reveal: elements fade in as they scroll into view. Replaces AOS
 * (aos.js + aos.css, and the two copies of it each legacy page loaded), for
 * the one effect the site uses: data-aos="fade-up" with a per-element
 * duration, 609 times across the pages to port.
 *
 * AOS's rules, kept: an element is revealed once its top is 120px inside the
 * bottom edge of the window; it stays revealed as it scrolls out of the top;
 * scrolling back up past that line hides it again, so it animates every time
 * it is scrolled to, not once.
 *
 * Markup is <x-ui.reveal :ms="600">: the hidden state is in the HTML and CSS
 * (resources/css/ui/reveal.css), so there is no flash of content before this
 * runs. With prefers-reduced-motion everything is simply shown; AOS ignored
 * that setting.
 *
 * Usage:  x-reveal
 */

const TRIGGER_OFFSET = 120;

export default (Alpine) => {
    Alpine.directive('reveal', (element, _, { cleanup }) => {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) {
            element.classList.add('reveal--shown');

            return;
        }

        const observer = new IntersectionObserver(([entry]) => {
            // Inside the shrunken window, or already scrolled past its top.
            element.classList.toggle('reveal--shown', entry.isIntersecting || entry.boundingClientRect.top < 0);
        }, { rootMargin: `0px 0px -${TRIGGER_OFFSET}px 0px` });

        observer.observe(element);
        cleanup(() => observer.disconnect());
    });
};
