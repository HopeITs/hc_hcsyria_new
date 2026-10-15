/*
 * The map of "Distribution Locations" on Family to Family
 * (resources/views/components/site/family-map.blade.php): Aleppo, a marker
 * for each place the programme gives out aid, each with a popup naming it.
 * Leaflet is loaded when this starts, so other pages do not carry it, and
 * the map starts a moment after the page, as the legacy one did, so it
 * measures a finished layout.
 */
const START_DELAY_MS = 2000;
const CENTRE = [36.21291103506083, 37.1521353849693];
const ZOOM = 15;

export default ({ markers, tiles }) => ({
    init() {
        setTimeout(() => this.start(), START_DELAY_MS);
    },

    async start() {
        const { default: L } = await import('leaflet');
        await import('leaflet/dist/leaflet.css');

        const icon = L.icon({
            iconUrl: '/images/ico/marker-icon-2x-red.png',
            iconRetinaUrl: '/images/ico/marker-icon-2x-red.png',
            iconSize: [18, 28],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            tooltipAnchor: [16, -28],
        });

        const map = L.map(this.$el, { center: CENTRE, zoom: ZOOM });
        L.tileLayer(tiles, { maxZoom: 20, attribution: '' }).addTo(map);

        markers.filter((place) => Number.isFinite(place.lat) && Number.isFinite(place.lng)).forEach((place) => {
            // Built from a text node, so nothing a record says can be markup.
            const popup = document.createElement('b');
            popup.append(` ${place.name}`);

            L.marker([place.lat, place.lng], { icon }).addTo(map).bindPopup(popup);
        });
    },
});
