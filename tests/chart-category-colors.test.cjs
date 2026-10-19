const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

test('categorical bars keep a distinct color beyond the base palette', () => {
    const source = fs.readFileSync(path.join(__dirname, '..', 'resources/views/reports/scripts.blade.php'), 'utf8');
    const start = source.indexOf('window.SimasterChartPalette = Object.freeze({');
    const end = source.indexOf('\n        });', start) + '\n        });'.length;
    assert.ok(start >= 0 && end > start);
    const context = { window: {} };
    vm.runInNewContext(source.slice(start, end), context);
    const palette = context.window.SimasterChartPalette;

    assert.deepEqual(Array.from(palette.distinctColors(8)), Array.from(palette.series.slice(0, 8)));
    for (const count of [12, 16, 24, 48]) {
        const colors = Array.from(palette.distinctColors(count));
        assert.equal(colors.length, count);
        assert.equal(new Set(colors).size, count);
    }
});
