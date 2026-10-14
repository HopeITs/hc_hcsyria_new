/*
 * The micro-projects list a signed-in member sees
 * (resources/views/components/⚡micro-project-list.blade.php):
 *
 *  - projectFilters: the filter bar's draft, which Apply sends to the list;
 *  - projectModal: the details window's keys, backdrop and swipe;
 *  - x-project-icon: an icon that darkens under the pointer, says what it
 *    does in a tooltip and, on the copy icon, copies the link to the project.
 *
 * Legacy did these with jQuery, jquery.balloon and Bootstrap's modal.
 */
const TIP_SHOW_MS = 1500;
const COPIED_SHOW_MS = 1000;
const SWIPE_UP_PX = 60;

let balloon;
let balloonTimer;

/** The tooltip, one for the page: `where` is the side of the element it appears on. */
function showTip(element, text, where = 'top', lifetime = TIP_SHOW_MS) {
    balloon ??= Object.assign(document.createElement('div'), { className: 'project-tip' });
    document.body.append(balloon);
    balloon.textContent = text;
    balloon.className = `project-tip project-tip--${where}`;

    const box = element.getBoundingClientRect();
    const own = balloon.getBoundingClientRect();
    const gap = 5;
    const top = where === 'top' ? box.top - own.height - gap : box.top + (box.height - own.height) / 2;
    const left = where === 'top' ? box.left + (box.width - own.width) / 2
        : where === 'left' ? box.left - own.width - 10 : box.right + 10;

    balloon.style.top = `${scrollY + top}px`;
    balloon.style.left = `${scrollX + left}px`;

    clearTimeout(balloonTimer);
    balloonTimer = setTimeout(hideTip, lifetime);
}

function hideTip() {
    clearTimeout(balloonTimer);
    balloon?.remove();
}

export default (Alpine) => {
    Alpine.directive('project-icon', (element, { expression }, { evaluate, cleanup }) => {
        const { hover, tip, copy } = evaluate(expression);
        const rest = element.getAttribute('src');

        const enter = () => {
            element.src = hover;
            if (tip) showTip(element, tip);
        };
        const leave = () => {
            element.src = rest;
            if (tip) hideTip();
        };
        const click = () => {
            navigator.clipboard?.writeText(`${location.host}/projects/micro-projects?projectDetailsModal=${copy}`);
            showTip(element, 'copied', 'left', COPIED_SHOW_MS);
        };

        element.addEventListener('mouseenter', enter);
        element.addEventListener('mouseleave', leave);
        if (copy) element.addEventListener('click', click);

        cleanup(() => {
            element.removeEventListener('mouseenter', enter);
            element.removeEventListener('mouseleave', leave);
            element.removeEventListener('click', click);
        });
    });

    Alpine.data('projectFilters', ({ cities, donors, applied }) => ({
        cities,
        donors,
        draft: { ...applied },

        init() {
            // The filters can change under the bar: the back button restores an earlier address.
            ['city', 'donor', 'hasVideo', 'search'].forEach((name) => {
                this.$watch(() => this.$wire[name], (value) => {
                    this.draft[name] = value;
                    if (name === 'search') window.dispatchEvent(new CustomEvent('search-set', { detail: value }));
                });
            });
        },

        /** "Filter Result": the draft's choices beside the count of the filters last applied. */
        get result() {
            const { city, donor, hasVideo, search } = this.draft;

            return `${this.$wire.count} (${this.$wire.total}) Projects | ${this.cities[city] ?? 'All Cities'} | ${this.donors[donor] ?? 'All Donors'}`
                + (search ? ` | ${this.$wire.search}` : '')
                + (hasVideo ? ' | Video' : '');
        },

        tip(element, text) {
            showTip(element, text, 'right', 1000);
        },

        /** Whether any filter is applied, which is when there is something to clear. */
        get filtered() {
            return Boolean(this.$wire.city || this.$wire.donor || this.$wire.hasVideo || this.$wire.search);
        },

        async apply() {
            const { city, donor, hasVideo, search } = this.draft;

            await this.$wire.apply(city, donor, hasVideo, search);
            this.showList();
        },

        async clear() {
            this.draft = { city: '', donor: '', hasVideo: false, search: '' };
            window.dispatchEvent(new CustomEvent('search-set', { detail: '' }));

            await this.$wire.apply('', '', false, '');
            this.showList();
        },

        /** Brings the list into view, but only when its top is hidden behind the bar or off the screen. */
        showList() {
            const list = document.querySelector('.project-tiles, .project-rows, .project-none');
            const bar = this.$root.getBoundingClientRect();

            if (list && list.getBoundingClientRect().top < bar.bottom) {
                document.querySelector('.member-projects')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },
    }));

    Alpine.data('projectModal', ({ locked }) => ({
        touch: 'ontouchstart' in document.documentElement,

        opener: null,

        init() {
            document.documentElement.classList.add('project-modal-open');
            this.opener = document.activeElement;
            this.$nextTick(() => this.$refs.dialog.focus());

            let startY = 0;
            this.$refs.swipe.addEventListener('touchstart', (event) => { startY = event.touches[0].clientY; }, { passive: true });
            this.$refs.swipe.addEventListener('touchend', (event) => {
                if (startY - event.changedTouches[0].clientY >= SWIPE_UP_PX) this.close();
            });
        },

        destroy() {
            document.documentElement.classList.remove('project-modal-open');

            // Back to where the visitor was, if that is still on the page.
            if (this.opener?.isConnected) this.opener.focus();
        },

        /** Tab stays inside the window while it is open. */
        trap(event) {
            const focusable = [...this.$refs.dialog.querySelectorAll('a[href], button, [tabindex]:not([tabindex="-1"])')]
                .filter((element) => element.offsetParent !== null);

            if (focusable.length === 0) return;

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const inside = this.$refs.dialog.contains(document.activeElement);

            if (event.shiftKey && (document.activeElement === first || ! inside || document.activeElement === this.$refs.dialog)) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && (document.activeElement === last || ! inside)) {
                event.preventDefault();
                first.focus();
            }
        },

        backdrop() {
            if (! locked) this.close();
        },

        close() {
            this.$wire.close();
        },
    }));
};
