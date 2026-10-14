/*
 * The lightbox: fancybox 3, which the legacy pages used for photo galleries
 * and for videos, as one Alpine component (resources/views/components/ui/lightbox.blade.php,
 * resources/css/ui/lightbox.css). Any link with data-lightbox="group" opens
 * it; the links sharing a group are its slides, in page order. A link whose
 * address is a YouTube one plays as a video, otherwise it is a photo, and
 * data-caption is the text under it.
 *
 * What it keeps of fancybox: the "3 / 14" counter, previous / next arrows
 * (and the arrow keys), zoom, slideshow, a strip of thumbnails, the caption,
 * close (button, Escape, a click outside the picture). Not kept: swipe
 * and pinch gestures, and the zoom-from-the-thumbnail opening animation.
 *
 * What it keeps of fancybox's address: the open slide is in the address
 * (#group-3, 1 for the first) so a link to it opens the gallery at that
 * photo; going to another slide changes it and closing removes it.
 */
const SLIDESHOW_MS = 4000;
const NOCOOKIE = /^https:\/\/(www\.)?youtube\.com\/(embed\/|watch\?v=)([\w-]+)/;

function slideFor(link) {
    const href = link.getAttribute('href');
    const video = NOCOOKIE.exec(href);

    return {
        type: video ? 'video' : 'image',
        src: video ? `https://www.youtube-nocookie.com/embed/${video[3]}` : href,
        thumb: link.querySelector('img')?.getAttribute('src') ?? href,
        caption: link.dataset.caption ?? '',
    };
}

/** The links of one gallery, in page order. */
function linksOf(group) {
    return [...document.querySelectorAll('a[data-lightbox]')].filter((a) => a.dataset.lightbox === group);
}

export default () => ({
    shown: false,
    slides: [],
    index: 0,
    zoomed: false,
    thumbs: false,
    playing: false,
    timer: null,

    group: null,

    init() {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-lightbox]');

            if (! link) return;

            event.preventDefault();

            this.openGroup(link.dataset.lightbox, linksOf(link.dataset.lightbox).indexOf(link));
        });

        // A link to a slide opens it; the gallery of a window that is itself opened by the address appears a moment later.
        this.fromAddress();
        addEventListener('hashchange', () => (location.hash ? this.fromAddress() : this.shown && this.close(false)));
        addEventListener('project-opened', () => setTimeout(() => this.fromAddress(), 100));
    },

    /** Opens the gallery and slide the address names, when this page has them. */
    fromAddress() {
        const named = /^#(.+)-(\d+)$/.exec(location.hash);

        if (! named) return;

        const count = linksOf(named[1]).length;
        const index = Number(named[2]) - 1;

        if (index < 0 || index >= count) return;

        if (this.shown && this.group === named[1]) return this.go(index);

        this.openGroup(named[1], index);
    },

    openGroup(group, index) {
        this.group = group;
        this.slides = linksOf(group).map(slideFor);
        this.open(index);
    },

    /** The address names the open slide. */
    mark() {
        history.replaceState(history.state, '', `${location.pathname}${location.search}#${this.group}-${this.index + 1}`);
    },

    open(index) {
        this.index = index;
        this.zoomed = false;
        this.thumbs = false;
        this.shown = true;
        document.documentElement.classList.add('lightbox-open');
        this.mark();
    },

    close(unmark = true) {
        this.shown = false;
        this.stop();
        document.documentElement.classList.remove('lightbox-open');

        if (unmark && location.hash) history.replaceState(history.state, '', location.pathname + location.search);
    },

    go(index) {
        this.index = (index + this.slides.length) % this.slides.length;
        this.zoomed = false;
        this.mark();
    },

    next() { this.go(this.index + 1); },
    prev() { this.go(this.index - 1); },

    get slide() {
        return this.slides[this.index] ?? {};
    },

    toggleSlideshow() {
        if (this.playing) return this.stop();

        this.playing = true;
        this.timer = setInterval(() => this.next(), SLIDESHOW_MS);
    },

    stop() {
        this.playing = false;
        clearInterval(this.timer);
    },

    keydown(event) {
        if (! this.shown) return;

        // Said to be handled, so a window under the gallery stays open.
        if (event.key === 'Escape') { event.preventDefault(); this.close(); }
        if (event.key === 'ArrowRight') this.next();
        if (event.key === 'ArrowLeft') this.prev();
    },
});
