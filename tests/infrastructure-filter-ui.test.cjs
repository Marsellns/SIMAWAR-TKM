const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.join(__dirname, '..');
const read = relative => fs.readFileSync(path.join(root, relative), 'utf8');

test('Total Site selections include every row and preserve ownership in the existing table', () => {
    const source = read('resources/views/infrastruktur/partials/analytics.blade.php');
    const start = source.indexOf('function dashboardTarget(');
    const end = source.indexOf('window.applyInfrastructureFilter =', start);
    const context = { URLSearchParams };
    vm.createContext(context);
    vm.runInContext(source.slice(start, end).replace(/@json\(route\('([^']+)'\)\)/g, '"/$1"'), context);

    for (const scope of ['sewa', 'combat']) {
        const target = new URL(context.dashboardTarget(scope, 'all_records', '', scope === 'sewa' ? 'TP' : ''), 'https://example.test');
        assert.equal(target.searchParams.has('unique_sites'), false);
        assert.equal(target.searchParams.get('filter_field'), 'all_records');
        assert.equal(target.searchParams.get('ownership_scope'), scope === 'sewa' ? 'TP' : null);
    }
});

test('status and missing PKS cards filter all matching database rows in the module table', () => {
    const source = read('resources/views/infrastruktur/partials/analytics.blade.php');
    const overview = read('resources/views/infrastruktur/index.blade.php');
    const start = source.indexOf('function dashboardTarget(');
    const end = source.indexOf('window.applyInfrastructureFilter =', start);
    const context = { URLSearchParams };
    vm.createContext(context);
    vm.runInContext(source.slice(start, end).replace(/@json\(route\('([^']+)'\)\)/g, '"/$1"'), context);

    for (const [status, label] of [['active', 'Status On Air'], ['off_air', 'Status Off Air'], ['without_pks', 'Site tanpa Nomor PKS']]) {
        assert.ok(source.includes(`'id' => '${status}', 'label' => '${label}'`));
        assert.ok(overview.includes(`'id' => '${status}', 'label' => '${label}'`));
        for (const scope of ['sewa', 'combat']) {
            const target = new URL(context.dashboardTarget(scope, 'summary_status', status), 'https://example.test');
            assert.equal(target.searchParams.get('filter_field'), 'summary_status');
            assert.equal(target.searchParams.get('filter_value'), status);
            assert.equal(target.searchParams.has('unique_sites'), false);
            assert.equal(target.pathname, scope === 'sewa' ? '/infrastruktur.sewa-lahan.index' : '/infrastruktur.combat.index');
        }
    }
    for (const scope of ['sewa', 'combat']) {
        const target = new URL(context.dashboardTarget(scope, 'status', 'Operational / Active'), 'https://example.test');
        assert.equal(target.searchParams.has('unique_sites'), false);
    }
});

test('Total Site clears previous year and search filters before reloading the table', () => {
    for (const module of ['sewa-lahan', 'combat']) {
        const source = read(`resources/views/infrastruktur/${module}/index.blade.php`);
        const start = source.indexOf("$(document).on('infrastructure-filter',");
        const end = source.indexOf("$('#page-size').on('change'", start);
        const state = { year: '2025', search: 'SHARED', columnSearch: 'SHARED', reloads: 0 };
        const table = {
            search(value) { state.search = value; return table; },
            columns: Object.assign(() => ({ search(value) { state.columnSearch = value; } }), { adjust() {} }),
            ajax: { reload() { state.reloads++; assert.equal(state.year, 'all'); } },
        };
        const context = {
            document: {}, window: { location: { search: '' } }, URLSearchParams, currentFilter: { field: 'tahun', value: '2025' },
            exportBaseUrl: '/export', table, updateFilterBadge() {},
            $(target) {
                if (target === context.document) return { on(name, handler) { state.handler = handler; } };
                return {
                    removeClass() { state.visible = true; },
                    val(value) { if (value !== undefined) state.year = value; return state.year; },
                    attr(name, value) { state.exportUrl = value; },
                };
            },
        };
        vm.createContext(context);
        vm.runInContext(source.slice(start, end), context);
        state.handler({}, { filterField: 'all_records', filterValue: '' });
        assert.equal(state.visible, true);
        assert.equal(state.year, 'all');
        assert.equal(state.search, '');
        assert.equal(state.columnSearch, '');
        assert.equal(state.exportUrl, '/export?tahun=all');
        assert.equal(state.reloads, 1);
        assert.equal(context.currentFilter.field, 'all_records');
    }
});

test('year filter is shared by infrastructure charts, detail requests and table drill-downs', () => {
    const source = read('resources/views/infrastruktur/partials/analytics.blade.php');
    const overview = read('resources/views/infrastruktur/index.blade.php');
    for (const page of ['index', 'sewa-lahan/index', 'combat/index']) {
        assert.match(read(`resources/views/infrastruktur/${page}.blade.php`), /infrastruktur\.partials\.scope-filter/);
    }
    assert.match(overview, /dashboard\.data'\)\), \{ tahun: year \}/);
    assert.match(source, /dataParams\.tahun = selectedYear/);
    assert.match(source, /params\.set\('tahun', selectedYear\)/);

    const start = source.indexOf('function dashboardTarget(');
    const end = source.indexOf('window.applyInfrastructureFilter =', start);
    const context = { URLSearchParams, window: { location: { search: '?tahun=2026' } } };
    vm.createContext(context);
    vm.runInContext(source.slice(start, end).replace(/@json\(route\('([^']+)'\)\)/g, '"/$1"'), context);
    for (const scope of ['sewa', 'combat']) {
        const target = new URL(context.dashboardTarget(scope, 'summary_status', 'active', scope === 'sewa' ? 'TP' : ''), 'https://example.test');
        assert.equal(target.searchParams.get('tahun'), '2026');
        assert.equal(target.searchParams.get('ownership_scope'), scope === 'sewa' ? 'TP' : null);
        assert.equal(target.searchParams.get('filter_value'), 'active');
    }
});

test('Sewa Lahan and Combat chart selections use their existing lower table', () => {
    const analytics = read('resources/views/infrastruktur/partials/analytics.blade.php');
    const guard = analytics.indexOf("if (scope === 'sewa' || scope === 'combat') {");
    const modalLookup = analytics.indexOf("document.getElementById('infraDrilldownModal')", guard);

    assert.ok(guard >= 0, 'scope guard for Sewa Lahan and Combat is missing');
    assert.ok(modalLookup > guard, 'table routing must happen before opening the drilldown modal');
    assert.match(analytics, /within_180:\s*\['lease_window', '180'\]/);
    assert.match(analytics, /window\.applyInfrastructureFilter\(scope, selected\[0\], selected\[1\], root\.data\('ownership-scope'\)/);
    assert.match(analytics, /params\.set\('ownership_scope', ownershipScope\)/);
    assert.doesNotMatch(analytics, /Status Dokumen Terbanyak/);
    assert.doesNotMatch(analytics, /infra-priority-table/);
    assert.match(analytics, /infra-priority-trigger/);
    assert.match(analytics, /'priority',\s*'1'/);
});

test('Infrastructure tables expose the operational dataset columns', () => {
    const sewa = read('resources/views/infrastruktur/sewa-lahan/index.blade.php');
    const combat = read('resources/views/infrastruktur/combat/index.blade.php');

    for (const label of [
        'Status Site', 'Area', 'NOP', 'Ownership', 'Vendor', 'Periode Awal Baru',
        'Periode Akhir Baru', 'Masa Sewa', 'Harga Baru / Tahun', 'Total Harga Existing', 'Process Aging',
    ]) {
        assert.ok(sewa.includes(`<th>${label}</th>`), `Sewa Lahan is missing ${label}`);
    }

    for (const label of [
        'Renewal Pattern', 'Match NBAE', 'Combat Type', 'Status Combat', 'Kabupaten',
        'Status Masa Sewa', 'PKS Status', 'Aging Days', 'Tindak Lanjut', 'Prioritas',
    ]) {
        assert.ok(combat.includes(`<th>${label}</th>`), `Combat is missing ${label}`);
    }
});

test('infrastructure source formulas are replaced by derived display fields', () => {
    const sewa = read('resources/views/infrastruktur/sewa-lahan/index.blade.php');
    const combat = read('resources/views/infrastruktur/combat/index.blade.php');

    assert.match(sewa, /data: 'lease_duration'/);
    assert.match(sewa, /data: 'process_aging_days'/);
    assert.match(combat, /data: 'lease_duration'/);
    assert.match(combat, /data: 'pks_status_label'/);
    assert.match(combat, /data: 'process_aging_days'/);
    assert.match(combat, /data: 'next_action_label'/);
    assert.match(combat, /data: 'priority_label'/);
    assert.match(sewa, /startsWith\('='\)/);
    assert.match(combat, /startsWith\('='\)/);
});

test('Combat table flattens the canonical workbook snapshot for its visible dimensions', () => {
    const combat = read('resources/views/infrastruktur/combat/index.blade.php');

    assert.match(combat, /function sourceDetails\(row\)/);
    assert.match(combat, /\{ \.\.\.revenue, \.\.\.database, \.\.\.flat \}/);
    assert.match(combat, /const details = sourceDetails\(row\)/);
});

test('main-module filters opt into the shared compact drawer', () => {
    const expectedViews = [
        'resources/views/dashboard/master.blade.php',
        'resources/views/dashboard/index.blade.php',
        'resources/views/pnl/index.blade.php',
        'resources/views/infrastruktur/sewa-lahan/index.blade.php',
        'resources/views/infrastruktur/combat/index.blade.php',
        'resources/views/electricity/centralized/listrik-pln/index.blade.php',
        'resources/views/electricity/centralized/payment/index.blade.php',
        'resources/views/electricity/inbuilding/payment/index.blade.php',
        'resources/views/electricity/dashboard/index.blade.php',
        'resources/views/po-varcost/index.blade.php',
        'resources/views/presales/index.blade.php',
        'resources/views/data-potensi/data-site/index.blade.php',
    ];

    for (const view of expectedViews) {
        const content = read(view);
        assert.ok(/data-simaster-filter-panel="[^"]+"/.test(content)
            || /infrastruktur\.partials\.scope-filter/.test(content), `${view} is not connected to the drawer`);
    }

    const layout = read('resources/views/reports/scripts.blade.php');
    assert.match(layout, /querySelectorAll\('\[data-simaster-filter-panel\]'\)/);
    assert.match(layout, /appendChild\(source\)/, 'the original controls must be moved so existing handlers survive');
    assert.match(layout, /const periodPicker = source\.querySelector\('\.simaster-filter-wrapper'\)/);
    assert.match(layout, /periodMenu\.classList\.add\('simaster-filter-context-menu'\)/);
    assert.match(layout, /trigger\.className = 'simaster-context-filter-trigger'/);
    assert.match(layout, /popupBody\.appendChild\(periodPicker\)/, 'period/NOP picker must open inside the compact popup');
    assert.match(layout, /periodMenu\) periodMenu\.classList\.add\('show'\)/, 'existing period picker must be shown only when Filter is opened');

    const filtersCss = read('public/css/simaster.css');
    assert.match(filtersCss, /\.simaster-filter-context-menu\s*\{\s*max-height: none;\s*overflow: visible/);
    assert.match(filtersCss, /\.simaster-filter-drawer \.simaster-filter-wrapper > \.simaster-filter-btn\s*\{\s*display: none !important/);
    assert.doesNotMatch(filtersCss, /\.simaster-filter-drawer\s*\{[\s\S]*?height: 100%/);
});

test('Site Telkomsel and Site TP retain ownership filters under the Sewa Lahan parent', () => {
    const navigation = read('app/Filament/Modules/Infrastructure/Pages/SiteTelkomselPage.php')
        + read('app/Filament/Modules/Infrastructure/Pages/SiteTpPage.php');
    const provider = read('app/Filament/Modules/ModuleNavigation.php');

    assert.match(navigation, /'Site Telkomsel', 'infrastruktur\.sewa-lahan\.index'[^\n]+\['ownership_scope' => 'Telkomsel'\], 'Sewa Lahan'/);
    assert.match(navigation, /'Site TP', 'infrastruktur\.sewa-lahan\.index'[^\n]+\['ownership_scope' => 'TP'\], 'Sewa Lahan'/);
    assert.match(provider, /->childItems\(/);
    assert.match(read('resources/views/vendor/filament-panels/components/sidebar/item.blade.php'), /:child-items="\$childItem->getChildItems\(\)"/);
    assert.match(provider, /->url\(fn \(\) => route\(\$module\[2\], \$module\[5\]/);
});

test('changed inline browser scripts remain valid JavaScript after Blade values are rendered', () => {
    const bladeToJavaScript = source => source
        .replace(/@json\(route\('[^']+'\)\)/g, '"/generated-url"')
        .replace(/@json\(\\App\\Support\\LeaseStatus::[A-Z0-9_]+\)/g, '"status"')
        .replace(/\{\{\s*auth\(\)->user\(\)->hasRole\('admin'\)\s*\?\s*'true'\s*:\s*'false'\s*\}\}/g, 'true');
    const inlineScripts = file => [...read(file).matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)]
        .map(match => match[1])
        .filter(source => source.trim());

    for (const file of [
        'resources/views/infrastruktur/index.blade.php',
        'resources/views/infrastruktur/partials/analytics.blade.php',
        'resources/views/infrastruktur/sewa-lahan/index.blade.php',
        'resources/views/infrastruktur/combat/index.blade.php',
    ]) {
        for (const source of inlineScripts(file)) {
            assert.doesNotThrow(() => new vm.Script(bladeToJavaScript(source)), `${file} contains invalid JavaScript`);
        }
    }

    const layout = read('resources/views/reports/scripts.blade.php');
    const drawerScript = inlineScripts('resources/views/reports/scripts.blade.php')
        .find(source => source.includes("querySelectorAll('[data-simaster-filter-panel]')"));
    assert.ok(drawerScript, 'shared drawer script is missing');
    assert.doesNotThrow(() => new vm.Script(drawerScript), 'shared drawer script contains invalid JavaScript');
    assert.ok(layout.includes('simaster-context-filter-trigger'));
});
