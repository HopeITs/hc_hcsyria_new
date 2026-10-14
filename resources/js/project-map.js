/*
 * The map under the project list: a red pin for each project of the
 * filtered list that has a position, a popup with its name and beneficiary,
 * and the view fitted to the pins (not closer than zoom 12, so a single pin
 * is not zoomed into). The pins come from the list ($wire.markers()) when
 * the map starts and whenever Apply has changed the filters.
 */
import { startMap } from './leaflet-map';

const CENTRE = [34.8021, 38.9968];
const FIT_PADDING = [40, 40];
const FIT_MAX_ZOOM = 12;

export default ({ tiles }) => {
    // Held outside the component's data: Alpine would wrap Leaflet's objects in proxies.
    let L;
    let map;
    let layer;

    return {
        async init() {
            ({ L, map } = await startMap(this.$el, { tiles, centre: CENTRE }));
            layer = L.layerGroup().addTo(map);

            // The container is measured once the page around it has settled.
            setTimeout(() => map.invalidateSize(), 250);
            addEventListener('resize', () => map.invalidateSize());

            await this.load();
        },

        async load() {
            if (! map) return;

            const pins = await this.$wire.markers();
            const icon = L.icon({
                iconUrl: '/images/ico/marker-icon-2x-red.png',
                iconRetinaUrl: '/images/ico/marker-icon-2x-red.png',
                iconSize: [18, 28],
                iconAnchor: [9, 28],
                popupAnchor: [1, -28],
            });

            layer.clearLayers();

            const markers = pins.map((pin) => L.marker([pin.lat, pin.lng], { icon }).bindPopup(this.popup(pin)).addTo(layer));

            if (markers.length > 0) {
                map.fitBounds(L.featureGroup(markers).getBounds(), { padding: FIT_PADDING, maxZoom: FIT_MAX_ZOOM });
            }

            setTimeout(() => map.invalidateSize(), 100);
        },

        /** The popup's contents, built from text nodes, so nothing a project's record says can be markup. */
        popup(pin) {
            const box = document.createElement('div');
            const line = (label, text) => {
                const bold = document.createElement('b');
                bold.textContent = `${label}:`;
                box.append(bold, ` ${text ?? ''}`);
            };

            line('Project', pin.name);
            box.append(document.createElement('br'));
            line('Beneficiary', pin.beneficiary);

            return box;
        },
    };
};
