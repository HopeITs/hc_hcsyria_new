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

export default () => ({
    shown: false,
    slides: [],
    index: 0,
    zoomed: false,
    thumbs: false,
    playing: false,
    timer: null,

    init() {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[data-lightbox]');

            if (! link) return;

            event.preventDefault();

            const group = [...document.querySelectorAll('a[data-lightbox]')].filter((a) => a.dataset.lightbox === link.dataset.lightbox);
            this.slides = group.map(slideFor);
            this.open(group.indexOf(link));
        });
    },

    open(index) {
        this.index = index;
        this.zoomed = false;
        this.thumbs = false;
        this.shown = true;
        document.documentElement.classList.add('lightbox-open');
    },

    close() {
        this.shown = false;
        this.stop();
        document.documentElement.classList.remove('lightbox-open');
    },

    go(index) {
        this.index = (index + this.slides.length) % this.slides.length;
        this.zoomed = false;
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

        if (event.key === 'Escape') this.close();
        if (event.key === 'ArrowRight') this.next();
        if (event.key === 'ArrowLeft') this.prev();
    },
});
