const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');

function siteMapContext(getCurrentPosition) {
    const context = vm.createContext({
        URL,
        navigator: { geolocation: { getCurrentPosition } },
    });
    vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/js/infrastructure-site-map.js'), 'utf8'), context);
    return context.infrastructureSiteMap;
}

function clickFixture(destination) {
    const frame = { src: '', title: '' };
    const attributes = {};
    const map = {
        dataset: { destination }, isConnected: true,
        querySelector: () => frame,
        setAttribute: (key, value) => { attributes[key] = value; },
        removeAttribute: key => { delete attributes[key]; },
    };
    const link = {
        href: '', title: '',
        closest: () => map,
        setAttribute: (key, value) => { attributes[key] = value; },
    };
    const event = {
        target: { closest: () => link },
        prevented: false,
        preventDefault() { this.prevented = true; },
    };
    return { frame, attributes, map, link, event };
}

test('first arrow click previews a route from device position; second click follows Google Maps link', () => {
    const siteMap = siteMapContext(success => success({ coords: { latitude: -6.2, longitude: 106.8 } }));
    const fixture = clickFixture('-6.392497,106.822832');

    siteMap.handleClick(fixture.event);
    assert.equal(fixture.event.prevented, true);
    assert.equal(fixture.map.dataset.phase, 'preview');
    assert.match(fixture.frame.src, /saddr=-6\.2%2C106\.8/);
    assert.match(fixture.frame.src, /daddr=-6\.392497%2C106\.822832/);
    assert.match(fixture.link.href, /origin=-6\.2%2C106\.8/);
    assert.match(fixture.link.href, /destination=-6\.392497%2C106\.822832/);

    fixture.event.prevented = false;
    siteMap.handleClick(fixture.event);
    assert.equal(fixture.event.prevented, false);
});

test('address-only sites retain directions when device location is unavailable', () => {
    const siteMap = siteMapContext((_, failure) => failure());
    const place = siteMap.destination(null, 'Jl. Raya Gandul 22', 'KOTA DEPOK');
    const fixture = clickFixture(place);

    siteMap.handleClick(fixture.event);
    assert.match(fixture.frame.src, /saddr=Current\+Location/);
    assert.match(fixture.frame.src, /daddr=Jl\.\+Raya\+Gandul\+22/);
    assert.match(fixture.link.href, /destination=Jl\.\+Raya\+Gandul\+22/);
    assert.doesNotMatch(fixture.link.href, /origin=/);
    assert.match(siteMap.render(null, 'Jl. Raya Gandul 22', 'KOTA DEPOK'), /data-infrastructure-site-map-link/);
    assert.doesNotMatch(siteMap.render(null, '-', ''), /data-infrastructure-site-map-link/);
});

test('Combat and Sewa Lahan both render the shared map component', () => {
    for (const module of ['combat', 'sewa-lahan']) {
        const view = fs.readFileSync(path.join(__dirname, `../resources/views/infrastruktur/${module}/index.blade.php`), 'utf8');
        assert.match(view, /infrastructureSiteMap\.render\(/);
        assert.match(view, /js\/infrastructure-site-map\.js/);
        assert.match(view, /css\/infrastructure-site-map\.css/);
        assert.doesNotMatch(view, /Google Maps menggunakan lokasi perangkat sebagai titik awal/);

        const start = view.indexOf('    function coordinates(d)');
        const end = view.indexOf('    function ', start + 1);
        assert.ok(start >= 0 && end > start);
        const context = vm.createContext({
            detailValue: (row, ...keys) => keys.map(key => row.source_details?.[key]).find(value => value != null) ?? null,
        });
        vm.runInContext(view.slice(start, end) + '\nthis.coordinates = coordinates;', context);
        const point = context.coordinates({ source_details: { latitude: '-6,392497', longitude: '106,822832' } });
        assert.equal(point.lat, -6.392497);
        assert.equal(point.lon, 106.822832);
    }
});
