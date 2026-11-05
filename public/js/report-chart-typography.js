(() => {
    if (window.SimasterChartTypography) return;
    const configured = new WeakSet();
    const wrappedFonts = new WeakMap();
    const labelSize = 15;

    function minimumSize(value, minimum = labelSize) {
        const match = String(value ?? '').match(/^([\d.]+)(px|rem|em)?$/);
        const pixels = match ? Number(match[1]) * (/^(rem|em)$/.test(match[2]) ? 16 : 1) : 0;
        return Math.max(minimum, pixels) + 'px';
    }

    function textStyle(style = {}, minimum = labelSize, weight = '400') {
        return { ...style, fontSize: minimumSize(style.fontSize, minimum), fontWeight: weight };
    }

    function highchartsOptions(H, options = {}, defaults = true) {
        const result = H.merge({}, options);
        const hasBars = ['column', 'bar'].includes(result.chart?.type)
            || (result.series || []).some(series => ['column', 'bar'].includes(series.type));
        const dark = document.documentElement?.getAttribute?.('data-bs-theme') === 'dark';
        const label = (value, weight = '400') => {
            if (Array.isArray(value)) return value.map(item => label(item, weight));
            if (value === false) return value;
            return { ...value, style: textStyle(value?.style, labelSize, weight) };
        };
        if (defaults || result.chart) result.chart = H.merge(result.chart || {}, { style: textStyle(result.chart?.style) });
        for (const [key, size, weight] of [['title', 18, '600'], ['subtitle', 15, '400'], ['caption', 15, '400']]) {
            if (defaults || result[key]) result[key] = label(result[key], weight);
            if (result[key]) result[key].style = textStyle(result[key].style, size, weight);
        }
        if (defaults || result.legend) {
            result.legend = H.merge(result.legend || {}, { itemStyle: textStyle(result.legend?.itemStyle, labelSize, '500') });
            if (result.legend.title) result.legend.title = label(result.legend.title, '600');
        }
        if (defaults || result.tooltip) result.tooltip = label(result.tooltip);
        for (const key of ['xAxis', 'yAxis', 'colorAxis']) {
            if (!defaults && !result[key]) continue;
            if (key === 'colorAxis' && !result[key]) continue;
            const axis = value => {
                const axisOptions = H.merge({}, value || {});
                if (hasBars && key !== 'colorAxis') {
                    // Keep month/category boundaries and value steps visible on report backgrounds.
                    Object.assign(axisOptions, {
                        gridLineWidth: 1,
                        gridLineColor: dark ? (key === 'yAxis' ? '#475569' : '#334155') : (key === 'yAxis' ? '#cbd5e1' : '#d8e0eb'),
                        gridLineDashStyle: key === 'yAxis' ? 'ShortDash' : 'Solid',
                        lineColor: dark ? '#64748b' : '#94a3b8',
                        lineWidth: 1,
                    });
                }
                axisOptions.labels = label(axisOptions.labels);
                axisOptions.title = label(axisOptions.title, '500');
                if (axisOptions.stackLabels) axisOptions.stackLabels = label(axisOptions.stackLabels, '500');
                for (const lines of ['plotLines', 'plotBands']) {
                    if (axisOptions[lines]) axisOptions[lines] = axisOptions[lines].map(line => ({ ...line, ...(line.label ? { label: label(line.label) } : {}) }));
                }
                return axisOptions;
            };
            result[key] = Array.isArray(result[key]) ? result[key].map(axis) : axis(result[key]);
        }
        if (defaults || result.plotOptions) {
            result.plotOptions ??= {};
            const types = defaults ? new Set(['series', 'pie', 'column', 'bar', 'line', ...Object.keys(result.plotOptions)]) : Object.keys(result.plotOptions);
            for (const type of types) {
                result.plotOptions[type] ??= {};
                result.plotOptions[type].dataLabels = label(result.plotOptions[type].dataLabels, '600');
            }
        }
        if (result.series) result.series = result.series.map(series => series.dataLabels === undefined ? series : { ...series, dataLabels: label(series.dataLabels, '600') });
        if (result.responsive?.rules) result.responsive.rules = result.responsive.rules.map(rule => ({ ...rule, chartOptions: highchartsOptions(H, rule.chartOptions, false) }));
        return result;
    }

    function chartFont(value, minimum = labelSize, weight = '400') {
        if (typeof value === 'function') {
            if (!wrappedFonts.has(value)) wrappedFonts.set(value, new Map());
            const fonts = wrappedFonts.get(value);
            const role = `${minimum}:${weight}`;
            if (!fonts.has(role)) {
                const wrapped = (...args) => chartFont(value(...args), minimum, weight);
                fonts.set(role, wrapped);
                wrappedFonts.set(wrapped, new Map([[role, wrapped]]));
            }
            return fonts.get(role);
        }
        return { ...value, size: Math.max(minimum, Number(value?.size) || 0), weight };
    }

    function chartJsOptions(options) {
        options.font = chartFont(options.font);
        for (const scale of Object.values(options.scales || {})) {
            for (const key of ['ticks', 'title', 'pointLabels']) {
                if (scale[key] === false) continue;
                scale[key] ??= {};
                scale[key].font = chartFont(scale[key].font, labelSize, key === 'title' ? '500' : '400');
            }
        }
        if (options.plugins !== false) {
            options.plugins ??= {};
            for (const key of ['legend', 'title', 'subtitle', 'tooltip', 'datalabels']) {
                if (options.plugins[key] === false) continue;
                if (key === 'datalabels' && !options.plugins[key]) continue;
                options.plugins[key] ??= {};
                const plugin = options.plugins[key];
                if (key === 'legend') {
                    plugin.labels ??= {};
                    plugin.labels.font = chartFont(plugin.labels.font, labelSize, '500');
                } else if (key === 'tooltip') {
                    for (const font of ['titleFont', 'bodyFont', 'footerFont']) plugin[font] = chartFont(plugin[font], labelSize, font === 'bodyFont' ? '400' : '600');
                } else plugin.font = chartFont(plugin.font, key === 'title' ? 18 : labelSize, key === 'title' || key === 'datalabels' ? '600' : '400');
            }
        }
    }

    function configure() {
        const H = window.Highcharts;
        if (H?.Chart && H.wrap && !configured.has(H.Chart)) {
            configured.add(H.Chart);
            H.setOptions(highchartsOptions(H));
            H.wrap(H.Chart.prototype, 'init', function (proceed, options, ...rest) {
                return proceed.call(this, highchartsOptions(H, options), ...rest);
            });
            H.wrap(H.Chart.prototype, 'update', function (proceed, options, ...rest) {
                return proceed.call(this, highchartsOptions(H, options, false), ...rest);
            });
        }
        const Chart = window.Chart;
        if (Chart?.register && Chart.defaults?.font && !configured.has(Chart)) {
            configured.add(Chart);
            Chart.defaults.font.size = labelSize;
            Chart.defaults.font.weight = '400';
            Chart.register({
                id: 'simaster-readability',
                beforeInit(chart) { chartJsOptions(chart.config.options); },
                beforeUpdate(chart) { chartJsOptions(chart.config.options); },
            });
        }
    }

    const apex = window.Apex || {};
    window.Apex = {
        ...apex,
        title: { ...apex.title, style: textStyle(apex.title?.style, 18, '600') },
        subtitle: { ...apex.subtitle, style: textStyle(apex.subtitle?.style) },
        dataLabels: { ...apex.dataLabels, style: textStyle(apex.dataLabels?.style, labelSize, '600') },
        legend: { ...apex.legend, fontSize: minimumSize(apex.legend?.fontSize), fontWeight: 500 },
        xaxis: { ...apex.xaxis, labels: { ...apex.xaxis?.labels, style: textStyle(apex.xaxis?.labels?.style) } },
        yaxis: { ...apex.yaxis, labels: { ...apex.yaxis?.labels, style: textStyle(apex.yaxis?.labels?.style) } },
        tooltip: { ...apex.tooltip, style: textStyle(apex.tooltip?.style) },
    };
    window.SimasterChartTypography = Object.freeze({ configure });
    // Chart libraries are loaded by each report after this shared asset.
    document.addEventListener('load', event => {
        if (event.target?.tagName === 'SCRIPT') configure();
    }, true);
    document.addEventListener('DOMContentLoaded', configure);
    document.addEventListener('livewire:navigated', configure);
    configure();
})();
