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
            const scale = (Math.min(2, window.devicePixelRatio || 1) * height) / base.height;

            this.total = pdf.numPages;
            const stage = this.$refs.stage;

            for (let number = 1; number <= pdf.numPages; number++) {
                const page = number === 1 ? first : await pdf.getPage(number);
                const viewport = page.getViewport({ scale });
                const canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;

                const image = document.createElement('img');
                image.src = canvas.toDataURL('image/jpeg', 0.85);
                image.alt = `Page ${number}`;
                image.draggable = false;
                const leaf = document.createElement('div');
                leaf.className = 'flipbook__leaf';
                leaf.appendChild(image);
                stage.appendChild(leaf);
            }

            // The stage is display:none until 'ready'; PageFlip measures its
            // container, so reveal it and let the DOM update before building.
            this.state = 'ready';
            await this.$nextTick();

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
            this.book.on('flip', (event) => { this.page = event.data + 1; });
        } catch (error) {
            console.error('Flipbook failed to open', error);
            this.state = 'failed';
        }
    },

    prev() { this.book?.flipPrev(); },
    next() { this.book?.flipNext(); },
    first() { this.book?.flip(0); },
    last() { this.book?.flip(this.total - 1); },

    fullscreen() {
        const root = this.$root;
        document.fullscreenElement ? document.exitFullscreen() : root.requestFullscreen?.();
    },

    destroy() { this.book?.destroy(); },
});
