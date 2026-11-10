/*
 * The page-turning reader of a Roots of Hope edition (replaces the heyzine.com
 * flipbook). The edition's PDF is drawn page by page with pdf.js, and St.PageFlip
 * turns the pages. Both load only on the reader page. The PDF is the site's own
 * file, so nothing leaves the site.
 */
export default (src) => ({
    state: 'loading',
    page: 1,
    total: 0,
    book: null,
    ratio: 0.7,

    async init() {
        try {
            const [pdfjs, worker, { PageFlip }] = await Promise.all([
                import('pdfjs-dist'),
                import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
                import('page-flip'),
            ]);
            pdfjs.GlobalWorkerOptions.workerSrc = worker.default;

            const pdf = await pdfjs.getDocument({ url: src }).promise;
            const first = await pdf.getPage(1);
            const base = first.getViewport({ scale: 1 });
            const height = Math.max(320, window.innerHeight - 110);
            const width = Math.round((height * base.width) / base.height);

            // A PDF page that is about twice as wide as the cover is a two-page
            // spread: it becomes two leaves (left half, right half).
            const leaves = [];
            const ratio = base.width / base.height;
            this.ratio = ratio;
            for (let number = 1; number <= pdf.numPages; number++) {
                const page = number === 1 ? first : await pdf.getPage(number);
                const view = page.getViewport({ scale: 1 });
                if (view.width / view.height > ratio * 1.5) {
                    leaves.push({ page, half: 0 }, { page, half: 1 });
                } else {
                    leaves.push({ page, half: null });
                }
            }

            this.total = leaves.length;
            const stage = this.$refs.stage;

            // Every leaf exists up front (empty) so the book can open as soon
            // as the first spread is drawn; the rest are drawn in the background.
            const images = leaves.map((_, index) => {
                const image = document.createElement('img');
                image.alt = `Page ${index + 1}`;
                image.draggable = false;
                const leaf = document.createElement('div');
                leaf.className = 'flipbook__leaf';
                leaf.appendChild(image);
                stage.appendChild(leaf);
                return image;
            });

            // A spread is drawn once; both halves are cut from that one canvas.
            const drawn = new Map();
            const render = (page) => {
                if (!drawn.has(page)) {
                    drawn.set(page, (async () => {
                        const viewport = page.getViewport({ scale: (Math.min(1.5, window.devicePixelRatio || 1) * height) / page.getViewport({ scale: 1 }).height });
                        const canvas = document.createElement('canvas');
                        canvas.width = Math.round(viewport.width);
                        canvas.height = Math.round(viewport.height);
                        await page.render({ canvasContext: canvas.getContext("2d"), viewport }).promise;
                        return canvas;
                    })());
                }
                return drawn.get(page);
            };

            const draw = async (index) => {
                const { page, half } = leaves[index];
                let canvas = await render(page);
                if (half !== null) {
                    const cut = document.createElement('canvas');
                    cut.width = Math.floor(canvas.width / 2);
                    cut.height = canvas.height;
                    cut.getContext('2d').drawImage(canvas, half * cut.width, 0, cut.width, canvas.height, 0, 0, cut.width, canvas.height);
                    canvas = cut;
                    if (half === 1) drawn.delete(page);
                }
                images[index].src = canvas.toDataURL('image/jpeg', 0.85);
            };

            for (let index = 0; index < Math.min(2, leaves.length); index++) {
                await draw(index);
            }

            // The stage is display:none until 'ready'; PageFlip measures its
            // container, so reveal it and let the DOM update before building.
            this.state = 'ready';
            await this.$nextTick();

            this.fit();
            window.addEventListener('resize', this.onResize = () => { this.fit(); this.book?.update(); });

            this.book = new PageFlip(stage, {
                width,
                height,
                size: 'stretch',
                minWidth: 240,
                maxWidth: 1600,
                minHeight: 320,
                maxHeight: 2000,
                showCover: true,
                maxShadowOpacity: 0.5,
                mobileScrollSupport: false,
            });
            this.book.loadFromHTML(stage.querySelectorAll('.flipbook__leaf'));
            // The page controls appear with 'ready' and take height; fit again once laid out.
            requestAnimationFrame(() => { this.fit(); this.book.update(); });
            this.book.on('flip', (event) => { this.page = event.data + 1; });

            for (let index = 2; index < leaves.length; index++) {
                await draw(index);
            }
        } catch (error) {
            console.error('Flipbook failed to open', error);
            if (!this.book) this.state = 'failed';
        }
    },

    // Width of the frame that lets the whole book (one page when narrow, two
    // when wide) fit the available height.
    fit() {
        const frame = this.$refs.frame;
        const room = frame.parentElement;
        const single = room.clientWidth < 560;
        const book = room.clientHeight * this.ratio * (single ? 1 : 2);
        frame.style.width = `${Math.max(240, Math.min(room.clientWidth, book))}px`;
    },

    prev() { this.book?.flipPrev(); },
    next() { this.book?.flipNext(); },
    first() { this.book?.flip(0); },
    last() { this.book?.flip(this.total - 1); },

    fullscreen() {
        const root = this.$root;
        document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen?.();
    },

    destroy() {
        window.removeEventListener('resize', this.onResize);
        this.book?.destroy();
    },
});
