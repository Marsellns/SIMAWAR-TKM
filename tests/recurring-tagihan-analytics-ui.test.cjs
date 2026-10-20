const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

test('Tagihan IPAS analytics browser script remains valid after Blade routes are rendered', () => {
    const source = fs.readFileSync(path.join(__dirname, '..', 'resources/views/infrastruktur/recurring-tagihan-ipas/index.blade.php'), 'utf8');
    const script = source.match(/<script>([\s\S]*?)<\/script>/)?.[1];
    assert.ok(script);
    const rendered = script.replace(/@json\(route\([^\r\n]*\)\)/g, '"/infrastruktur/recurring-tagihan-ipas"');
    new vm.Script(rendered);
});

test('chart buckets preserve totals and carry matching site-table filters', () => {
    const source = fs.readFileSync(path.join(__dirname, '..', 'resources/views/infrastruktur/recurring-tagihan-ipas/index.blade.php'), 'utf8');
    const helpers = source.match(/    const topWithOther = [\s\S]*?(?=    const clickableSeries =)/)?.[0];
    assert.ok(helpers);
    const context = vm.createContext({});
    vm.runInContext(`${helpers}\nglobalThis.chartHelpers = { topWithOther, yearPoints };`, context);

    const providers = context.chartHelpers.topWithOther([
        { label: 'A', total: 5 }, { label: 'B', total: 3 }, { label: 'C', total: 2 },
    ], 2, 'tp');
    assert.equal(providers.reduce((sum, point) => sum + point.y, 0), 10);
    assert.equal(providers[2].name, 'Lainnya');
    assert.equal(providers[2].custom.dimension, 'tp_other');
    assert.deepEqual(Array.from(providers[2].custom.except), ['A', 'B']);

    const years = context.chartHelpers.yearPoints([
        ...Array.from({ length: 8 }, (_, index) => ({ label: String(2014 + index), total: 1 })),
        { label: 'Tanpa tanggal', total: 2 },
    ]);
    assert.equal(years.length, 8);
    assert.equal(years.reduce((sum, point) => sum + point.y, 0), 10);
    assert.equal(years[0].custom.dimension, 'before_year');
    assert.equal(years[0].custom.value, '2016');
    assert.equal(years.at(-1).custom.dimension, 'missing_year');
});
