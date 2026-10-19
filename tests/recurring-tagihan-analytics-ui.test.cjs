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
