/*
 * "Our Spread": a list of the programme's centres by town, the details of
 * the chosen one beside a map with a marker for each open centre
 * (resources/views/components/site/centre-spread.blade.php).
 *
 * Choosing an open centre in the list shows its details and opens its
 * marker's popup; closed centres are listed but cannot be chosen. Leaflet
 * is loaded when this component starts, so pages without a map do not carry
 * it. The map starts a moment after the page, as the legacy one did, so it
 * measures a finished layout.
 */
const START_DELAY_MS = 1500;
const CENTRE = [34.8021, 36.9968];
const ZOOM = 7;

export default ({ centres, selected, tiles }) => {
    // Held outside the component's data: Alpine would wrap Leaflet's objects in proxies.
    const markers = {};

    return {
    centres,
    selected,
    touched: false,

    get centre() {
        return this.centres.find((centre) => centre.id === this.selected) ?? this.centres[0];
    },

    /**
     * The working hours: the record's own until a centre has been chosen,
     * then, for the centres that have them, the ones the legacy page wrote
     * into its script (the record's wording differs for those).
     */
    get hours() {
        return this.touched ? this.centre.hours : this.centre.hoursRecord;
    },

    choose(centre) {
        if (! centre.active) return;

        this.touched = true;
        this.selected = centre.id;
        markers[centre.id]?.openPopup();
    },

    init() {
        setTimeout(() => this.start(), START_DELAY_MS);
    },

    async start() {
        const { default: L } = await import('leaflet');
        await import('leaflet/dist/leaflet.css');

        const icon = L.icon({
            iconUrl: '/images/ico/marker-icon-2x.png',
            iconRetinaUrl: '/images/ico/marker-icon-2x.png',
            shadowUrl: '/images/ico/marker-shadow.png',
            iconSize: [18, 28],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            tooltipAnchor: [16, -28],
        });

        const map = L.map(this.$refs.map, { center: CENTRE, zoom: ZOOM });
        L.tileLayer(tiles, { attribution: '' }).addTo(map);

        this.centres.filter((centre) => centre.active && centre.latitude && centre.longitude).forEach((centre) => {
            const marker = L.marker([centre.latitude, centre.longitude], { icon }).addTo(map);
            marker.bindPopup(this.popup(centre), { maxWidth: 200 });
            markers[centre.id] = marker;
        });
    },

    /** The popup's contents, built from text nodes, so nothing a centre's record says can be markup. */
    popup(centre) {
        const box = document.createElement('b');
        const lines = [`Address: ${centre.address}`, centre.hoursText, `Tel: ${centre.telephone}`, `Email: ${centre.email}`];

        lines.forEach((line, i) => {
            if (i > 0) box.append(document.createElement('br'));
            box.append(document.createTextNode(line));
        });

        return box;
    },
    };
};
