const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const assert = require('node:assert/strict');

const helper = fs.readFileSync(path.join(__dirname, '../public/js/infrastructure-stage-duration.js'), 'utf8');
const context = vm.createContext({ Date, Intl });
vm.runInContext(helper, context);
const { forStage } = context.infrastructureStageDuration;

test('source date formatting converts only date columns', () => {
    const { formatSourceDate } = context.infrastructureStageDuration;
    assert.equal(formatSourceDate('start_sewa', 45420), '08 Mei 2024');
    assert.equal(formatSourceDate('tgl_pks_ke_legal', '45834'), '26 Jun 2025');
    assert.equal(formatSourceDate('invoice_date', '2026-10-23'), '23 Okt 2026');
    assert.equal(formatSourceDate('date_on_air', 'NY'), 'NY');
    assert.equal(formatSourceDate('pr_date_year', 2026), 2026);
    assert.equal(formatSourceDate('masa_sewa', 5), 5);
});

test('completed calendar months follow the next recorded stage or today', () => {
    const stages = [['BAK', '2026-05-22'], ['PKS/BAK ke Owner', '2026-07-10'], ['PKS ke Legal', null]];
    const bak = forStage(stages, 0, { asOf: '2026-10-23' });
    assert.equal(bak.label, '1 bulan');
    assert.equal(bak.ongoing, false);
    const owner = forStage(stages, 1, { asOf: '2026-10-23' });
    assert.equal(owner.label, '3 bulan');
    assert.equal(owner.ongoing, true);
});

test('short stages use days and completed or invalid stages have no running duration', () => {
    assert.equal(forStage([['BAK', '2026-05-22'], ['PKS', '2026-05-29']], 0).label, '7 hari');
    assert.equal(forStage([['Paid', '2026-07-10']], 0, { terminal: true }), null);
    assert.equal(forStage([['BAK', '2026-07-10']], 0, { asOf: '2026-05-22' }), null);
    assert.equal(forStage([['BAK', null]], 0, { asOf: '2026-10-23' }), null);
});

test('Excel serials and manual date strings calculate the same duration', () => {
    const startSerial = (Date.UTC(2026, 4, 22) - Date.UTC(1899, 11, 30)) / 86400000;
    const stages = [['BAK', startSerial], ['PKS', '10/07/2026']];
    assert.equal(forStage(stages, 0).label, '1 bulan');
});

test('Combat timeline reads manual BAK and PKS/BAK owner dates', () => {
    const view = fs.readFileSync(path.join(__dirname, '../resources/views/infrastruktur/combat/index.blade.php'), 'utf8');
    const start = view.indexOf('    function currentStage(d)');
    const end = view.indexOf('    function renderHistory(', start);
    assert.ok(start >= 0 && end > start);
    const combat = vm.createContext({ Date, Intl });
    vm.runInContext(helper, combat);
    combat.detailValue = (row, ...keys) => keys.map(key => row.source_details?.[key]).find(value => value != null && value !== '') ?? null;
    combat.esc = value => String(value);
    vm.runInContext(view.slice(start, end) + '\nthis.renderTimeline = renderTimeline;', combat);
    const html = combat.renderTimeline({
        source_details: { tanggal_bak: '2026-05-22', tgl_pks_bak_ke_owner: '2026-07-10' },
    });
    assert.match(html, /<strong>BAK<\/strong><span>22 Mei 2026<\/span><span class="combat-detail-duration">1 bulan<\/span>/);
    assert.match(html, /<strong>PKS\/BAK ke Owner<\/strong><span>10 Jul 2026<\/span>/);
    assert.doesNotMatch(html, /duration-end|title="Sampai /);

    const serial = date => (Date.parse(date) - Date.UTC(1899, 11, 30)) / 86400000;
    const excelHtml = combat.renderTimeline({
        source_details: { tanggal_bak: serial('2026-05-22'), tgl_pks_bak_ke_owner: serial('2026-07-10') },
    });
    assert.match(excelHtml, /<strong>BAK<\/strong><span>22 Mei 2026<\/span><span class="combat-detail-duration">1 bulan<\/span>/);
    assert.doesNotMatch(excelHtml, /duration-end|title="Sampai /);
});

test('Combat extra source fields omit duplicate aging and format legacy period dates', () => {
    const view = fs.readFileSync(path.join(__dirname, '../resources/views/infrastruktur/combat/index.blade.php'), 'utf8');
    const start = view.indexOf('    const KNOWN_COMBAT_KEYS = ');
    const end = view.indexOf('    function combatIcon(', start);
    assert.ok(start >= 0 && end > start);
    const combat = vm.createContext({ Date, Intl });
    vm.runInContext(helper, combat);
    combat.sourceDetails = row => row.source_details;
    combat.isPnlKey = () => false;
    combat.detailField = (label, value) => `<dt>${label}</dt><dd>${value}</dd>`;
    vm.runInContext(view.slice(start, end) + '\nthis.renderSourceDetails = renderSourceDetails;', combat);
    const html = combat.renderSourceDetails({ source_details: {
        aging_days: '94 hari', lama_proses_saat_ini_hari: 94,
        new_period_awal: 46235, new_period_akhir: '48060',
        start_sewa: 45420, end_sewa: '45784', tanggal_bak: 45821,
        tgl_pks_ke_legal: '45834', date_on_air: 'NY', masa_sewa: '1 tahun',
        catatan_sumber: 'Tetap terlihat',
    } });
    assert.match(html, /<dt>new period awal<\/dt><dd>01 Agu 2026<\/dd>/);
    assert.match(html, /<dt>new period akhir<\/dt><dd>31 Jul 2031<\/dd>/);
    assert.match(html, /<dt>start sewa<\/dt><dd>08 Mei 2024<\/dd>/);
    assert.match(html, /<dt>end sewa<\/dt><dd>07 Mei 2025<\/dd>/);
    assert.match(html, /<dt>tanggal bak<\/dt><dd>13 Jun 2025<\/dd>/);
    assert.match(html, /<dt>tgl pks ke legal<\/dt><dd>26 Jun 2025<\/dd>/);
    assert.match(html, /<dt>date on air<\/dt><dd>NY<\/dd>/);
    assert.match(html, /<dt>masa sewa<\/dt><dd>1 tahun<\/dd>/);
    assert.match(html, /Tetap terlihat/);
    assert.doesNotMatch(html, /46235|48060|45420|45784|45821|45834|94 hari|lama proses saat ini hari|aging days/i);
});
