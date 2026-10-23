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
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/js/infrastructure-stage-duration.js'), 'utf8'), context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/js/infrastructure-site-map.js'), 'utf8'), context);
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
    assert.match(html, /data-infrastructure-site-map-link/);
    assert.match(html, /<svg[^>]*>.*<path d="M4 12h16M13 5l7 7-7 7"/s);
    assert.match(html, /destination=-6\.49%2C107\.45/);
    assert.doesNotMatch(html, /Lokasi site siap dibuka|Koordinat:|Google Maps menggunakan lokasi perangkat/);
    assert.doesNotMatch(html, /1970/);
    assert.doesNotMatch(html, /<strong>Penawaran 1<\/strong>/);
});

test('address-only site still offers directions, while missing location does not', () => {
    const addressOnly = context.renderDetail({
        site_code: 'DPK019',
        source_details: { address: 'Jl. Raya Gandul 22', kabupaten: 'KOTA DEPOK' },
    });
    assert.match(addressOnly, /data-infrastructure-site-map-link/);
    assert.match(addressOnly, /destination=Jl\.\+Raya\+Gandul\+22%2C\+KOTA\+DEPOK%2C\+Indonesia/);

    const missing = context.renderDetail({ site_code: 'NO-LOCATION', source_details: {} });
    assert.doesNotMatch(missing, /data-infrastructure-site-map-link/);
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

test('matching source year appears only as Tahun Renewal in the site detail', () => {
    const matching = context.renderDetail({
        site_code: 'SEWA01', tahun_renewal: 2027,
        source_details: { tahun: 2027, catatan_sumber: 'Tetap terlihat' },
    });
    assert.match(matching, /<dt>Tahun Renewal<\/dt><dd>2027<\/dd>/);
    assert.doesNotMatch(matching, /<dt>tahun<\/dt>/i);
    assert.match(matching, /Tetap terlihat/);

    const different = context.renderDetail({
        site_code: 'SEWA01', tahun_renewal: 2027,
        source_details: { tahun: 2026 },
    });
    assert.match(different, /<dt>tahun<\/dt><dd>2026<\/dd>/i);
});

test('Rebuild BAK duration ends at the next recorded stage', () => {
    const html = context.renderDetail({
        site_code: 'SEWA01', tgl_bak_baru: '2026-05-22',
        source_details: { tgl_pks_bak_ke_owner: '2026-07-10' },
    });
    assert.match(html, /<strong>BAK<\/strong><span>22 Mei 2026<\/span><span class="sewa-detail-duration">1 bulan<\/span>/);
    assert.match(html, /<strong>PKS\/BAK ke Owner<\/strong><span>10 Jul 2026<\/span>/);
    assert.doesNotMatch(html, /duration-end|title="Sampai /);
});

test('same-day BAK and Owner dates show separate durations without endpoint labels', () => {
    const html = context.renderDetail({
        site_code: 'DPK093', tgl_bak_baru: '2026-08-13',
        source_details: { tgl_pks_bak_ke_owner: '2026-08-13' },
    });
    assert.match(html, /<strong>BAK<\/strong><span>13 Agu 2026<\/span><span class="sewa-detail-duration">0 hari<\/span>/);
    assert.match(html, /<strong>PKS\/BAK ke Owner<\/strong><span>13 Agu 2026<\/span><span class="sewa-detail-duration">/);
    assert.doesNotMatch(html, /duration-end|title="Sampai /);
});

test('Rebuild source periods display as dates without repeating cached aging values', () => {
    const html = context.renderDetail({
        site_code: 'EPG198', process_aging_days: 105,
        start_date_baru: '2026-08-01', end_date_baru: '2031-07-31',
        source_details: {
            periode_awal_baru: 46235, periode_akhir_baru: '48060',
            date_on_air: 45821,
            aging_days: '94 hari', lama_proses_saat_ini_hari: 94,
            prioritas: 'Urgent',
        },
    });
    assert.match(html, /<dt>periode awal baru<\/dt><dd>01 Agu 2026<\/dd>/);
    assert.match(html, /<dt>periode akhir baru<\/dt><dd>31 Jul 2031<\/dd>/);
    assert.match(html, /<dt>date on air<\/dt><dd>13 Jun 2025<\/dd>/);
    assert.match(html, /<dt>Lama Proses<\/dt><dd>105 hari<\/dd>/);
    assert.match(html, /Urgent/);
    assert.doesNotMatch(html, /46235|48060|94 hari|lama proses saat ini hari|aging days/i);
});
