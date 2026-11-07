// Rebuild the small, public locality reference from a geoBoundaries ADM2 download.
// node scripts/prepare-site-map-boundaries.mjs /path/to/geoBoundaries-IDN-ADM2_simplified.geojson
import fs from 'node:fs';
const source = process.argv[2];
if (!source) throw new Error('Provide the downloaded geoBoundaries IDN ADM2 GeoJSON file.');
const cities = new Set(['Bekasi', 'Kota Bekasi', 'Bogor', 'Kota Bogor', 'Kota Depok', 'Karawang', 'Sukabumi', 'Kota Sukabumi', 'Purwakarta',
    'Kota Cilegon', 'Kota Serang', 'Kota Tangerang', 'Kota Tangerang Selatan', 'Lebak', 'Pandeglang', 'Serang', 'Tangerang',
    'Bandung', 'Bandung Barat', 'Ciamis', 'Cianjur', 'Cirebon', 'Garut', 'Indramayu', 'Kuningan', 'Majalengka', 'Pangandaran', 'Subang', 'Sumedang', 'Tasikmalaya',
    'Kota Bandung', 'Kota Banjar', 'Kota Cimahi', 'Kota Cirebon', 'Kota Tasikmalaya',
    'Kepulauan Seribu', 'Kota Jakarta Barat', 'Kota Jakarta Pusat', 'Kota Jakarta Selatan', 'Kota Jakarta Timur', 'Kota Jakarta Utara']);
const data = JSON.parse(fs.readFileSync(source, 'utf8'));
const features = data.features.filter(feature => cities.has(feature.properties.shapeName)).map(feature => ({
    type: 'Feature', properties: { city: feature.properties.shapeName, source_id: feature.properties.shapeID }, geometry: feature.geometry,
}));
if (features.length !== cities.size) throw new Error('The source does not contain every required locality.');
// A short Plus Code may omit the Jakarta municipality. Accept it only when its
// recovered point is unique across the actual public Jakarta polygons.
features.push({ type: 'Feature', properties: { city: 'Jakarta', source_id: 'jakarta-union' }, geometry: {
    type: 'MultiPolygon', coordinates: features.filter(feature => /Jakarta|Kepulauan Seribu/.test(feature.properties.city))
        .flatMap(feature => feature.geometry.type === 'Polygon' ? [feature.geometry.coordinates] : feature.geometry.coordinates),
} });
fs.mkdirSync('resources/data', { recursive: true });
fs.writeFileSync('resources/data/site-map-localities.geojson', JSON.stringify({
    type: 'FeatureCollection',
    attribution: 'geoBoundaries IDN ADM2 22746128; BPS, WFP, OCHA ROAP; CC BY 3.0 IGO',
    source: 'https://www.geoboundaries.org/api/current/gbOpen/IDN/ADM2/',
    features,
}));
console.log(`Prepared ${features.length} locality boundaries.`);
