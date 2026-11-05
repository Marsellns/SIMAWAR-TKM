const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../public/js/report-chart-typography.js'), 'utf8');

function merge(...objects) {
    const result = {};
    for (const object of objects) {
        for (const [key, value] of Object.entries(object || {})) {
            result[key] = Array.isArray(value) ? value.map(item => item && typeof item === 'object' ? merge(item) : item)
                : value && typeof value === 'object' ? merge(result[key], value) : value;
        }
    }
    return result;
}

function load(theme = 'light') {
    class HighchartsChart {
        init(options) { this.options = options; }
        update(options) { this.updated = options; }
    }
    const Highcharts = {
        Chart: HighchartsChart, merge,
        setOptions(options) { this.defaults = options; },
        wrap(target, key, wrapper) {
            const original = target[key];
            target[key] = function (...args) { return wrapper.call(this, original, ...args); };
        },
    };
    const Chart = { defaults: { font: {} }, register(plugin) { this.plugin = plugin; } };
    const window = { Highcharts, Chart, Apex: { chart: { animations: { enabled: false } } } };
    vm.runInNewContext(source, { window, document: { documentElement: { getAttribute: () => theme }, addEventListener() {} } });
    return window;
}

test('Highcharts separates titles, chart labels and notes without altering data or callbacks', () => {
    const { Highcharts: H } = load();
    const formatter = function () { return this.y; };
    const options = {
        title: { text: 'Tagihan', style: { fontSize: '24px', color: '#123456' } },
        subtitle: { text: 'Periode berjalan' },
        xAxis: [{ labels: { style: { fontSize: '12px' } }, title: { text: 'Bulan' } }],
        tooltip: { formatter },
        series: [{ data: [20, 30], dataLabels: [{ enabled: true, formatter }, { enabled: false }] }],
        plotOptions: { pie: { dataLabels: false } },
    };
    const chart = new H.Chart();
    chart.init(options);
    assert.equal(chart.options.title.style.fontWeight, '600');
    assert.equal(chart.options.title.style.fontSize, '24px');
    assert.equal(chart.options.title.style.color, '#123456');
    assert.equal(chart.options.subtitle.style.fontWeight, '400');
    assert.equal(chart.options.legend.itemStyle.fontWeight, '500');
    assert.equal(chart.options.xAxis[0].labels.style.fontWeight, '400');
    assert.equal(chart.options.xAxis[0].labels.style.fontSize, '15px');
    assert.equal(chart.options.xAxis[0].title.style.fontWeight, '500');
    assert.equal(chart.options.tooltip.style.fontWeight, '400');
    assert.equal(chart.options.tooltip.formatter, formatter);
    assert.deepEqual(chart.options.series[0].data, [20, 30]);
    assert.equal(chart.options.series[0].dataLabels[0].style.fontWeight, '600');
    assert.equal(chart.options.series[0].dataLabels[0].formatter, formatter);
    assert.equal(chart.options.series[0].dataLabels[1].enabled, false);
    assert.equal(chart.options.plotOptions.pie.dataLabels, false);
    assert.equal(options.xAxis[0].labels.style.fontSize, '12px');
});

test('Highcharts updates and responsive options keep the typography hierarchy', () => {
    const { Highcharts: H } = load();
    const chart = new H.Chart();
    chart.update({ responsive: { rules: [{ condition: { maxWidth: 600 }, chartOptions: {
        title: { text: 'Mobile' }, legend: { enabled: false }, xAxis: { labels: { rotation: -45 } },
    } }] } });
    assert.equal(chart.updated.title, undefined);
    const mobile = chart.updated.responsive.rules[0].chartOptions;
    assert.equal(mobile.title.style.fontWeight, '600');
    assert.equal(mobile.legend.enabled, false);
    assert.equal(mobile.xAxis.labels.style.fontWeight, '400');
    assert.equal(mobile.xAxis.labels.rotation, -45);
});

test('bar charts retain visible category boundaries and value steps in both themes', () => {
    for (const theme of ['light', 'dark']) {
        const { Highcharts: H } = load(theme);
        const chart = new H.Chart();
        chart.init({ chart: { type: 'column' }, yAxis: { gridLineColor: '#f1f5f9' }, series: [{ data: [10, 20] }] });
        assert.equal(chart.options.xAxis.gridLineWidth, 1);
        assert.equal(chart.options.yAxis.gridLineWidth, 1);
        assert.equal(chart.options.yAxis.gridLineDashStyle, 'ShortDash');
        assert.equal(chart.options.yAxis.gridLineColor, theme === 'dark' ? '#475569' : '#cbd5e1');
        assert.deepEqual(chart.options.series[0].data, [10, 20]);
        const pie = new H.Chart();
        pie.init({ chart: { type: 'pie' }, series: [{ data: [10, 20] }] });
        assert.equal(pie.options.yAxis.gridLineWidth, undefined);
    }
});

test('Chart.js keeps scriptable fonts stable across updates and distinguishes their roles', () => {
    const { Chart } = load();
    const font = context => ({ family: 'Inter', size: context.large ? 22 : 10 });
    const options = {
        scales: { x: { ticks: { font }, title: { font } } },
        plugins: { title: { font }, subtitle: false, tooltip: { bodyFont: font }, datalabels: { font } },
    };
    const chart = { config: { options } };
    Chart.plugin.beforeInit(chart);
    const tickFont = options.scales.x.ticks.font;
    assert.equal(tickFont({}).weight, '400');
    assert.equal(tickFont({}).size, 15);
    assert.equal(tickFont({ large: true }).size, 22);
    assert.equal(options.scales.x.title.font({}).weight, '500');
    assert.equal(options.plugins.title.font({}).weight, '600');
    assert.equal(options.plugins.title.font({}).size, 18);
    assert.equal(options.plugins.legend.labels.font.weight, '500');
    assert.equal(options.plugins.tooltip.bodyFont({}).weight, '400');
    assert.equal(options.plugins.tooltip.titleFont.weight, '600');
    assert.equal(options.plugins.datalabels.font({}).weight, '600');
    assert.equal(options.plugins.subtitle, false);
    Chart.plugin.beforeUpdate(chart);
    assert.equal(options.scales.x.ticks.font, tickFont);
    assert.equal(Chart.defaults.font.weight, '400');
});

test('ApexCharts uses semibold data labels while preserving existing chart settings', () => {
    const { Apex } = load();
    assert.equal(Apex.title.style.fontWeight, '600');
    assert.equal(Apex.subtitle.style.fontWeight, '400');
    assert.equal(Apex.xaxis.labels.style.fontWeight, '400');
    assert.equal(Apex.yaxis.labels.style.fontWeight, '400');
    assert.equal(Apex.dataLabels.style.fontWeight, '600');
    assert.equal(Apex.legend.fontWeight, 500);
    assert.equal(Apex.tooltip.style.fontWeight, '400');
    assert.equal(Apex.chart.animations.enabled, false);
});
