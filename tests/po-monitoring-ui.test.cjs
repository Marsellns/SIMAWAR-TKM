const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../public/assets/po-monitoring/js/dashboard.js'), 'utf8');

function load() {
    const elements = new Map();
    const element = id => {
        if (!elements.has(id)) elements.set(id, {
            value: '', hidden: true, textContent: '', dataset: {}, events: {}, children: [],
            addEventListener(name, callback) { this.events[name] = callback; },
            replaceChildren() { this.children = []; },
            append(child) { this.children.push(child); },
        });
        return elements.get(id);
    };
    const buttons = ['hq', 'varcost', 'presales'].map(key => Object.assign(element(key), { dataset: { poOpen: key } }));
    const tables = [];
    function DataTable(selector, options) {
        this.options = options;
        this.filters = () => options.ajax.data({});
        this.events = {};
        this.on = (event, callback) => { this.events[event] = callback; };
        this.search = value => { this.searchValue = value; return this; };
        this.ajax = { reload: () => { this.reloads = (this.reloads || 0) + 1; } };
        this.page = { info: () => ({ recordsDisplay: 3 }) };
        this.columns = { adjust() {} };
        this.settings = () => [{ jqXHR: { abort: () => { this.aborted = true; } } }];
        this.destroy = () => { this.destroyed = true; };
        tables.push(this);
    }
    DataTable.render = { text: () => ({ display: value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;') }) };
    const charts = [];
    const datasets = Object.fromEntries(['hq', 'varcost', 'presales'].map(key => [key, { title: key, url: '/'+key, columns: { po_number: 'Nomor PO', current_step: 'Tahap' } }]));
    const definitions = [
        { title: 'Submodul', type: 'column', dataset: 'hq', unit: 'Baris', data: [{ name: 'PO Varcost', y: 2, dataset: 'varcost' }] },
        { title: 'Status HQ', type: 'pie', dataset: 'hq', unit: 'PO', data: [{ name: 'Overdue', y: 3 }] },
        { title: 'Status GR', type: 'bar', dataset: 'varcost', unit: 'PO', data: [{ name: 'Belum diisi', y: 1 }] },
        { title: 'Presales', type: 'pie', dataset: 'presales', unit: 'Dokumen', data: [{ name: 'Pending', y: 2 }] },
    ];
    const window = { SimasterPoMonitoring: { datasets, charts: definitions, detailsUrl: '/po-monitoring/details' }, SimasterChartPalette: { series: ['blue'], distinctColors: count => Array.from({ length: count }, (_, index) => `color-${index}`) }, addEventListener() {} };
    let pendingSearch;
    vm.runInNewContext(source, {
        window, DataTable,
        $: arg => typeof arg === 'function' ? arg() : { off() {} },
        document: { getElementById: element, querySelector: element, createElement: element, querySelectorAll: () => buttons, documentElement: { getAttribute: () => 'light' } },
        bootstrap: { Modal: { getOrCreateInstance: () => ({ show() {} }) } },
        Highcharts: { chart(container, options) { charts.push(options); return { destroy() {} }; } },
        clearTimeout: () => { pendingSearch = null; },
        setTimeout: callback => { pendingSearch = callback; return 1; },
    });
    return { element, buttons, tables, charts, runSearch: () => pendingSearch() };
}

test('each PO card opens its own database dataset, including a previously unused module', () => {
    const { buttons, tables } = load();
    buttons.forEach((button, index) => {
        button.events.click();
        assert.equal(tables[index].filters().dataset, button.dataset.poOpen);
        assert.equal(tables[index].filters().category, '');
        assert.equal(tables[index].options.serverSide, true);
        if (index) assert.equal(tables[index - 1].destroyed, true);
    });
});

test('submodule bars and every status diagram select the corresponding dataset and category', () => {
    const { charts, tables } = load();
    const selections = [['varcost', ''], ['hq', 'Overdue'], ['varcost', 'Belum diisi'], ['presales', 'Pending']];
    charts.forEach((chart, index) => {
        const point = chart.series[0].data[0];
        chart.plotOptions.series.point.events.click.call({ options: point, name: point.name });
        const filters = tables.at(-1).filters();
        assert.deepEqual([filters.dataset, filters.category], selections[index]);
    });
});

test('typed search retains the category and reset restores the entire selected module', () => {
    const { charts, tables, element, runSearch } = load();
    charts[1].plotOptions.series.point.events.click.call({ options: {}, name: 'Overdue' });
    const table = tables[0];
    element('po-detail-search').value = '  HQ-123  ';
    element('po-detail-search').events.input();
    runSearch();
    assert.equal(table.searchValue, 'HQ-123');
    assert.equal(table.filters().category, 'Overdue');
    element('po-detail-reset').events.click();
    assert.equal(table.filters().category, '');
    assert.equal(table.filters().dataset, 'hq');
    assert.equal(table.searchValue, '');
});

test('database text is escaped and request aborts do not display a loading failure', () => {
    const { buttons, tables, element } = load();
    buttons[0].events.click();
    const options = tables[0].options;
    assert.equal(options.columns[0].render('<img src=x onerror=alert(1)>'), '&lt;img src=x onerror=alert(1)&gt;');
    assert.equal(options.columns[0].render(null), '—');
    assert.equal(options.columns[1].render(2), 'Manager NOP');
    options.ajax.error({ statusText: 'abort' });
    assert.equal(element('po-detail-error').hidden, true);
    options.ajax.error({ statusText: 'error' });
    assert.equal(element('po-detail-error').hidden, false);
});
