/*
 * Figures per centre, beside a map that picks the centre (DISC's three).
 * `city` is the picked centre's number; the markup around the map sets it
 * (resources/views/components/ui/syria-map.blade.php) and shows that
 * centre's title and start date.
 *
 * The radial bars replace the amCharts radar chart: one ring per measure,
 * innermost first, each a track of 270 degrees from twelve o'clock round to
 * nine and a bar from twelve o'clock of value / max of the way. The bars
 * grow from nothing for a second when the page loads and when another centre
 * is picked, and a bubble in the bar's colour says its figure when it is
 * pointed at.
 */
const SWEEP = 270 / 360;

export default ({ measures, centres, max }) => ({
    city: 0,
    shown: false,
    tip: null,

    init() {
        // Starts empty so the bars grow in on the first frame.
        requestAnimationFrame(() => requestAnimationFrame(() => { this.shown = true; }));
        this.$watch('city', () => {
            this.shown = false;
            setTimeout(() => { this.shown = true; }, 30);
        });
    },

    /** Length of a bar, in hundredths of the ring it sits on (the ring has pathLength 100). */
    length(key) {
        if (! this.shown) return 0;

        const measure = measures.find((m) => m.key === key);
        const length = Math.min(centres[this.city][key] / max, 1) * SWEEP * 100;

        // Round ends sit inside the bar's length, so the visible stroke is shorter by a cap at each end.
        return Math.max(length - 2 * measure.cap, 0);
    },

    point(event, key) {
        const box = this.$refs.chart.getBoundingClientRect();
        this.tip = {
            x: event.clientX - box.left,
            y: event.clientY - box.top,
            text: centres[this.city][key].toLocaleString('en-US'),
            color: measures.find((measure) => measure.key === key).color,
        };
    },

    leave() {
        this.tip = null;
    },

});
