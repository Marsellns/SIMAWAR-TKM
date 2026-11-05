    let analyticsData = null;
    let activePnlTab = 'site';
    const analyticsCharts = new Map();
    const pnlTabs = Array.from(document.querySelectorAll('[data-pnl-tab]'));

    function activatePnlTab(name) {
        if (!pnlTabs.some(tab => tab.dataset.pnlTab === name)) return;
        activePnlTab = name;
        pnlTabs.forEach(tab => {
            const active = tab.dataset.pnlTab === name;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        document.querySelectorAll('[data-pnl-panel]').forEach(panel => {
            panel.hidden = panel.dataset.pnlPanel !== name;
        });
        requestAnimationFrame(() => {
            renderActivePnlCharts();
            if (name === 'analysis') table.columns.adjust();
        });
    }

    pnlTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activatePnlTab(tab.dataset.pnlTab));
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? index + 1 : event.key === 'ArrowLeft' ? index - 1
                : event.key === 'Home' ? 0 : event.key === 'End' ? pnlTabs.length - 1 : null;
            if (next === null) return;
            event.preventDefault();
            const target = pnlTabs[(next + pnlTabs.length) % pnlTabs.length];
            activatePnlTab(target.dataset.pnlTab);
            target.focus();
        });
    });

    function updatePnlDataContext() {
        $('#pnl-data-context').text([
            $('#filter-month option:selected').text(), $('#filter-year').val(),
            $('#filter-nop option:selected').text(), $('#filter-city option:selected').text(),
            $('#filter-status option:selected').text(), selectedSiteCode,
        ].filter(Boolean).join(' · '));
    }

    function drillPnl(filters) {
        clearHighlightSiteFilter();
        Object.entries(filters).forEach(([field, value]) => $('#filter-' + field).val(value));
        table.search('');
        table.columns().search('');
        updateExportUrl();
        activatePnlTab('analysis');
        $('#pnl-table').one('draw.dt.pnlDrill', () => {
            document.getElementById('pnl-table-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
        table.page('first').draw();
        loadPnlCharts();
        loadPnlHighlights();
    }

    function pnlPointClick() {
        const custom = this.options.custom;
        if (custom?.site) openSiteHistory(custom.site);
        else if (custom?.filters) drillPnl(custom.filters);
    }

    function renderActivePnlCharts() {
        if (!analyticsData) return;
        const data = analyticsData;
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const text = dark ? '#cbd5e1' : '#334155';
        const profitColor = palette.financial.profit;
        const lossColor = palette.financial.loss;
        const statusDefs = [
            ['Profit', 'Profit', 'profit_sites', profitColor],
            ['Loss / Impas', 'Loss', 'loss_sites', lossColor],
            ['Tidak Aktif', 'TidakAktif', 'inactive_sites', palette.financial.inactive],
        ];
        const base = {
            chart: { backgroundColor: 'transparent', style: { fontFamily: 'inherit' } },
            title: { text: null }, credits: { enabled: false },
            xAxis: { labels: { style: { color: text } }, lineColor: dark ? '#334155' : '#e2e8f0' },
            yAxis: { title: { style: { color: text } }, labels: { style: { color: text } }, gridLineColor: dark ? '#334155' : '#e2e8f0' },
            legend: { itemStyle: { color: text, fontWeight: '500' } },
            tooltip: { backgroundColor: dark ? '#0f172a' : '#fff', style: { color: text } },
            plotOptions: {
                series: { cursor: 'pointer', borderWidth: 0, point: { events: { click: pnlPointClick } } },
                column: { borderRadius: 4 }, bar: { borderRadius: 3 },
            },
        };
        function draw(id, options, available = true, height = 320) {
            const element = document.getElementById(id);
            analyticsCharts.get(id)?.destroy();
            analyticsCharts.delete(id);
            element.style.height = height + 'px';
            if (!available) {
                element.innerHTML = '<p class="text-body-secondary text-center py-5">Belum ada data untuk filter ini.</p>';
                return;
            }
            analyticsCharts.set(id, Highcharts.chart(id, Highcharts.merge(base, { chart: { height } }, options)));
        }
        const moneyAxis = { title: { text: 'Miliar Rp' }, labels: { formatter() { return fmtNumber(this.value / 1e9, 1); } }, plotLines: [{ value: 0, color: '#94a3b8', width: 1 }] };
        const moneyTooltip = { shared: true, formatter() {
            const points = this.points || [this];
            return '<b>' + esc(points[0]?.key ?? this.key) + '</b><br>' + points.map(point =>
                esc(point.series.name) + ': <b>' + fmtRupiah(point.y) + '</b>'
            ).join('<br>');
        } };
        const point = (row, value, field, extra = {}) => ({
            name: row.name, y: value === null ? null : Number(value),
            custom: { filters: { [field]: row.key, ...extra } },
        });
        const statusChart = (id, rows, field, horizontal = false) => draw(id, {
            chart: { type: horizontal ? 'bar' : 'column' },
            xAxis: { categories: rows.map(row => row.name) },
            yAxis: { min: 0, allowDecimals: false, title: { text: 'Jumlah site' } },
            plotOptions: { series: { stacking: 'normal' } },
            tooltip: { pointFormat: '{series.name}: <b>{point.y}</b> site' },
            series: statusDefs.map(([name, key, metric, color]) => ({ name, color, data: rows.map(row => point(row, row[metric], field, { status: key })) })),
        }, rows.some(row => row.sites > 0), horizontal ? Math.max(320, rows.length * 34) : 320);
        const financialChart = (id, rows, field, contribution = false, horizontal = false) => {
            const metrics = contribution
                ? [['Profit', 'profit', profitColor], ['Loss', 'loss', lossColor]]
                : [['Revenue', 'revenue', palette.financial.revenue], ['Cost', 'cost', palette.financial.cost], ['Net P&L', 'profit_loss', profitColor]];
            draw(id, {
                chart: { type: horizontal ? 'bar' : 'column' },
                xAxis: { categories: rows.map(row => row.name) }, yAxis: moneyAxis, tooltip: moneyTooltip,
                series: metrics.map(([name, metric, color]) => ({
                    name, color, negativeColor: lossColor,
                    data: rows.map(row => point(row, row[metric], field, contribution ? { status: metric === 'profit' ? 'Profit' : 'Loss' } : {})),
                })),
            }, rows.some(row => row.active > 0), horizontal ? Math.max(320, rows.length * 38) : 320);
        };
        if (activePnlTab === 'site') {
            statusChart('pnl-site-status-chart', data.nop, 'nop');
            draw('pnl-category-chart', {
                chart: { type: 'pie' },
                tooltip: { pointFormat: '<b>{point.y}</b> site ({point.percentage:.1f}%)' },
                legend: { itemStyle: { fontSize: '1rem' } },
                plotOptions: { pie: { innerSize: '55%', showInLegend: true, dataLabels: { enabled: false } } },
                series: [{ name: 'Site', data: data.status.map((row, index) => ({ name: row.name, y: row.y, color: statusDefs[index][3], custom: { filters: { status: row.key } } })) }],
            }, data.sites_count > 0, 300);
        }
        if (activePnlTab === 'nop') {
            financialChart('pnl-nop-financial-chart', data.nop, 'nop');
            draw('pnl-nop-margin-chart', {
                chart: { type: 'bar' }, legend: { enabled: false },
                xAxis: { categories: data.nop.map(row => row.name) },
                yAxis: { title: { text: 'Margin (%)' } },
                tooltip: { pointFormat: 'Margin: <b>{point.y:.2f}%</b>' },
                series: [{ name: 'Margin', color: profitColor, negativeColor: lossColor, data: data.nop.map(row => point(row, row.margin, 'nop')) }],
            }, data.nop.some(row => row.margin !== null));
        }
        if (activePnlTab === 'monthly') {
            const rows = data.monthly;
            const monthlySeries = metric => rows.map(row => ({ y: row[metric], custom: { filters: { month: String(row.month) } } }));
            const categories = rows.map(row => monthNames[row.month - 1]);
            draw('pnl-growth-chart', {
                chart: { type: 'column' }, xAxis: { categories }, yAxis: moneyAxis, tooltip: moneyTooltip,
                series: [
                    { name: 'Revenue', color: palette.financial.revenue, data: monthlySeries('revenue') },
                    { name: 'Cost', color: palette.financial.cost, data: monthlySeries('cost') },
                    { name: 'Net P&L', color: profitColor, negativeColor: lossColor, data: monthlySeries('profit_loss') },
                ],
            }, rows.length > 0);
            draw('pnl-monthly-margin-chart', {
                chart: { type: 'line' }, xAxis: { categories }, yAxis: { title: { text: 'Margin (%)' } },
                tooltip: { pointFormat: 'Margin: <b>{point.y:.2f}%</b>' }, legend: { enabled: false },
                series: [{ name: 'Margin', color: '#8b5cf6', data: monthlySeries('margin') }],
            }, rows.some(row => row.margin !== null));
        }
        if (activePnlTab === 'nop-pnl') {
            financialChart('pnl-nop-profit-loss-chart', data.nop, 'nop', true);
            statusChart('pnl-nop-status-chart', data.nop, 'nop');
        }
        if (activePnlTab === 'city') {
            financialChart('pnl-city-financial-chart', data.cities, 'city', true, true);
            statusChart('pnl-city-status-chart', data.cities, 'city', true);
        }
        if (activePnlTab === 'analysis') {
            draw('pnl-site-comparison-chart', {
                chart: { type: 'column' }, xAxis: { categories: data.analysis.map(site => site.site_id) },
                yAxis: moneyAxis, tooltip: moneyTooltip,
                series: [
                    ['Revenue', 'revenue', palette.financial.revenue], ['Cost', 'cost', palette.financial.cost],
                    ['Net P&L', 'profit_loss', profitColor],
                ].map(([name, metric, color]) => ({
                    name, color, negativeColor: lossColor,
                    data: data.analysis.map(site => ({ y: site[metric], custom: { site } })),
                })),
            }, data.analysis.length > 0);
        }
    }

    function loadPnlCharts() {
        if (chartRequest) chartRequest.abort();
        analyticsData = null;
        $('[data-pnl-panel] .pnl-chart, #pnl-category-chart').addClass('opacity-50').attr('aria-busy', 'true');
        $('#pnl-analytics-error').addClass('d-none');
        updatePnlDataContext();
        let request;
        request = $.ajax({
            url: chartApiUrl, data: getChartFilters(),
            success(res) {
                analyticsData = res;
                $('#pnl-chart-context').text(fmtNumber(res.sites_count) + ' site · ' + $('#filter-year').val() + ' · ' + $('#filter-month option:selected').text() + ' · ' + $('#filter-nop option:selected').text() + ' · ' + $('#filter-city option:selected').text() + '. Status Loss mencakup impas (P&L = 0).');
                renderActivePnlCharts();
            },
            error(_xhr, status) {
                if (status === 'abort') return;
                analyticsCharts.forEach(chart => chart.destroy());
                analyticsCharts.clear();
                $('.pnl-chart, #pnl-category-chart').empty();
                $('#pnl-chart-context').text('Diagram belum tersedia.');
                $('#pnl-analytics-error').removeClass('d-none').text('Diagram gagal dimuat. ')
                    .append($('<button type="button" class="btn btn-sm btn-outline-danger">').text('Coba lagi').on('click', loadPnlCharts));
            },
            complete() {
                if (chartRequest !== request) return;
                $('.pnl-chart, #pnl-category-chart').removeClass('opacity-50').attr('aria-busy', 'false');
                chartRequest = null;
            },
        });
        chartRequest = request;
    }

    $('#pnl-clear-drilldown').on('click', () => {
        $('#filter-nop, #filter-city, #filter-status').val('');
        $('#filter-month').val('all');
        drillPnl({});
    });
    window.addEventListener('simaster:theme-changed', renderActivePnlCharts);
    window.addEventListener('simaster:data-changed', event => {
        event.detail.handled = true;
        loadPnlCharts();
        loadPnlHighlights();
    });
    loadPnlCharts();
    loadPnlHighlights();
