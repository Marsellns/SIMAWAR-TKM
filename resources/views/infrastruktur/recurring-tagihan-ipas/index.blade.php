@extends('layouts.app')

@section('title', 'Recurring (Tagihan Ipas) — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — Recurring (Tagihan Ipas)</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('infrastruktur.partials.entry-actions', ['dataset' => 'recurring-tagihan-ipas'])
            <a href="{{ route('infrastruktur.upload.export', 'recurring-tagihan-ipas') }}" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
            <a href="{{ route('infrastruktur.upload.export', ['dataset' => 'recurring-tagihan-ipas', 'detail' => 1]) }}" class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Rincian CSV</a>
        </div>
    </div>

    <div class="card mb-3" data-simaster-filter-panel="Filter Tagihan IPAS">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="filter-tp" class="form-label mb-0 small fw-semibold text-nowrap">Filter TP:</label>
                <select id="filter-tp" class="form-select form-select-sm" style="width:auto;min-width:160px">
                    <option value="all">Semua TP</option>
                    @foreach ($tpList as $tp)
                        <option value="{{ $tp }}">{{ $tp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 500] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <section class="infra-analytics mb-4" aria-labelledby="tagihan-analytics-title">
        <h2 class="h5 mb-2" id="tagihan-analytics-title">Analitik Tagihan IPAS</h2>
        <p class="small text-body-secondary mb-3">Kartu dan diagram dihitung dari rincian termin sesuai filter TP. Klik kartu atau batang diagram untuk menampilkan site terkait pada tabel di bawah. Setiap baris tabel tetap merangkum seluruh rincian site tersebut.</p>
        <div class="row g-3 mb-3">
            @foreach ([
                ['key' => 'sites', 'label' => 'Total Site', 'class' => 'primary', 'icon' => 'site'],
                ['key' => 'details', 'label' => 'Jumlah Rincian Termin', 'class' => 'success', 'icon' => 'details'],
                ['key' => 'amount', 'label' => 'Total Tagihan', 'class' => 'info', 'icon' => 'amount'],
                ['key' => 'details_per_site', 'label' => 'Rata-rata Rincian / Site', 'class' => 'warning', 'icon' => 'average'],
            ] as $card)
                <div class="col-xl-3 col-md-6 col-12">
                    <button type="button" class="card h-100 w-100 border-{{ $card['class'] }} infra-summary-card text-start ipas-metric-card" data-ipas-card="{{ $card['key'] }}" aria-label="Lihat data {{ $card['label'] }}">
                        <div class="card-body py-2 infra-summary-card-body">
                            <div>
                                <div class="infra-summary-label text-body-secondary">{{ $card['label'] }}</div>
                                <div class="fs-4 fw-bold" data-ipas-metric="{{ $card['key'] }}" aria-live="polite">—</div>
                            </div>
                            <span class="infra-summary-icon" aria-hidden="true">
                                @switch($card['icon'])
                                    @case('site')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 7 21M12 3l5 18M7 21h10M8 16h8M9 11h6"/></svg>
                                        @break
                                    @case('details')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                                        @break
                                    @case('amount')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M15 8H9v8h6M9 12h5"/></svg>
                                        @break
                                    @default
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19V5M4 19h16M8 15l4-5 3 2 4-6"/></svg>
                                @endswitch
                            </span>
                        </div>
                    </button>
                </div>
            @endforeach
        </div>
        <div class="alert alert-info py-2 mb-3" id="tagihan-analytics-status" role="status">Memuat ringkasan Tagihan IPAS…</div>
        <div class="row g-3 infra-detail-chart-grid d-none" id="tagihan-chart-grid">
            <div class="col-xl-6 d-flex"><div class="card h-100 w-100 infra-detail-chart-card"><div class="card-header fw-semibold">Distribusi Rincian per TP</div><div class="card-body"><div id="tagihan-tp-chart" class="infra-detail-chart" style="height:300px"></div></div></div></div>
            <div class="col-xl-6 d-flex"><div class="card h-100 w-100 infra-detail-chart-card"><div class="card-header fw-semibold">Nominal Tagihan per Jenis Kontrak</div><div class="card-body"><div id="tagihan-contract-chart" class="infra-detail-chart" style="height:300px"></div></div></div></div>
            <div class="col-12 d-flex"><div class="card h-100 w-100 infra-detail-chart-card"><div class="card-header fw-semibold">Tahun Awal Termin — 6 Tahun Terakhir</div><div class="card-body"><div id="tagihan-year-chart" class="infra-detail-chart" style="height:230px"></div></div></div></div>
        </div>
    </section>

    <div class="card" id="tagihan-site-section">
        <div class="card-body">
            <div class="alert alert-info py-2 d-none d-flex flex-wrap justify-content-between align-items-center gap-2" id="tagihan-site-focus" role="status">
                <span id="tagihan-site-focus-label"></span>
                <button type="button" class="btn btn-sm btn-outline-primary" id="tagihan-site-focus-clear">Tampilkan semua</button>
            </div>
            <p class="small text-muted mb-3">Satu baris per Site ID. Termin dan periode merangkum nomor yang tersedia; rentang tanggal mencakup seluruh kontrak. Total Tagihan adalah jumlah semua rincian, bukan nilai per termin. Klik site untuk melihat setiap siklus kontrak dan dokumen sumbernya.</p>
            <table id="tagihan-table" class="display align-middle" style="width:100%">
                <thead><tr>
                    <th>No</th><th>Site ID</th><th>Site Name</th><th>TP</th>
                    <th>Contract Type</th><th>Termin</th><th>Periode Ke</th>
                    <th>Rentang Termin</th><th>Jumlah Rincian</th><th>Total Tagihan</th><th>Batch Name</th>
                </tr></thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="tagihan-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title fs-5" id="tagihan-detail-title">Detail Tagihan IPAS</h2>
                        <div class="small text-muted" id="tagihan-detail-subtitle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3" id="tagihan-detail-fields"></div>
                </div>
            </div>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'recurring-tagihan-ipas'])
@endsection

@push('styles')
<style>
    .ipas-metric-card { cursor: pointer; background: var(--bs-body-bg); transition: box-shadow .15s ease, transform .15s ease; }
    .ipas-metric-card:hover, .ipas-metric-card:focus-visible { box-shadow: 0 .25rem .75rem rgba(0, 0, 0, .12); transform: translateY(-1px); }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/infrastructure-stage-duration.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
$(function () {
    const date = (data) => {
        if (!data) return '-';
        const d = new Date(data);
        return isNaN(d) ? data : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    };
    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        ...['site_code', 'site_name', 'tp', 'contract_type', 'termin', 'periode_ke'].map((key) => ({ data: key, name: key, render: DataTable.render.text(), defaultContent: '-' })),
        { data: 'termin_start', name: 'termin_start', render: (data, type, row) => `${date(data)} s.d. ${date(row.termin_end)}` },
        { data: 'term_count', name: 'term_count', searchable: false },
        {
            data: 'amount', name: 'amount', className: 'text-end',
            render: (data) => data == null || data === '' ? '-' : new Intl.NumberFormat('id-ID').format(data),
        },
        { data: 'batch_name', name: 'batch_name', render: DataTable.render.text(), defaultContent: '-' },
    ];
    let siteFilter = { dimension: 'all' };
    let siteSort = '';
    const table = new DataTable('#tagihan-table', {
        processing: true, serverSide: true, scrollX: true,
        ajax: {
            url: @json(route('infrastruktur.recurring-tagihan-ipas.data')),
            data: (d) => {
                d.tp = $('#filter-tp').val();
                d.dimension = siteFilter.dimension;
                d.value = siteFilter.value || '';
                d.except = siteFilter.except || [];
                d.sort = siteSort;
            },
        },
        columns, order: [], pageLength: 10,
        createdRow: (row) => {
            $(row).css('cursor', 'pointer').attr('title', 'Klik untuk melihat detail lengkap');
        },
    });

    const number = new Intl.NumberFormat('id-ID');
    const amount = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const compact = new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 2 });
    let analyticsRequest;
    let currentAnalytics;
    let charts = [];
    const showSiteRows = (filter, message, sort = '') => {
        siteFilter = filter;
        siteSort = sort;
        $('#tagihan-site-focus-label').text(message);
        $('#tagihan-site-focus').removeClass('d-none');
        table.search('').order([]).ajax.reload();
        document.getElementById('tagihan-site-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    $('[data-ipas-card]').on('click', function () {
        switch (this.dataset.ipasCard) {
            case 'sites':
                showSiteRows({ dimension: 'all' }, 'Menampilkan semua site sesuai filter TP. Klik baris untuk melihat seluruh rincian dan dokumen sumbernya.');
                break;
            case 'details':
                showSiteRows({ dimension: 'all' }, 'Site yang membentuk jumlah rincian termin. Kolom Jumlah Rincian merangkum setiap site.', 'details_desc');
                break;
            case 'amount':
                showSiteRows({ dimension: 'all' }, 'Site yang membentuk total tagihan. Tabel diurutkan menurut total seluruh rincian per site.', 'amount_desc');
                break;
            case 'details_per_site':
                showSiteRows({ dimension: 'all' },
                    `${number.format(currentAnalytics?.metrics.details || 0)} rincian ÷ ${number.format(currentAnalytics?.metrics.sites || 0)} site = ${number.format(currentAnalytics?.metrics.details_per_site || 0)} rincian per site. Kolom Jumlah Rincian menunjukkan masing-masing site.`, 'details_desc');
                break;
        }
    });
    $('#tagihan-site-focus-clear').on('click', () => {
        siteFilter = { dimension: 'all' };
        siteSort = '';
        $('#tagihan-site-focus').addClass('d-none');
        table.search('').order([]).ajax.reload();
    });

    const chartTheme = () => {
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const text = dark ? '#f1f5f9' : '#0f172a';
        const muted = dark ? '#cbd5e1' : '#64748b';
        const grid = dark ? '#334155' : '#e2e8f0';
        return {
            colors: window.SimasterChartPalette.series,
            credits: { enabled: false },
            chart: { backgroundColor: dark ? '#111827' : '#ffffff', className: 'infra-analytics-chart' },
            title: { text: null },
            legend: { itemStyle: { color: text }, itemHoverStyle: { color: text } },
            xAxis: { lineColor: grid, labels: { style: { color: muted } } },
            yAxis: { gridLineColor: grid, title: { text: null }, labels: { style: { color: muted } } },
            plotOptions: { series: { borderWidth: 0, dataLabels: { style: { color: text, textOutline: 'none' } } } },
        };
    };

    const topWithOther = (rows, limit, dimension) => {
        const points = rows.map(row => ({ name: row.label, y: Number(row.total) || 0 }))
            .sort((a, b) => b.y - a.y);
        const shown = points.slice(0, limit).map(point => ({ ...point, custom: { dimension, value: point.name } }));
        if (points.length > limit) shown.push({
            name: 'Lainnya',
            y: points.slice(limit).reduce((sum, point) => sum + point.y, 0),
            custom: { dimension: `${dimension}_other`, except: shown.map(point => point.name) },
        });
        return shown;
    };

    const yearPoints = (rows) => {
        const dated = rows.filter(row => /^\d{4}$/.test(row.label)).sort((a, b) => Number(a.label) - Number(b.label));
        const recent = dated.slice(-6);
        const older = dated.slice(0, -6);
        const points = [];
        if (older.length) points.push({
            name: `Sebelum ${recent[0].label}`,
            y: older.reduce((sum, row) => sum + Number(row.total || 0), 0),
            custom: { dimension: 'before_year', value: recent[0].label },
        });
        recent.forEach(row => points.push({ name: row.label, y: Number(row.total) || 0, custom: { dimension: 'year', value: row.label } }));
        const missing = rows.filter(row => !/^\d{4}$/.test(row.label));
        if (missing.length) points.push({
            name: 'Tanpa tanggal',
            y: missing.reduce((sum, row) => sum + Number(row.total || 0), 0),
            custom: { dimension: 'missing_year' },
        });
        return points;
    };

    const clickableSeries = prefix => ({
        plotOptions: { series: {
            cursor: 'pointer',
            point: { events: { click: function () {
                showSiteRows(this.options.custom, `${prefix}: ${this.name}. Tabel menampilkan site yang memiliki rincian sesuai pilihan; total tiap site tetap mencakup semua terminnya.`);
            } } },
        } },
    });

    const renderAnalytics = (data) => {
        currentAnalytics = data;
        $('[data-ipas-card]').prop('disabled', false);
        $('[data-ipas-metric="sites"]').text(number.format(data.metrics.sites));
        $('[data-ipas-metric="details"]').text(number.format(data.metrics.details));
        $('[data-ipas-metric="details_per_site"]').text(number.format(data.metrics.details_per_site));
        const total = Number(data.metrics.amount);
        $('[data-ipas-metric="amount"]').text(`Rp ${compact.format(total)}`)
            .attr('title', `Rp ${amount.format(total)}`);
        charts.forEach(chart => chart.destroy());
        charts = [];
        const hasDetails = data.metrics.details > 0;
        $('#tagihan-chart-grid').toggleClass('d-none', !hasDetails || !window.Highcharts);
        $('#tagihan-analytics-status').toggleClass('d-none', hasDetails && !!window.Highcharts)
            .text(hasDetails ? 'Diagram belum dapat dimuat. Coba segarkan halaman.' : 'Belum ada rincian termin untuk filter TP ini.');
        if (!hasDetails || !window.Highcharts) return;

        const providers = topWithOther(data.providers, 7, 'tp');
        const contracts = topWithOther(data.contracts, 8, 'contract');
        const years = yearPoints(data.years);
        charts.push(Highcharts.chart('tagihan-tp-chart', Highcharts.merge(chartTheme(), clickableSeries('Rincian TP'), {
            chart: { type: 'bar' },
            colors: window.SimasterChartPalette.distinctColors(providers.length),
            xAxis: { type: 'category' },
            yAxis: { min: 0, allowDecimals: false },
            legend: { enabled: false },
            plotOptions: { bar: { colorByPoint: true } },
            tooltip: { valueSuffix: ' rincian' },
            series: [{ name: 'Rincian', data: providers }],
        })));
        charts.push(Highcharts.chart('tagihan-contract-chart', Highcharts.merge(chartTheme(), clickableSeries('Jenis Kontrak'), {
            chart: { type: 'bar' },
            colors: window.SimasterChartPalette.distinctColors(contracts.length),
            xAxis: { type: 'category' },
            yAxis: { labels: { formatter: function () { return compact.format(this.value); } } },
            legend: { enabled: false },
            plotOptions: { bar: { colorByPoint: true } },
            tooltip: { valuePrefix: 'Rp ', valueDecimals: 2 },
            series: [{ name: 'Total Tagihan', data: contracts }],
        })));
        charts.push(Highcharts.chart('tagihan-year-chart', Highcharts.merge(chartTheme(), clickableSeries('Tahun Awal Termin'), {
            chart: { type: 'column' },
            colors: window.SimasterChartPalette.distinctColors(years.length),
            xAxis: { type: 'category', labels: { rotation: 0, formatter: function () {
                return this.value === 'Tanpa tanggal' ? 'Tgl kosong' : String(this.value).replace(/^Sebelum /, '<');
            } } },
            yAxis: { min: 0, allowDecimals: false },
            legend: { enabled: false },
            plotOptions: { column: { colorByPoint: true } },
            tooltip: { valueSuffix: ' rincian' },
            series: [{ name: 'Rincian', data: years }],
        })));
    };

    const loadAnalytics = () => {
        if (analyticsRequest) analyticsRequest.abort();
        currentAnalytics = null;
        $('[data-ipas-card]').prop('disabled', true);
        $('[data-ipas-metric]').text('—');
        $('#tagihan-chart-grid').addClass('d-none');
        $('#tagihan-analytics-status').removeClass('d-none').removeClass('alert-danger').addClass('alert-info')
            .text('Memuat ringkasan Tagihan IPAS…');
        analyticsRequest = $.getJSON(@json(route('infrastruktur.recurring-tagihan-ipas.analytics')), { tp: $('#filter-tp').val() })
            .done(renderAnalytics)
            .fail((xhr, status) => {
                if (status !== 'abort') $('#tagihan-analytics-status').removeClass('d-none').removeClass('alert-info')
                    .addClass('alert-danger').text('Ringkasan gagal dimuat. Coba segarkan halaman.');
            });
    };
    loadAnalytics();
    window.addEventListener('simaster:theme-changed', () => { if (currentAnalytics) renderAnalytics(currentAnalytics); });

    const labels = {
        recurring: 'Recurring', siteid: 'Site ID', site_name: 'Site Name', contract_type: 'Contract Type',
        termin: 'Termin', periode_ke: 'Periode Ke', termin_start: 'Termin Start', termin_end: 'Termin End',
        termin_amount: 'Termin Amount', status: 'Status', tp: 'TP', tp_type: 'TP Type', region: 'Region',
        region2: 'Region (2)', contract_no: 'Contract No', sap_contract_no: 'SAP Contract No',
        contract_description: 'Contract Description', infra_contract: 'Infra Contract', contract_type2: 'Contract Type (2)',
        first_contract_ref: 'First Contract Ref', sow: 'SOW', sow_detail: 'SOW Detail', pr_number: 'PR Number',
        pr_date_year: 'PR Date Year', po_number: 'PO Number', po_date_year: 'PO Date Year', pr_date: 'PR Date',
        po_date: 'PO Date', collaboration: 'Collaboration', collaboration_date: 'Collaboration Date',
        invoice_number: 'Invoice Number', invoice_date: 'Invoice Date', gr_number: 'GR Number', gr_date: 'GR Date',
        batch_name: 'Batch Name',
    };

    let detailRequest;
    const openSiteDetail = row => {
        $('#tagihan-detail-title').text(row.site_code || 'Detail Tagihan IPAS');
        $('#tagihan-detail-subtitle').text(row.term_count == null
            ? `${row.site_name || ''} • Semua rincian site ini`
            : `${row.site_name || ''} • ${row.term_count} rincian • ${date(row.termin_start)} s.d. ${date(row.termin_end)}`);
        const container = $('#tagihan-detail-fields').empty();
        if (detailRequest) detailRequest.abort();
        container.append($('<div>').addClass('col-12 text-muted').text('Memuat rincian termin…'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('tagihan-detail-modal')).show();
        const url = @json(route('infrastruktur.recurring-tagihan-ipas.show', ['site' => 0])).replace(/\/0$/, `/${row.site_id || row.id}`);
        detailRequest = $.getJSON(url).done(({ data }) => {
            container.empty();
            data.forEach((term) => {
                const details = term.source_details || {};
                const section = $('<section>').addClass('col-12 border-bottom pb-3');
                section.append($('<h3>').addClass('fs-6').text(`Termin ${term.termin || '-'} • Periode ${term.periode_ke || '-'} • ${date(term.termin_start)} s.d. ${date(term.termin_end)}`));
                section.append($('<p>').addClass('small mb-2').text([
                    term.tp, term.contract_type,
                    `Kontrak: ${details.contract_no || '-'} / Siklus: ${details.infra_contract || '-'}`,
                    `Nominal: ${term.amount == null ? '-' : new Intl.NumberFormat('id-ID').format(term.amount)}`,
                    `Batch: ${term.batch_name || '-'}`,
                ].filter(Boolean).join(' • ')));
                const source = $('<details>').append($('<summary>').addClass('small').text('Lihat detail sumber / PO / invoice'));
                const fields = $('<div>').addClass('row g-3 mt-1');
                Object.entries(details).forEach(([key, value]) => {
                    if (value === null || value === '') return;
                    const field = $('<div>').addClass('col-12 col-md-6 col-xl-4');
                    field.append($('<div>').addClass('small text-muted').text(labels[key] || key.replaceAll('_', ' ')));
                    field.append($('<div>').addClass('text-break').text(String(infrastructureStageDuration.formatSourceDate(key, value))));
                    fields.append(field);
                });
                section.append(source.append(fields));
                container.append(section);
            });
            if (!data.length) container.append($('<div>').addClass('col-12 text-muted').text('Belum ada rincian termin.'));
        }).fail((xhr, status) => {
            if (status !== 'abort') container.empty().append($('<div>').addClass('col-12 text-danger').text('Rincian gagal dimuat. Tutup dan buka kembali site untuk mencoba lagi.'));
        });
    };
    $('#tagihan-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (row) openSiteDetail(row);
    });
    $('#filter-tp').on('change', () => {
        siteFilter = { dimension: 'all' };
        siteSort = '';
        $('#tagihan-site-focus').addClass('d-none');
        table.search('').order([]).ajax.reload();
        loadAnalytics();
    });
    $('#page-size').on('change', function () { table.page.len(parseInt($(this).val())).draw(); });
});
</script>
@endpush
