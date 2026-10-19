/* Database-backed drilldowns for the existing PO Monitoring report. */
$(function () {
    const config = window.SimasterPoMonitoring;
    const modalElement = document.getElementById('po-detail-modal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const search = document.getElementById('po-detail-search');
    const selector = document.getElementById('po-detail-dataset');
    const count = document.getElementById('po-detail-count');
    const error = document.getElementById('po-detail-error');
    const format = value => Number(value).toLocaleString('id-ID');
    const textRenderer = DataTable.render.text();
    const escapeHtml = value => textRenderer.display(String(value ?? ''));
    const steps = { 1: 'Uploaded', 2: 'Manager NOP', 3: 'Manager SQ', 4: 'Manager NOS', 5: 'Manager NBAE' };
    let table = null;
    let dataset = 'hq';
    let category = '';
    let searchTimer;

    function refreshContext() {
        const definition = config.datasets[dataset];
        selector.value = dataset;
        document.getElementById('po-detail-title').textContent = definition.title + (category ? ` — ${category}` : ' — Semua data');
        document.getElementById('po-detail-source').href = definition.url;
        search.placeholder = dataset === 'presales' ? 'Cari judul, nomor dokumen, atau pengunggah…' : 'Cari nomor PO, deskripsi, atau status…';
        error.hidden = true;
        count.textContent = 'Memuat data…';
    }

    function loadRows() {
        refreshContext();
        table.search(search.value.trim()).ajax.reload();
    }

    function createTable() {
        const columns = Object.entries(config.datasets[dataset].columns);
        const head = document.querySelector('#po-detail-table thead');
        head.replaceChildren();
        const row = document.createElement('tr');
        columns.forEach(([, label]) => { const th = document.createElement('th'); th.textContent = label; row.append(th); });
        head.append(row);
        table = new DataTable('#po-detail-table', {
            processing: true, serverSide: true, scrollX: true, pageLength: 10, lengthMenu: [10, 25, 50, 100], ordering: false,
            dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"lip>',
            ajax: {
                url: config.detailsUrl,
                data: params => Object.assign(params, { dataset, category }),
                error: xhr => {
                    if (xhr.statusText === 'abort') return;
                    error.textContent = 'Data gagal dimuat. Tutup lalu buka kembali rincian untuk mencoba lagi.';
                    error.hidden = false;
                    count.textContent = 'Gagal memuat data.';
                },
            },
            columns: columns.map(([key]) => ({
                data: key, defaultContent: '—',
                render: value => escapeHtml(value === null || value === '' ? '—' : (key === 'current_step' ? (steps[value] || value) : value)),
            })),
            language: { emptyTable: 'Belum ada data.', zeroRecords: 'Tidak ada data yang cocok.', processing: 'Memuat data…', lengthMenu: '_MENU_ baris', info: '_START_–_END_ dari _TOTAL_ baris', infoEmpty: '0 baris', infoFiltered: '', paginate: { previous: 'Sebelumnya', next: 'Berikutnya' } },
        });
        table.on('draw.po', () => { count.textContent = `${format(table.page.info().recordsDisplay)} baris sesuai pilihan`; });
    }

    function open({ dataset: nextDataset = 'hq', category: nextCategory = '' } = {}) {
        if (!config.datasets[nextDataset]) return;
        clearTimeout(searchTimer);
        if (table) {
            table.settings()[0].jqXHR?.abort();
            if (dataset !== nextDataset) {
                $('#po-detail-table').off('.po');
                table.destroy();
                table = null;
                document.querySelector('#po-detail-table tbody').replaceChildren();
            }
        }
        dataset = nextDataset;
        category = nextCategory;
        search.value = '';
        refreshContext();
        modal.show();
        if (!table) createTable();
        else loadRows();
    }

    document.querySelectorAll('[data-po-open]').forEach(button => button.addEventListener('click', () => open({ dataset: button.dataset.poOpen })));
    selector.addEventListener('change', () => open({ dataset: selector.value }));
    search.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadRows, 250); });
    document.getElementById('po-detail-reset').addEventListener('click', () => { clearTimeout(searchTimer); category = ''; search.value = ''; loadRows(); });
    modalElement.addEventListener('shown.bs.modal', () => table?.columns.adjust());
    modalElement.addEventListener('hidden.bs.modal', () => clearTimeout(searchTimer));

    const charts = [];
    function renderModuleCharts() {
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const text = dark ? '#e2e8f0' : '#334155';
        config.charts.forEach((definition, index) => {
            const container = document.getElementById(`module-chart-${index}`);
            charts[index]?.destroy();
            if (!definition.data.some(point => point.y > 0)) {
                container.textContent = 'Belum ada data untuk ditampilkan.';
                container.className = 'd-flex align-items-center justify-content-center text-body-secondary';
                return;
            }
            const pie = definition.type === 'pie';
            const openPoint = point => open({ dataset: point.options.dataset || definition.dataset, category: point.options.dataset ? '' : point.name });
            charts[index] = Highcharts.chart(container, {
                chart: { type: definition.type, backgroundColor: 'transparent', height: 330, style: { fontFamily: 'inherit' } },
                title: { text: null }, credits: { enabled: false },
                accessibility: { description: definition.title },
                colors: pie ? window.SimasterChartPalette.series : window.SimasterChartPalette.distinctColors(definition.data.length),
                xAxis: { type: 'category', labels: { style: { color: text } }, lineColor: dark ? '#334155' : '#e2e8f0' },
                yAxis: { min: 0, allowDecimals: false, title: { text: definition.unit, style: { color: text } }, labels: { style: { color: text } }, gridLineColor: dark ? '#334155' : '#e2e8f0' },
                legend: { enabled: pie, itemStyle: { color: text }, events: { itemClick(event) { openPoint(event.legendItem); return false; } } },
                tooltip: { backgroundColor: dark ? '#1e293b' : '#fff', style: { color: text }, formatter() { return `${escapeHtml(this.key)}: <b>${format(this.y)}</b> ${definition.unit}${pie ? ` (${Highcharts.numberFormat(this.percentage, 1)}%)` : ''}`; } },
                plotOptions: {
                    series: { cursor: 'pointer', borderWidth: 0, colorByPoint: true, dataLabels: { enabled: !pie, style: { color: text, textOutline: 'none' } }, point: { events: { click() { openPoint(this); } } } },
                    pie: { innerSize: '55%', showInLegend: true, dataLabels: { enabled: definition.data.length <= 8, format: '{point.name}: {point.percentage:.1f}%', style: { color: text, textOutline: 'none' } } },
                },
                series: [{ name: definition.unit, data: definition.data }],
            });
        });
    }
    renderModuleCharts();
    window.addEventListener('simaster:theme-changed', renderModuleCharts);
});
