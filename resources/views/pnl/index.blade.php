@extends('layouts.app')

@section('title', 'Profit & Loss — SIMAWAR')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @if ($errors->has('pnl_file'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $errors->first('pnl_file') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3 page-hero">
        <div>
            <h1 class="h4 mb-0">Profit & Loss (P & L) Data</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'pnl'])
            <a href="{{ route('pnl.upload') }}" class="btn btn-outline-brand btn-sm">Upload Excel</a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Profit &amp; Loss">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            {{-- Page Size --}}
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Periode --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-year" class="form-label mb-0 small fw-semibold text-nowrap">Pilih Tahun:</label>
                <select id="filter-year" class="form-select form-select-sm" style="min-width: 105px;"></select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="filter-month" class="form-label mb-0 small fw-semibold text-nowrap">Pilih Bulan:</label>
                <select id="filter-month" class="form-select form-select-sm" style="min-width: 135px;"></select>
            </div>

            {{-- Filter NOP --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-nop" class="form-label mb-0 small fw-semibold text-nowrap">NOP:</label>
                <select id="filter-nop" class="form-select form-select-sm" style="min-width: 170px;">
                    <option value="">Semua NOP</option>
                    <option value="__unmapped__" @selected($selectedNop === '__unmapped__')>Belum dipetakan</option>
                    @foreach ($nops as $nop)
                        <option value="{{ $nop }}" @selected($nop === $selectedNop)>{{ $nop }}</option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label for="filter-city" class="form-label mb-0 small fw-semibold">Kota:</label>
                <select id="filter-city" class="form-select form-select-sm">
                    <option value="">Semua Kota</option>
                    <option value="__unmapped__" @selected($selectedCity === '__unmapped__')>Belum dipetakan</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}" @selected(strcasecmp($city, $selectedCity) === 0)>{{ $city }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Status (Semua / Profit / Loss / Tidak Aktif) --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-status" class="form-label mb-0 small fw-semibold text-nowrap">Status:</label>
                <select id="filter-status" class="form-select form-select-sm" style="min-width: 140px;">
                    <option value="" @selected(empty($selectedStatus))>Semua Status</option>
                    <option value="Profit" @selected($selectedStatus === 'Profit')>Profit</option>
                    <option value="Loss" @selected($selectedStatus === 'Loss')>Loss</option>
                    <option value="TidakAktif" @selected($selectedStatus === 'TidakAktif' || $selectedStatus === 'Tidak Aktif')>Tidak Aktif</option>
                </select>
            </div>

            {{-- Download Excel --}}
            <a href="{{ route('pnl.export-excel') }}" id="btn-export" class="btn btn-sm btn-outline-brand ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3" id="pnl-financial-summary" aria-live="polite">
        @foreach (['revenue' => 'Total Revenue', 'cost' => 'Total Cost', 'profit_loss' => 'Net Profit / Loss', 'margin' => 'Margin P&L'] as $key => $label)
            <div class="col-xl-3 col-6"><div class="card h-100"><div class="card-body">
                <div class="small text-body-secondary mb-2">{{ $label }}</div>
                <div class="pnl-summary-value" id="pnl-summary-{{ $key }}">—</div>
            </div></div></div>
        @endforeach
    </div>
    @php($pnlTabs = ['site' => 'Site Performance', 'nop' => 'NOP Performance', 'monthly' => 'Tren Bulanan', 'nop-pnl' => 'Profit/Loss per NOP', 'city' => 'Profit/Loss per City', 'analysis' => 'Analisis Site'])
    <div class="pnl-tabs mb-3" role="tablist" aria-label="Bagian Profit &amp; Loss">
        @foreach ($pnlTabs as $key => $label)
            <button type="button" id="pnl-tab-{{ $key }}" class="pnl-tab {{ $loop->first ? 'active' : '' }}" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="pnl-panel-{{ $key }}" tabindex="{{ $loop->first ? '0' : '-1' }}" data-pnl-tab="{{ $key }}">{{ $label }}</button>
        @endforeach
    </div>
    <p class="small text-body-secondary mb-3" id="pnl-chart-context" role="status">Memuat diagram sesuai periode dan wilayah…</p>
    <div id="pnl-analytics-error" class="alert alert-danger d-none" role="alert"></div>
    <section id="pnl-panel-site" data-pnl-panel="site" role="tabpanel" aria-labelledby="pnl-tab-site">
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h6 mb-0">Performa Site per NOP</h2>
                </div>
                <div class="card-body">
                    <div id="pnl-site-status-chart" class="pnl-chart" aria-label="Jumlah site menurut NOP dan status"></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h2 class="h6 mb-0">Komposisi Status Site</h2>
                </div>
                <div class="card-body">
                    <div id="pnl-category-chart" style="height: 270px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div><h2 class="h5 mb-1">Performa Site</h2><p class="small text-body-secondary mb-0" id="pnl-ranking-caption">Memuat ringkasan periode dan NOP…</p></div>
    </div>
    <div class="row g-3 mb-4">
        @foreach (['profit' => 'Top 3 Site Profit', 'loss' => 'Top 3 Site Loss'] as $type => $title)
            <div class="col-xl-6">
                <section class="card pnl-ranking-panel h-100 pnl-ranking-{{ $type }}" aria-labelledby="pnl-ranking-title-{{ $type }}">
                    <div class="card-header d-flex align-items-center gap-2">
                        <span class="pnl-ranking-symbol" aria-hidden="true">{{ $type === 'profit' ? '↗' : '↘' }}</span>
                        <div><h3 class="h6 mb-1" id="pnl-ranking-title-{{ $type }}">{{ $title }}</h3><p class="small text-body-secondary mb-0">{{ $type === 'profit' ? 'Laba tertinggi' : 'Kerugian terbesar' }} berdasarkan net P&L</p></div>
                    </div>
                    <div id="pnl-ranking-{{ $type }}" class="pnl-ranking-list" aria-live="polite" aria-busy="true" aria-controls="pnl-table"><p class="text-body-secondary p-3 mb-0">Memuat peringkat site…</p></div>
                </section>
            </div>
        @endforeach
    </div>

    </section>
    @include('pnl.partials.analytics-panels')
    <section id="pnl-panel-analysis" data-pnl-panel="analysis" role="tabpanel" aria-labelledby="pnl-tab-analysis" hidden>
    <div class="card mb-3">
        <div class="card-header"><h2 class="h6 mb-1">Revenue dan Cost per Site</h2><p class="small text-body-secondary mb-0">10 site dengan nilai absolut P&amp;L terbesar pada filter aktif.</p></div>
        <div class="card-body"><div id="pnl-site-comparison-chart" class="pnl-chart" aria-label="Perbandingan revenue dan cost site"></div></div>
    </div>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div><h2 class="h5 mb-1">Data Site</h2><p id="pnl-data-context" class="small text-body-secondary mb-0">Data mengikuti filter periode dan wilayah di atas.</p></div>
        <button type="button" id="pnl-clear-drilldown" class="btn btn-sm btn-outline-secondary">Reset pilihan diagram</button>
    </div>
    <div class="card" id="pnl-table-card" tabindex="-1">
        <div class="card-body">
            <table id="pnl-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>NOP</th>
                        <th>Kota</th>
                        <th>Revenue</th>
                        <th>Cost</th>
                        <th>Profit / Loss</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
    </section>

    {{-- Modal Detail Site & Histori Bulanan --}}
    <div class="modal fade" id="siteDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Rincian Histori Site — <span id="modal-site-id" class="fw-bold"></span>
                        <span id="modal-site-name" class="text-body-secondary fw-normal fs-6 ms-1"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="modal-site-body">
                    <div class="text-center text-body-secondary py-4">Memuat histori data...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="metricDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="metric-detail-title">Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody id="metric-detail-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .pnl-tabs { display: flex; flex-wrap: wrap; gap: .65rem; }
    .simaster-report .pnl-tab { border: 1px solid var(--bs-border-color); border-radius: .65rem; padding: .55rem .75rem; color: var(--bs-body-color); background: var(--bs-body-bg); font: inherit; font-size: .875rem; font-weight: 600; cursor: pointer; transition: background .2s, box-shadow .2s; }
    .simaster-report .pnl-tab:hover { background: var(--bs-tertiary-bg); }
    .simaster-report .pnl-tab.active { color: #fff; background: linear-gradient(120deg, #0d9488, #0f766e); border-color: #0f766e; box-shadow: 0 4px 12px rgb(13 148 136 / .16); }
    .simaster-report .pnl-tab:focus-visible { outline: 3px solid #38bdf8; outline-offset: 2px; }
    [data-pnl-panel][hidden] { display: none !important; }
    .pnl-chart { height: 320px; min-width: 0; width: 100%; }
    .pnl-chart-scroll { max-height: 440px; overflow-y: auto; overscroll-behavior: contain; }
    .pnl-summary-compact { display: none; }
    #pnl-financial-summary .card { container: pnl-summary / inline-size; }
    #pnl-financial-summary .card-body { padding: 1rem; }
    @container pnl-summary (max-width: 16rem) {
        .pnl-summary-full { display: none; }
        .pnl-summary-compact { display: inline; }
    }
    .pnl-analytics-table { max-height: 370px; overflow: auto; }
    .pnl-analytics-table button { text-align: left; }
    @media (max-width: 575px) { .simaster-report .pnl-tab { flex: 1 1 42%; padding: .65rem .5rem; font-size: .78rem; } .pnl-chart { height: 290px; } }
    @media (max-width: 575px) { .pnl-summary-full { display: none; } .pnl-summary-compact { display: inline; } }
    @media (prefers-reduced-motion: reduce) { .simaster-report .pnl-tab { transition: none; } }
    #pnl-table tbody tr { cursor: pointer; }
    .pnl-highlight {
        border-radius: 12px;
        background: var(--bs-body-bg);
        color: inherit;
        cursor: pointer;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .pnl-highlight:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .12); }
    .pnl-highlight:focus-visible { outline: 3px solid rgba(59, 130, 246, .35); outline-offset: 2px; }
    .pnl-highlight:disabled { cursor: default; opacity: .72; }
    .pnl-highlight.is-selected { box-shadow: 0 0 0 2px rgba(59, 130, 246, .45); }
    .pnl-highlight-profit { border-left: 4px solid #14b8a6; }
    .pnl-highlight-loss { border-left: 4px solid #ef7357; }
    .pnl-highlight-value { font-size: clamp(1.35rem, 2vw, 1.8rem); font-weight: 700; line-height: 1.2; }
    .pnl-summary-value { font-size: clamp(1rem, 1.5vw, 1.45rem); font-weight: 750; font-variant-numeric: tabular-nums; overflow-wrap: anywhere; }
    .pnl-ranking-panel { overflow: hidden; }
    .pnl-ranking-symbol { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 12px; font-size: 1.5rem; font-weight: 700; background: rgba(20,184,166,.12); color: #0d9488; }
    .pnl-ranking-loss .pnl-ranking-symbol { background: rgba(239,115,87,.12); color: #dc6247; }
    .pnl-rank-row { position: relative; padding: 1rem; border-top: 1px solid var(--bs-border-color); }
    .pnl-rank-row:first-child { border-top: 0; }
    .pnl-rank-row > .pnl-highlight { display: grid; grid-template-columns: 28px minmax(0,1fr) auto; align-items: start; gap: .65rem; padding: 0; width: 100%; border: 0; background: transparent; color: inherit; text-align: left; }
    .pnl-rank-number { width: 27px; height: 27px; display: grid; place-items: center; border-radius: 9px; background: var(--bs-tertiary-bg); font-weight: 600; color: var(--bs-secondary-color); }
    .pnl-rank-amount { text-align: right; font-weight: 750; font-variant-numeric: tabular-nums; }
    .pnl-rank-metrics { display: flex; flex-wrap: wrap; align-items: end; gap: .55rem 1.25rem; margin-top: .7rem; padding-left: 2.4rem; font-size: .78rem; }
    .pnl-rank-metrics span { display: block; color: var(--bs-secondary-color); margin-bottom: .15rem; }
    .pnl-rank-detail { margin-left: auto; }
    @media(max-width:575px) { .pnl-rank-row > .pnl-highlight { grid-template-columns: 26px minmax(0,1fr); } .pnl-rank-amount { grid-column: 2; text-align: left; } .pnl-rank-metrics { gap: .6rem; padding-left: 0; } }
    #pnl-table tbody tr.pnl-highlighted-row > * { background-color: rgba(59, 130, 246, .14); }
</style>
@endpush

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
$(function () {
    const dataUrl = @json(route('pnl.data'));
    const highlightApiUrl = @json(route('pnl.highlights'));
    const chartApiUrl = @json(route('pnl.analytics'));
    const historyBaseUrl = @json(url('pnl/site-history'));
    const exportBaseUrl = @json(route('pnl.export-excel'));
    const availablePeriods = @json($periods);
    const initialPeriod = @json($selectedPeriod).split('-').map(Number);
    const initialAllMonths = @json($selectedAllMonths ?? false);
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const palette = window.SimasterChartPalette;
    let chartRequest = null;
    let highlightRequest = null;
    let highlightSiteFilterActive = false;
    let selectedSiteCode = '';
    const fmtNumber = (value, decimals = 0) => Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

    function populateMonths(tahun, selectedBulan) {
        const months = availablePeriods
            .filter((period) => Number(period.tahun) === Number(tahun))
            .map((period) => Number(period.bulan))
            .sort((a, b) => a - b);
        const $month = $('#filter-month').empty();
        // Tambahkan pilihan Semua Bulan
        $month.append($('<option>').val('all').text('Semua Bulan'));
        months.forEach((bulan) => $month.append($('<option>').val(bulan).text(monthNames[bulan - 1])));
        // Pre-select: 'all' jika selectedBulan === 0 atau 'all', else nilai bulan
        if (!months.length || selectedBulan === 0 || selectedBulan === 'all' || selectedBulan === null) {
            $month.val('all');
        } else {
            $month.val(months.includes(Number(selectedBulan)) ? Number(selectedBulan) : months[months.length - 1]);
        }
    }

    function initPeriodFilters() {
        const years = [...new Set(availablePeriods.map((period) => Number(period.tahun)))].sort((a, b) => b - a);
        if (!years.length) years.push(initialPeriod[1]);
        const $year = $('#filter-year').empty();
        years.forEach((tahun) => $year.append($('<option>').val(tahun).text(tahun)));
        $year.val(initialPeriod[1]);
        // initialPeriod[0] === 0 berarti semua bulan
        populateMonths(initialPeriod[1], initialAllMonths ? 0 : initialPeriod[0]);
        $('#filter-year, #filter-month').trigger('simaster:period-sync');
    }

    initPeriodFilters();

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'nop', name: 'nop', defaultContent: '-' },
        { data: 'city', name: 'city', defaultContent: '-' },
        { data: 'revenue', name: 'revenue', className: 'text-end' },
        { data: 'cost', name: 'cost', className: 'text-end' },
        { data: 'profit_loss', name: 'profit_loss', className: 'text-end' },
        { data: 'status_badge', name: 'status_badge', className: 'text-center' },
    ];

    const table = new DataTable('#pnl-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: dataUrl,
            data: function (d) {
                const bulanVal = $('#filter-month').val();
                // Kirim 'all' jika Semua Bulan dipilih, else kirim nilai bulan
                d.bulan = (bulanVal === 'all') ? 'all' : bulanVal;
                d.tahun = $('#filter-year').val();
                d.nop = $('#filter-nop').val();
                d.status = $('#filter-status').val();
                d.city = $('#filter-city').val();
                d.site = selectedSiteCode;
            }
        },
        columns: columns,
        order: [],
        pageLength: 10,
    });

    let pnlModalChart = null;

    function updateExportUrl() {
        const status = $('#filter-status').val();
        const nop = $('#filter-nop').val();
        const bulanVal = $('#filter-month').val();

        const params = new URLSearchParams();
        if (bulanVal && bulanVal !== 'all') params.append('bulan', bulanVal);
        else if (bulanVal === 'all') params.append('bulan', 'all');
        params.append('tahun', $('#filter-year').val());
        if (nop) params.append('nop', nop);
        if (status) params.append('status', status);
        if ($('#filter-city').val()) params.append('city', $('#filter-city').val());
        if (selectedSiteCode) params.append('site', selectedSiteCode);

        $('#btn-export').attr('href', exportBaseUrl + '?' + params.toString());
        updatePnlDataContext();
    }

    updateExportUrl();

    function getChartFilters() {
        const bulan = $('#filter-month').val();

        return {
            tahun: $('#filter-year').val(),
            bulan: bulan === 'all' ? 'all' : bulan,
            nop: $('#filter-nop').val(),
            city: $('#filter-city').val()
        };
    }

    function renderRanking(type, sites) {
        const $list = $(`#pnl-ranking-${type}`).empty().attr('aria-busy', 'false');
        if (!sites.length) {
            $('<p class="p-3 mb-0 text-body-secondary">').text(`Tidak ada site ${type} untuk filter ini.`).appendTo($list);
            return;
        }
        sites.forEach((site, index) => {
            const $row = $('<div class="pnl-rank-row">').appendTo($list);
            const $button = $('<button type="button" class="pnl-highlight" aria-controls="pnl-table" aria-pressed="false">').attr('data-site-id', site.site_id).appendTo($row);
            $('<span class="pnl-rank-number">').text(index + 1).appendTo($button);
            const $name = $('<span class="min-w-0">').appendTo($button);
            $('<span class="d-block fw-semibold">').text(site.site_name || site.site_id).appendTo($name);
            $('<span class="d-block small text-body-secondary mt-1">').text(`${site.site_id}${site.nop ? ` · ${site.nop}` : ''}`).appendTo($name);
            $('<span class="pnl-rank-amount">').addClass(type === 'profit' ? 'text-profit' : 'text-loss').text(fmtRupiah(site.profit_loss)).appendTo($button);
            const $metrics = $('<div class="pnl-rank-metrics">').appendTo($row);
            [['Revenue', fmtRupiah(site.revenue)], ['Cost', fmtRupiah(site.cost)], ['Margin', site.margin === null ? '—' : `${fmtNumber(site.margin, 2)}%`]].forEach(([label, value]) => {
                const $metric = $('<div>').appendTo($metrics);
                $('<span>').text(label).appendTo($metric);
                $('<strong>').text(value).appendTo($metric);
            });
            $('<button type="button" class="btn btn-sm btn-outline-secondary pnl-rank-detail">').text('Rincian').on('click', () => openSiteHistory(site)).appendTo($metrics);
        });
    }

    function loadPnlHighlights() {
        if (highlightRequest) highlightRequest.abort();
        $('.pnl-ranking-list').attr('aria-busy', 'true').html('<p class="p-3 mb-0 text-body-secondary">Memuat peringkat site…</p>');
        $('#pnl-financial-summary .pnl-summary-value').text('—');

        let request;
        request = $.ajax({
            url: highlightApiUrl,
            method: 'GET',
            data: getChartFilters(),
            success: function (res) {
                renderRanking('profit', res.top_profit || []);
                renderRanking('loss', res.top_loss || []);
                const summary = res.summary || {};
                ['revenue', 'cost', 'profit_loss'].forEach(key => {
                    const value = Number(summary[key] || 0);
                    const full = fmtRupiah(value);
                    const $value = $('#pnl-summary-' + key).empty().attr('title', full);
                    $('<span class="visually-hidden">').text(full).appendTo($value);
                    $('<span class="pnl-summary-full" aria-hidden="true">').text(full).appendTo($value);
                    $('<span class="pnl-summary-compact" aria-hidden="true">').text('Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact', maximumFractionDigits: 2 }).format(value)).appendTo($value);
                });
                $('#pnl-summary-profit_loss').toggleClass('text-profit', summary.profit_loss > 0).toggleClass('text-loss', summary.profit_loss < 0);
                $('#pnl-summary-margin').text(summary.margin === null ? '—' : `${fmtNumber(summary.margin, 2)}%`);
                $('#pnl-ranking-caption').text(`${summary.sites_count || 0} site dengan data finansial · ${$('#filter-nop').val() || 'Semua NOP'} · mengikuti periode terpilih`);
            },
            error: function (_xhr, status) {
                if (status === 'abort') return;
                $('.pnl-ranking-list').attr('aria-busy', 'false').html('<p class="p-3 mb-0 text-danger">Gagal memuat peringkat. Ubah filter untuk mencoba kembali.</p>');
                $('#pnl-ranking-caption').text('Ringkasan belum tersedia.');
            },
            complete: function () {
                if (highlightRequest === request) highlightRequest = null;
            }
        });
        highlightRequest = request;
    }

    @include('pnl.partials.analytics-script')

    function clearHighlightSiteFilter() {
        selectedSiteCode = '';
        if (!highlightSiteFilterActive) return;
        table.column(1).search('');
        highlightSiteFilterActive = false;
        $('.pnl-highlight').removeClass('is-selected').attr('aria-pressed', 'false');
    }

    function showHighlightInTable($card) {
        const siteId = $card.attr('data-site-id');
        if (!siteId || $card.prop('disabled')) return;

        // Kartu menunjuk satu Site ID. Status dikembalikan ke semua status agar
        // hanya filter kolom Site ID yang menentukan baris hasilnya.
        $('#filter-status').val('');
        $('.pnl-highlight').removeClass('is-selected').attr('aria-pressed', 'false');
        $card.addClass('is-selected').attr('aria-pressed', 'true');
        highlightSiteFilterActive = true;
        selectedSiteCode = siteId;
        updateExportUrl();
        activatePnlTab('analysis');

        $('#pnl-table').one('draw.dt.pnlHighlight', function () {
            const matchingRow = $(table.rows({ page: 'current' }).nodes().toArray()).filter(function () {
                return table.row(this).data()?.site_id === siteId;
            }).first();
            matchingRow.addClass('pnl-highlighted-row');
            window.setTimeout(() => matchingRow.removeClass('pnl-highlighted-row'), 1800);
            document.getElementById('pnl-table-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Bersihkan pencarian lain lalu terapkan pencarian hanya pada kolom
        // Site ID. Filter periode dan NOP tetap dikirim melalui AJAX tabel.
        table.search('');
        table.columns().search('');
        table.column(1).search(siteId);
        table.page('first').draw();
    }

    $(document).on('click', '.pnl-highlight', function () {
        showHighlightInTable($(this));
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    $('#filter-year').on('change', function () {
        // Saat ganti tahun, pertahankan pilihan Semua Bulan jika sebelumnya Semua Bulan
        const prevBulan = $('#filter-month').val();
        populateMonths(this.value, prevBulan === 'all' ? 0 : null);
        clearHighlightSiteFilter();
        updateExportUrl();
        table.draw();
        loadPnlCharts();
        loadPnlHighlights();
    });

    $('#filter-month, #filter-nop, #filter-city').on('change', function () {
        clearHighlightSiteFilter();
        updateExportUrl();
        table.draw();
        loadPnlCharts();
        loadPnlHighlights();
    });

    $('#filter-status').on('change', function () {
        clearHighlightSiteFilter();
        updateExportUrl();
        table.draw();
    });

    // Helper formatting
    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }
    function fmtRupiah(val) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
    }

    const modal = new bootstrap.Modal('#siteDetailModal');

    // Row Click Popup Modal
    $('#pnl-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row || !row.id) return;
        openSiteHistory(row);
    });

    function openSiteHistory(row) {
        $('#modal-site-id').text(row.site_id);
        $('#modal-site-name').text('(' + (row.site_name || '-') + ') — NOP: ' + (row.nop || '-'));
        $('#modal-site-body').html('<div class="text-center text-body-secondary py-4">Memuat data histori & grafik...</div>');
        modal.show();

        $.ajax({
            url: historyBaseUrl + '/' + row.id,
            method: 'GET',
            success: function (res) {
                renderSiteModalContent(res);
            },
            error: function () {
                $('#modal-site-body').html('<div class="alert alert-danger mb-0">Gagal memuat rincian histori site.</div>');
            }
        });
    }

    $('#siteDetailModal').on('shown.bs.modal', function () {
        if (pnlModalChart) {
            pnlModalChart.reflow();
        }
    });

    function renderSiteModalContent(res) {
        const t = res.totals || {};
        const history = res.history || [];

        const categories = [];
        const revData = [];
        const costData = [];
        const pnlData = [];

        let rowsHtml = '';
        history.forEach(function (h, index) {
            const isProfit = h.profit_loss > 0;
            const isAnomaly = Boolean(h.is_anomaly);
            const pnlClass = isAnomaly ? 'text-secondary' : (isProfit ? 'text-profit' : 'text-loss');
            const badgeClass = isAnomaly ? 'badge-anomaly' : (isProfit ? 'badge-profit' : 'badge-loss');
            const statusLabel = isAnomaly ? 'Anomali' : h.status;

            categories.push(h.bulan_label);
            revData.push(parseFloat(h.revenue) || 0);
            costData.push(parseFloat(h.cost) || 0);
            pnlData.push(parseFloat(h.profit_loss) || 0);

            rowsHtml += `
            <tr class="${isAnomaly ? 'table-warning' : ''}">
                <td>${esc(h.bulan_label)}</td>
                <td class="text-end"><button type="button" class="btn btn-link btn-sm p-0 metric-detail-button" data-detail-type="revenue" data-detail-index="${index}" title="Lihat detail Revenue">${fmtRupiah(h.revenue)} <span aria-hidden="true">+</span></button></td>
                <td class="text-end"><button type="button" class="btn btn-link btn-sm p-0 metric-detail-button" data-detail-type="cost" data-detail-index="${index}" title="Lihat detail Cost">${fmtRupiah(h.cost)} <span aria-hidden="true">+</span></button></td>
                <td class="text-end ${pnlClass}">${fmtRupiah(h.profit_loss)}</td>
                <td class="text-center">
                    <span class="badge ${badgeClass}">${esc(statusLabel)}</span>
                </td>
            </tr>`;
        });

        const html = `
        ${history.some(h => h.is_anomaly) ? '<div class="alert alert-warning py-2 small">Baris bertanda <b>Anomali</b> ditampilkan sebagai referensi dan tidak termasuk dalam total finansial.</div>' : ''}
        <div class="row g-2 mb-3 text-center">
            <div class="col-md-4">
                <div class="p-2 border rounded stat-card">
                    <span class="text-secondary small">Total Revenue</span>
                    <h6 class="mb-0 fw-bold mt-1">${fmtRupiah(t.total_revenue)}</h6>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 border rounded stat-card">
                    <span class="text-secondary small">Total Cost</span>
                    <h6 class="mb-0 fw-bold mt-1">${fmtRupiah(t.total_cost)}</h6>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2 border rounded stat-card">
                    <span class="text-secondary small">Total Net PnL</span>
                    <h6 class="mb-0 fw-bold ${t.total_pnl > 0 ? 'text-profit' : 'text-loss'} mt-1">${fmtRupiah(t.total_pnl)}</h6>
                </div>
            </div>
        </div>

        {{-- Grafik Diagram Batang Histori --}}
        <div class="card border mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center">
                <span class="small fw-semibold">Grafik Diagram Batang: Perbandingan Revenue vs Cost vs Net PnL per Bulan</span>
            </div>
            <div class="card-body p-2">
                <div id="modal-pnl-chart" style="height: 270px; width: 100%;"></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Bulan</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Profit / Loss</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${rowsHtml || '<tr><td colspan="5" class="text-center text-muted py-3">Tidak ada data histori bulanan</td></tr>'}
                </tbody>
            </table>
        </div>`;

        $('#modal-site-body').html(html);

        $('#siteDetailModal').off('click.metricDetail', '.metric-detail-button').on('click.metricDetail', '.metric-detail-button', function () {
            const historyRow = history[Number($(this).data('detail-index'))];
            const type = $(this).data('detail-type');
            const details = type === 'cost' ? historyRow.cost_details : historyRow.revenue_details;
            const title = type === 'cost' ? 'Cost Details' : 'Revenue Details';
            const rows = Object.entries(details || {}).map(([label, value]) =>
                `<tr><th>${esc(label)}</th><td class="text-end">${value === null ? '-' : fmtRupiah(value)}</td></tr>`
            ).join('');

            $('#metric-detail-title').text(`${historyRow.bulan_label} ${title}`);
            $('#metric-detail-body').html(rows || '<tr><td class="text-center">-</td></tr>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('metricDetailModal')).show();
        });

        // Render Highcharts Bar / Column Chart
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#94A3B8' : '#64748B';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

        if (categories.length > 0) {
            pnlModalChart = Highcharts.chart('modal-pnl-chart', {
                chart: {
                    type: 'column',
                    backgroundColor: 'transparent',
                    animation: true
                },
                title: { text: null },
                credits: { enabled: false },
                xAxis: {
                    categories: categories,
                    labels: { style: { color: textColor, fontSize: '11px' } },
                    lineColor: gridColor,
                    tickColor: gridColor
                },
                yAxis: {
                    title: { text: null },
                    gridLineColor: gridColor,
                    labels: {
                        formatter: function () {
                            const val = this.value;
                            if (Math.abs(val) >= 1e9) return (val / 1e9).toFixed(1) + 'M';
                            if (Math.abs(val) >= 1e6) return (val / 1e6).toFixed(0) + 'jt';
                            return val;
                        },
                        style: { color: textColor, fontSize: '11px' }
                    }
                },
                legend: {
                    itemStyle: { color: isDark ? '#E2E8F0' : '#334155', fontSize: '11px' }
                },
                tooltip: {
                    shared: true,
                    backgroundColor: isDark ? '#1E293B' : '#FFFFFF',
                    borderColor: isDark ? '#334155' : '#CBD5E1',
                    style: { color: isDark ? '#F8FAFC' : '#1E293B' },
                    formatter: function () {
                        let s = `<strong>${this.x}</strong><br/>`;
                        this.points.forEach(point => {
                            const color = point.color;
                            const percentage = window.SimasterChartMetrics.format(window.SimasterChartMetrics.highcharts(point));
                            s += `<span style="color:${color}">\u25CF</span> ${point.series.name}: <b>${fmtRupiah(point.y)}</b> ` +
                                `(<b>${percentage}</b> dari total ${point.series.name})<br/>`;
                        });
                        return s;
                    }
                },
                plotOptions: {
                    column: {
                        borderRadius: 3,
                        pointPadding: 0.1,
                        groupPadding: 0.15,
                        borderWidth: 0
                    }
                },
                series: [
                    { name: 'Revenue', data: revData, color: palette.financial.revenue },
                    { name: 'Cost', data: costData, color: palette.financial.cost },
                    { name: 'Net PnL', data: pnlData, color: palette.financial.profit, negativeColor: palette.financial.loss }
                ]
            });
        }
    }
});
</script>
@endpush
