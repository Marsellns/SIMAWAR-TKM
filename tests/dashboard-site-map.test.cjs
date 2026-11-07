const fs = require('node:fs');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');

function fixture(module = 'feasibility', fetcher = async () => ({ ok: true, json: async () => data('ONE') })) {
    const elements = new Map();
    function element() { return { value: '', checked: false, textContent: '', innerHTML: '', dataset: {}, classList: { add() {}, remove() {} }, addEventListener() {}, setAttribute() {}, removeAttribute() {}, querySelectorAll: () => [], replaceChildren() {} }; }
    const root = { dataset: { module, scope: 'all', url: '/reports/site-map/' + module }, querySelector(selector) { if (!elements.has(selector)) elements.set(selector, element()); return elements.get(selector); } };
    const rendered = [];
    const map = { setView() { return this; }, on() {}, createPane() {}, getPane: () => ({ style: {} }), fitBounds() {}, removeLayer() {}, invalidateSize() {} };
    const layer = () => ({ addTo() { return this; }, clearLayers() {} });
    const L = {
        map: () => map, control: { zoom: layer }, tileLayer: () => ({ ...layer(), on() {} }), layerGroup: layer,
        polygon: layer, marker: layer, divIcon: options => options, latLngBounds: points => points,
        circleMarker(point, options) { const marker = { ...layer(), bindTooltip() {}, on() {}, point, options }; rendered.push(marker); return marker; },
    };
    const context = vm.createContext({
        URL, URLSearchParams, AbortController, setTimeout, clearTimeout, L, ResizeObserver: class { observe() {} },
        location: { origin: 'http://localhost', search: '' }, addEventListener() {}, fetch: fetcher,
        document: { readyState: 'loading', addEventListener() {}, querySelector: () => root },
    });
    vm.runInContext(fs.readFileSync('public/js/dashboard-site-map.js', 'utf8'), context);
    return { api: context.SimasterSiteMap, root, elements, rendered, field: name => root.querySelector(`[data-map-${name}]`) };
}
function data(id) {
    return { sites: [{ id, name: 'Site', nop: 'NOP BOGOR', city: 'Bogor', point: { lat: -6.5, lng: 106.8 }, fields: [] }], summary: { total: 1, mapped: 1, missing: 0 }, legend: 'NOP' };
}

test('tooltip displays real module fields and escapes untrusted source strings', () => {
    const { api } = fixture();
    const html = api.tooltip({ id: '<script>', name: '<img onerror=x>', nop: 'NOP BOGOR', city: 'Bogor', fields: [{ label: 'Revenue', value: 'Rp 200' }], coordinate_source: 'Plus Code alamat' });
    assert.match(html, /Revenue/); assert.match(html, /Rp 200/); assert.match(html, /Plus Code alamat/);
    assert.doesNotMatch(html, /<script>|<img/); assert.match(html, /&lt;script&gt;/);
});

test('newest filter request wins even if an older server response arrives last', async () => {
    const responses = [];
    const view = fixture('pnl', () => new Promise(resolve => responses.push(resolve)));
    const first = view.api.refresh({ tahun: 2025 });
    const second = view.api.refresh({ tahun: 2026 });
    responses[1]({ ok: true, json: async () => data('NEW') }); await second;
    responses[0]({ ok: true, json: async () => data('OLD') }); await first;
    assert.match(view.field('rows').innerHTML, /NEW/); assert.doesNotMatch(view.field('rows').innerHTML, /OLD/);
});

test('equipment filters run locally and never put thousands of site identifiers into a GET URL', async () => {
    const urls = [];
    const view = fixture('equipment', async url => { urls.push(String(url)); return { ok: true, json: async () => ({ ...data('ONE'), sites: [...data('ONE').sites, ...data('TWO').sites] }) }; });
    await view.api.refresh({ sites: ['ONE', ...Array.from({ length: 6000 }, (_, index) => 'SITE' + index)] });
    assert.equal(urls.length, 1); assert.ok(urls[0].length < 100);
    assert.match(view.field('rows').innerHTML, /ONE/); assert.doesNotMatch(view.field('rows').innerHTML, /TWO/);
    await view.api.refresh({ sites: [] });
    assert.equal(urls.length, 1); assert.equal(view.field('total').textContent, '0');
});

test('a failed filter request removes stale points and offers a retry', async () => {
    let fail = false;
    const view = fixture('pnl', async () => ({ ok: !fail, json: async () => data('ONE') }));
    await view.api.refresh({ tahun: 2025 }); fail = true;
    await view.api.refresh({ tahun: 2026 });
    assert.equal(view.field('total').textContent, '—'); assert.doesNotMatch(view.field('rows').innerHTML, /ONE/);
    assert.match(view.field('status').textContent, /mencoba lagi/);
});

test('NOP coverage outline ignores duplicates and refuses a polygon for collinear sites', () => {
    const { api } = fixture();
    assert.equal(api.hull([{ lat: 1, lng: 1 }, { lat: 2, lng: 2 }]).length, 0);
    const outline = api.hull([{ lat: 1, lng: 1 }, { lat: 1, lng: 2 }, { lat: 2, lng: 2 }, { lat: 2, lng: 1 }, { lat: 1.5, lng: 1.5 }, { lat: 1, lng: 1 }]);
    assert.equal(outline.length, 4);
});

test('nearby NOP labels remain readable without changing site positions', () => {
    const { api } = fixture();
    const points = [{ x: 100, y: 100 }, { x: 120, y: 105 }, { x: 130, y: 115 }, { x: 500, y: 100 }];
    const original = JSON.stringify(points);
    const offsets = api.labelOffsets(points);
    for (let a = 0; a < points.length; a++) for (let b = a + 1; b < points.length; b++) {
        if (Math.abs(points[a].x - points[b].x) < 180) assert.ok(Math.abs(points[a].y + offsets[a] - points[b].y - offsets[b]) >= 24);
    }
    assert.equal(offsets[3], 0);
    assert.equal(JSON.stringify(points), original);
});
