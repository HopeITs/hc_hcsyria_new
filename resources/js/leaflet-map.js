/*
 * The map every Leaflet page starts the same way: Leaflet and its stylesheet
 * loaded on demand (so pages without a map do not carry them), a map in the
 * given element centred on Syria, and the tile layer from
 * config('hcsyria.map_tiles'), whose address the page passes in.
 */
const CENTRE = [34.8021, 36.9968];
const ZOOM = 7;

export async function startMap(element, { tiles, centre = CENTRE, zoom = ZOOM, ...options }) {
    const { default: L } = await import('leaflet');
    await import('leaflet/dist/leaflet.css');

    const map = L.map(element, { center: centre, zoom, ...options });
    L.tileLayer(tiles, { attribution: '' }).addTo(map);

    return { L, map };
}
