/*
 * Radial bars: the amCharts radar column chart the legacy statistics used
 * (DISC's learners, Study Zone's fields of education), as Alpine data for
 * resources/views/components/ui/radial-bars.blade.php.
 *
 * One ring per bar, innermost first, each a track of 270 degrees from twelve
 * o'clock round to nine and a bar from twelve o'clock of value / max of the
 * way. The bars grow from nothing for a second when the page loads and when
 * another data set is picked, and a bubble in the bar's colour says its
 * figure when it is pointed at.
 *
 * `sets` holds the figures, one object per data set (key => number); `pick`
 * is the set shown. Whatever picks it (the map of Syria, in DISC's case)
 * writes `pick`. `bars` maps each size the chart is drawn at ('' for the main
 * one) to its bars, [{ key, color, cap }], cap being the half width of the bar
 * in hundredths of its ring (App\Support\RadialBars::variants()).
 */
const SWEEP = 270 / 360;

export default ({ bars, sets, max }) => ({
    pick: 0,
    shown: false,
    tip: null,

    init() {
        // Starts empty so the bars grow in on the first frame.
        requestAnimationFrame(() => requestAnimationFrame(() => { this.shown = true; }));
        this.$watch('pick', () => {
            this.shown = false;
            setTimeout(() => { this.shown = true; }, 30);
        });
    },

    /** Length of a bar, in hundredths of the ring it sits on (the ring has pathLength 100). */
    length(key, size = '') {
        if (! this.shown) return 0;

        const bar = bars[size].find((b) => b.key === key);
        const length = Math.min(sets[this.pick][key] / max, 1) * SWEEP * 100;

        // Round ends sit inside the bar's length, so the visible stroke is shorter by a cap at each end.
        return Math.max(length - 2 * bar.cap, 0);
    },

    point(event, key) {
        const box = this.$refs.chart.getBoundingClientRect();
        this.tip = {
            x: event.clientX - box.left,
            y: event.clientY - box.top,
            text: sets[this.pick][key].toLocaleString('en-US'),
            color: bars[''].find((bar) => bar.key === key).color,
        };
    },

    leave() {
        this.tip = null;
    },
});
