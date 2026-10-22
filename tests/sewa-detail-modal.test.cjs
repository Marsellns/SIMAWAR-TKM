const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');

const view = fs.readFileSync(path.join(__dirname, '../resources/views/infrastruktur/sewa-lahan/index.blade.php'), 'utf8');
const start = view.indexOf('    function esc(v)');
const end = view.indexOf('    const detailModal = ', start);
assert.ok(start >= 0 && end > start);

const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
})[character]);
const $ = () => ({
    text(value) { this.value = value; return this; },
    html() { return escapeHtml(this.value); },
});
const context = vm.createContext({ $, Date, Intl, URL });
vm.runInContext(view.slice(start, end) + '\nthis.renderDetail = renderDetail;', context);

test('Rebuild dates produce an operational timeline and accept the source longitude spelling', () => {
    const html = context.renderDetail({
        site_code: 'KRW008', site_name: 'Cikampek',
        tgl_bak_baru: '2026-07-15',
        source_details: {
            tgl_negosiasi: 46000, tgl_pksbak_ke_owner: '2026-07-20',
            latitude: -6.49, longtitude: 107.45,
        },
    });
    assert.match(html, /PKS\/BAK ke Owner/);
    assert.match(html, /Negosiasi/);
    assert.match(html, /Rute dari posisi saya/);
    assert.match(html, /destination=-6\.49%2C107\.45/);
    assert.doesNotMatch(html, /1970/);
    assert.doesNotMatch(html, /<strong>Penawaran 1<\/strong>/);
});

test('address-only site still offers directions, while missing location does not', () => {
    const addressOnly = context.renderDetail({
        site_code: 'DPK019',
        source_details: { address: 'Jl. Raya Gandul 22', kabupaten: 'KOTA DEPOK' },
    });
    assert.match(addressOnly, /Rute dari posisi saya/);
    assert.match(addressOnly, /destination=Jl\.\+Raya\+Gandul\+22%2C\+KOTA\+DEPOK%2C\+Indonesia/);

    const missing = context.renderDetail({ site_code: 'NO-LOCATION', source_details: {} });
    assert.doesNotMatch(missing, /Rute dari posisi saya/);
});

test('template values and dates produce the offer timeline without inventing other stages', () => {
    const html = context.renderDetail({
        site_code: 'KRW008', penawaran_1: 925000000, nego_1: 900000000,
        source_details: { tanggal_penawaran_1: '2026-07-12', tanggal_nego_1: '2026-07-28' },
    });
    assert.match(html, /<strong>Penawaran 1<\/strong>/);
    assert.match(html, /<strong>Nego 1<\/strong>/);
    assert.match(html, /Rp 925\.000\.000/);
    assert.doesNotMatch(html, /PKS\/BAK ke Owner/);
});

test('site text is escaped and edit control follows the server-provided admin URL', () => {
    const row = { site_code: 'SAFE', site_name: '<script>alert(1)</script>', source_details: {} };
    const viewer = context.renderDetail(row);
    assert.doesNotMatch(viewer, /<script>/);
    assert.doesNotMatch(viewer, /data-sewa-edit/);
    const admin = context.renderDetail({ ...row, edit_url: '/infrastruktur/sewa-lahan/1/edit' });
    assert.match(admin, /data-sewa-edit/);
});

