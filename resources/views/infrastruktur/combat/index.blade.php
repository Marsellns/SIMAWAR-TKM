@extends('layouts.app')

@section('title', 'Combat — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — Combat</h1>
        @include('infrastruktur.partials.entry-actions', ['dataset' => 'combat'])
    </div>
    @include('infrastruktur.partials.scope-filter', ['scope' => 'combat'])

    @include('infrastruktur.partials.analytics', ['scope' => 'combat'])

    {{-- Container Data Tabel Infrastruktur --}}
    <div id="infrastructure-data" class="infrastructure-table-section mt-4 {{ request()->filled('filter_field') || (request()->filled('tahun') && request('tahun') !== 'all') || in_array(strtoupper(trim((string) request('available_on_jarvis'))), ['YES', 'NO', '-'], true) || request()->boolean('unique_sites') ? '' : 'd-none' }}">
        {{-- Banner Filter Aktif dari Diagram / Kartu --}}
        <div id="active-filter-alert" class="alert alert-info py-2 px-3 d-flex align-items-center justify-content-between mb-3 d-none">
            <div class="d-flex align-items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
                <span>Filter aktif dari diagram/ringkasan: <strong id="active-filter-text"></strong></span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" id="btn-clear-filter">
                Reset Filter
            </button>
        </div>

        {{-- Toolbar --}}
        <div class="card mb-3">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                    <select id="page-size" class="form-select form-select-sm" style="width:auto">
                        @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                            <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('infrastruktur.combat.export-excel') }}" id="btn-export" class="btn btn-sm btn-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                    Excel
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="combat-table" class="display align-middle text-nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            @role('admin') <th>Aksi</th> @endrole
                            <th>Site ID</th>
                            <th>Site Name</th>
                            <th>Tahun Justi Dirnet</th>
                            <th>Renewal Pattern</th>
                            <th>Match NBAE</th>
                            <th>Available on Jarvis</th>
                            <th>Type</th>
                            <th>Combat Type</th>
                            <th>Status Site</th>
                            <th>Status Combat</th>
                            <th>Status Dokumen</th>
                            <th>Status Perpanjangan</th>
                            <th>NOP</th>
                            <th>Region</th>
                            <th>Kabupaten</th>
                            <th>TP</th>
                            <th>Periode Awal Baru</th>
                            <th>Periode Akhir Baru</th>
                            <th>Masa Sewa</th>
                            <th>Status Masa Sewa</th>
                            <th>PKS Status</th>
                            <th>No PKS Baru</th>
                            <th>Total Harga Baru</th>
                            <th>Aging Days</th>
                            <th>Tindak Lanjut</th>
                            <th>Prioritas</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Detail tetap berada pada modal yang sama agar alur tabel dan filter tidak berubah. --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detail-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable combat-detail-dialog">
            <div class="modal-content">
                <div class="modal-header combat-detail-topbar">
                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-body-secondary p-0" data-bs-dismiss="modal" aria-label="Kembali ke daftar site">← Kembali ke daftar site</button>
                    <h2 class="modal-title visually-hidden" id="detail-title">Detail Combat</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body">
                    <div class="text-center text-body-secondary py-5">Memuat detail Combat...</div>
                </div>
            </div>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'combat'])
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/infrastructure-site-map.css') }}">
<style>
    #combat-table tbody tr { cursor: pointer; }
    #detailModal .combat-detail-dialog { width: min(1180px, calc(100vw - 2rem)); max-width: none; }
    #detailModal .modal-content { background: var(--bs-tertiary-bg); border: 0; border-radius: 16px; overflow: hidden; }
    #detailModal .combat-detail-topbar { padding: .8rem 1.25rem; background: var(--bs-body-bg); border-color: var(--bs-border-color); }
    #detailModal .combat-detail-topbar { font-size: .9rem; }
    #detailModal .combat-detail-topbar h2 { font-size: 1rem; font-weight: 600; color: var(--bs-secondary-color); }
    #detailModal .modal-body { padding: 1.2rem; }
    .combat-detail { color: var(--bs-body-color); font-size: .95rem; line-height: 1.5; }
    .combat-detail-hero { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; padding: .2rem .1rem 1.2rem; }
    .combat-detail-hero.infrastructure-site-hero { padding: 1.2rem 1.35rem; margin-bottom: 1.2rem; }
    .combat-detail-identity { display: flex; align-items: center; gap: 1rem; min-width: 0; }
    .combat-detail-logo { width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: #fff1f2; color: #e11d48; display: grid; place-items: center; flex: none; }
    .combat-detail-logo .combat-detail-icon { width: 2.25rem; height: 2.25rem; background: transparent; color: inherit; }
    .combat-detail-logo svg { width: 2rem; height: 2rem; }
    .combat-detail-eyebrow { display: inline-block; font-size: .78rem; font-weight: 700; letter-spacing: .05em; color: #be123c; background: #ffe4e6; border-radius: .25rem; padding: .2rem .45rem; }
    .combat-detail-name { margin: .3rem 0; font-size: clamp(1.35rem, 2.2vw, 1.85rem); font-weight: 800; line-height: 1.25; overflow-wrap: anywhere; }
    .combat-detail-meta, .combat-detail-badges { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem .8rem; }
    .combat-detail-meta { color: var(--bs-secondary-color); font-size: .88rem; }
    .combat-detail-meta > span { display: inline-flex; align-items: center; gap: .25rem; }
    .combat-detail-meta .combat-detail-icon { width: 1rem; height: 1rem; background: transparent; }
    .combat-detail-meta .combat-detail-icon svg { width: .85rem; height: .85rem; }
    .combat-detail-badges { justify-content: flex-end; }
    .combat-detail-badge { display: inline-flex; align-items: center; gap: .3rem; border-radius: .35rem; padding: .35rem .6rem; background: var(--bs-secondary-bg); color: var(--bs-secondary-color); font-size: .84rem; font-weight: 650; }
    .combat-detail-badge.is-danger { background: #fff1f2; color: #e11d48; }
    .combat-detail-badge.is-warning { background: #fff7ed; color: #b45309; }
    .combat-detail-badge.is-success { background: #ecfdf5; color: #047857; }
    .combat-detail-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; margin-bottom: 1.2rem; }
    .combat-detail-stat, .combat-detail-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: .75rem; min-width: 0; box-shadow: 0 2px 7px rgba(15, 23, 42, .035); }
    .combat-detail-stat { padding: .95rem; display: flex; align-items: flex-start; gap: .7rem; }
    .combat-detail-icon { display: inline-grid; place-items: center; width: 2rem; height: 2rem; flex: none; border-radius: .55rem; background: #eff6ff; color: #2563eb; }
    .combat-detail-icon svg { width: 1.1rem; height: 1.1rem; }
    .combat-detail-icon.is-rose { background: #fff1f2; color: #e11d48; }
    .combat-detail-icon.is-green { background: #ecfdf5; color: #059669; }
    .combat-detail-icon.is-violet { background: #f5f3ff; color: #7c3aed; }
    .combat-detail-icon.is-amber { background: #fffbeb; color: #d97706; }
    .combat-detail-stat-label { color: var(--bs-secondary-color); font-size: .82rem; }
    .combat-detail-stat-value { margin-top: .2rem; font-size: 1.05rem; font-weight: 750; line-height: 1.3; overflow-wrap: anywhere; }
    .combat-detail-stat-note { color: var(--bs-secondary-color); font-size: .78rem; margin-top: .18rem; }
    .combat-detail-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .9rem; align-items: start; }
    .combat-detail-column { display: grid; gap: .9rem; min-width: 0; }
    .combat-detail-card { overflow: hidden; }
    .combat-detail-card-head { display: flex; align-items: center; justify-content: space-between; gap: .6rem; padding: .85rem 1.05rem; border-bottom: 1px solid var(--bs-border-color); }
    .combat-detail-card-heading { display: flex; align-items: center; gap: .55rem; min-width: 0; }
    .combat-detail-card-heading .combat-detail-icon { width: 1.7rem; height: 1.7rem; }
    .combat-detail-card-heading h3 { margin: 0; font-size: 1rem; font-weight: 750; }
    .combat-detail-card-heading small { display: block; color: var(--bs-secondary-color); font-size: .8rem; }
    .combat-detail-card-head .btn { display: inline-flex; align-items: center; gap: .2rem; white-space: nowrap; }
    .combat-detail-card-head .btn .combat-detail-icon { width: 1rem; height: 1rem; background: transparent; }
    .combat-detail-card-head .btn .combat-detail-icon svg { width: .8rem; height: .8rem; }
    .combat-detail-card-body { padding: 1.05rem; }
    .combat-detail-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem 1rem; margin: 0; }
    .combat-detail-field { min-width: 0; padding-bottom: .5rem; border-bottom: 1px solid var(--bs-border-color); }
    .combat-detail-field dt { font-size: .8rem; color: var(--bs-secondary-color); font-weight: 500; }
    .combat-detail-field dd { margin: .2rem 0 0; font-size: .9rem; font-weight: 650; overflow-wrap: anywhere; }
    .combat-detail-field.is-wide { grid-column: 1 / -1; }
    .combat-detail-timeline { display: grid; grid-template-columns: repeat(var(--combat-step-count, 6), minmax(0, 1fr)); margin-bottom: 1rem; }
    .combat-detail-step { position: relative; padding-top: 1.8rem; text-align: center; font-size: .8rem; color: var(--bs-secondary-color); min-width: 0; }
    .combat-detail-step::before { content: ''; position: absolute; top: .2rem; left: calc(50% - .38rem); width: .76rem; height: .76rem; border-radius: 50%; background: #cbd5e1; z-index: 1; }
    .combat-detail-step::after { content: ''; position: absolute; top: .53rem; left: 50%; width: 100%; height: 2px; background: #cbd5e1; }
    .combat-detail-step:last-child::after { display: none; }
    .combat-detail-step.is-current { color: #e11d48; font-weight: 750; }
    .combat-detail-step.is-current::before { background: #e11d48; box-shadow: 0 0 0 4px #ffe4e6; }
    .combat-detail-step.is-recorded::before { background: #10b981; }
    .combat-detail-step strong { display: block; overflow-wrap: anywhere; }
    .combat-detail-step span { display: block; margin-top: .25rem; }
    .combat-detail-step .combat-detail-duration { color: var(--bs-body-color); font-weight: 600; }
    .combat-detail-history { width: 100%; font-size: .86rem; }
    .combat-detail-history th { color: var(--bs-secondary-color); font-size: .77rem; text-transform: uppercase; letter-spacing: .03em; }
    .combat-detail-history td, .combat-detail-history th { padding: .65rem .4rem; border-bottom: 1px solid var(--bs-border-color); vertical-align: top; }
    .combat-detail-history tr:last-child td { border: 0; }
    .combat-detail-muted { color: var(--bs-secondary-color); font-size: .84rem; }
    .combat-detail-extra summary { cursor: pointer; font-size: .9rem; font-weight: 650; }
    .combat-detail-edit { background: var(--bs-body-bg); padding: 1rem; border: 1px solid var(--bs-border-color); border-radius: .75rem; }
    .combat-detail-edit h3 { font-size: 1rem; font-weight: 750; }
    #detailModal [data-site-performance] .card { border: 0 !important; box-shadow: none; }
    #detailModal [data-site-performance] .card-header { border: 0; padding: .25rem 0 .6rem; }
    #detailModal [data-site-performance] .card-header strong { display: none; }
    #detailModal [data-site-performance] .card-body { padding: 0; }
    #detailModal [data-site-performance] .highcharts-axis-labels text,
    #detailModal [data-site-performance] .highcharts-legend-item text { font-size: 13px !important; }
    @media (max-width: 820px) { .combat-detail-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .combat-detail-grid { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { #detailModal .combat-detail-dialog { width: calc(100vw - 1rem); margin: .5rem auto; } #detailModal .modal-body { padding: .8rem; } .combat-detail-hero { flex-direction: column; } .combat-detail-badges { justify-content: flex-start; } .combat-detail-stats { gap: .5rem; } .combat-detail-stat { padding: .75rem; } .combat-detail-timeline { overflow-x: auto; } .combat-detail-step { min-width: 78px; } }
    @media (max-width: 420px) { .combat-detail-stats { grid-template-columns: 1fr; } }
</style>
@endpush

@push('scripts')
<script src="{{ asset('js/infrastructure-stage-duration.js') }}"></script>
<script src="{{ asset('js/infrastructure-site-map.js') }}"></script>
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const exportBaseUrl = @json(route('infrastruktur.combat.export-excel'));
    const tableInitiallyVisible = !$('#infrastructure-data').hasClass('d-none');

    const urlParams = new URLSearchParams(window.location.search);
    const currentFilter = {
        field: urlParams.get('filter_field') || '',
        value: urlParams.get('filter_value') || ''
    };

    function updateExportUrl() {
        const params = new URLSearchParams({ tahun: $('#filter-tahun').val() || 'all' });
        const jarvis = $('#filter-available-on-jarvis').val();
        if (jarvis) params.set('available_on_jarvis', jarvis);
        $('#btn-export').attr('href', exportBaseUrl + '?' + params);
    }

    // Sinkronisasi tahun jika ada di URL
    const initialTahun = urlParams.get('tahun');
    if (initialTahun && $('#filter-tahun option[value="' + initialTahun + '"]').length) {
        $('#filter-tahun').val(initialTahun);
    }
    updateExportUrl();

    function updateFilterBadge() {
        if (currentFilter.field && currentFilter.value) {
            const labelOnlyFilters = {
                priority: 'Prioritas Tindakan',
                lease_alert: 'Peringatan Masa Sewa',
            };
            const fieldLabel = labelOnlyFilters[currentFilter.field] || currentFilter.field.replace(/_/g, ' ');
            $('#active-filter-text').text(labelOnlyFilters[currentFilter.field] ? fieldLabel : `${fieldLabel}: ${currentFilter.value}`);
            $('#active-filter-alert').removeClass('d-none');
        } else {
            $('#active-filter-alert').addClass('d-none');
        }
    }
    updateFilterBadge();

    $('#btn-clear-filter').on('click', function () {
        currentFilter.field = '';
        currentFilter.value = '';
        const url = new URL(window.location.href);
        url.searchParams.delete('filter_field');
        url.searchParams.delete('filter_value');
        url.searchParams.delete('unique_sites');
        window.history.pushState({}, '', url);
        updateFilterBadge();
        if (($('#filter-tahun').val() || 'all') !== 'all' || $('#filter-available-on-jarvis').val()) {
            table.ajax.reload();
        } else {
            $('#infrastructure-data').addClass('d-none');
        }
    });

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];
    if (isAdmin) { columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false }); }
    columns.push(
        { data: 'site_code', name: 'site_code' },
        { data: 'site_name', name: 'site_name' },
        { data: 'tahun_justi_dirnet', name: 'tahun_justi_dirnet' },
        detailTextColumn('renewal_pattern'),
        detailTextColumn('match_nbae'),
        detailTextColumn('available_on_jarvis'),
        detailTextColumn('type'),
        detailTextColumn('combat_type'),
        detailTextColumn('status'),
        detailTextColumn('status_combat'),
        { data: 'status_dokumen', name: 'status_dokumen' },
        { data: 'status_perpanjangan', name: 'status_perpanjangan', defaultContent: '-' },
        detailTextColumn('nop'),
        detailTextColumn('region'),
        detailTextColumn('kabupaten'),
        detailTextColumn('tp', 'tp_vendor', 'tp_v'),
        dateColumn('start_date_baru'),
        dateColumn('end_date_baru'),
        { data: 'lease_duration', name: 'lease_duration', orderable: false, searchable: false, defaultContent: '-' },
        leaseStatusColumn(),
        { data: 'pks_status_label', name: 'pks_status_label', orderable: false, searchable: false, defaultContent: '-' },
        { data: 'no_pks_baru', name: 'no_pks_baru', defaultContent: '-' },
        moneyColumn('total_harga_baru'),
        { data: 'process_aging_days', name: 'process_aging_days', orderable: false, searchable: false,
            render: (value, type) => type === 'display' ? (value === null || value === undefined ? '-' : `${value} hari`) : value },
        { data: 'next_action_label', name: 'next_action_label', orderable: false, searchable: false, defaultContent: '-' },
        { data: 'priority_label', name: 'priority_label', orderable: false, searchable: false, defaultContent: '-' },
    );

    const tableOptions = {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('infrastruktur.combat.data')), data: (d) => {
            d.tahun = $('#filter-tahun').val();
            d.available_on_jarvis = $('#filter-available-on-jarvis').val();
            d.filter_field = currentFilter.field;
            d.filter_value = currentFilter.value;
            d.unique_sites = new URLSearchParams(window.location.search).get('unique_sites') || '';
        } },
        columns,
        order: [],
        pageLength: 10,
    };
    if (!tableInitiallyVisible) tableOptions.deferLoading = 0;
    const table = new DataTable('#combat-table', tableOptions);

    $(document).on('infrastructure-filter', function (e, data) {
        $('#infrastructure-data').removeClass('d-none');
        currentFilter.field = (data && data.filterField) || '';
        currentFilter.value = (data && data.filterValue) || '';

        if (currentFilter.field === 'all_records') {
            const selectedYear = new URLSearchParams(window.location.search).get('tahun') || 'all';
            $('#filter-tahun').val(selectedYear);
            updateExportUrl();
            table.search('').columns().search('');
        }

        if (currentFilter.field === 'tahun') {
            $('#filter-tahun').val(currentFilter.value || 'all');
            updateExportUrl();
            currentFilter.field = '';
            currentFilter.value = '';
        }

        updateFilterBadge();
        table.ajax.reload();
        table.columns.adjust();
        if (typeof window.scrollToInfrastructureTable === 'function') {
            window.scrollToInfrastructureTable();
        }
    });

    $('#page-size').on('change', function () { table.page.len(parseInt($(this).val())).draw(); });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }
    function fmtMoney(v) { return v == null || v === '' ? '-' : new Intl.NumberFormat('id-ID').format(v); }
    function fmtDate(v) { if (!v) return '-'; const d = new Date(v); return isNaN(d) ? esc(v) : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }
    function sourceDetails(row) {
        const details = row?.source_details || {};
        const database = details.database && typeof details.database === 'object' ? details.database : {};
        const revenue = details.database_revenue && typeof details.database_revenue === 'object' ? details.database_revenue : {};
        const flat = Object.fromEntries(Object.entries(details)
            .filter(([key]) => key !== 'database' && key !== 'database_revenue'));

        // Keep the table readable even when a browser receives a pre-fix
        // response from cache: DATABASE is the master sheet and therefore
        // overrides the revenue sheet for NOP, TP/Vendor, and Status Site.
        return { ...revenue, ...database, ...flat };
    }
    function detailValue(row, ...keys) {
        const details = sourceDetails(row);
        for (const key of keys) {
            const value = details[key];
            if (value !== null && value !== undefined && value !== ''
                && !(typeof value === 'string' && value.trim().startsWith('='))) return value;
        }
        return null;
    }
    function detailTextColumn(...keys) {
        return {
            data: null, orderable: false, searchable: false,
            render: (_, type, row) => type === 'display' ? esc(detailValue(row, ...keys)) : detailValue(row, ...keys)
        };
    }
    function moneyColumn(key) {
        return {
            data: key, name: key,
            render: (value, type) => type === 'display' ? (value ? 'Rp ' + fmtMoney(value) : '-') : value
        };
    }
    function dateColumn(key) {
        return {
            data: key, name: key,
            render: (value, type) => type === 'display' ? fmtDate(value) : value
        };
    }
    function leaseStatus(row) {
        const raw = row?.end_date_baru || row?.end_date_lama;
        if (!raw) return @json(\App\Support\LeaseStatus::NO_END_DATE);
        const end = new Date(raw);
        if (Number.isNaN(end.getTime())) return detailValue(row, 'status_masa_sewa', 'status_masa_sewa2') || '-';
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);
        const days = Math.round((end - today) / 86400000);
        if (days < 0) return @json(\App\Support\LeaseStatus::EXPIRED);
        if (days <= 90) return @json(\App\Support\LeaseStatus::WITHIN_90_DAYS);
        if (days <= 180) return @json(\App\Support\LeaseStatus::WITHIN_180_DAYS);
        return @json(\App\Support\LeaseStatus::SAFE);
    }
    function leaseStatusColumn() {
        return {
            data: null, orderable: false, searchable: false,
            render: (_, type, row) => type === 'display' ? esc(leaseStatus(row)) : leaseStatus(row)
        };
    }
    function isPnlKey(key) {
        const k = key.toLowerCase();
        return /^(rev|cost|pnl|revenue|margin|profit|tracy|payload)/i.test(k)
            || /_202[0-9]/.test(k)
            || /-(25|26)/.test(k)
            || /(jan|feb|mar|apr|mei|may|jun|jul|aug|agu|sep|okt|oct|nov|des|dec)/i.test(k);
    }

    const KNOWN_COMBAT_KEYS = new Set([
        'no', 'aksi', 'site_id', 'site_code', 'site_name', 'tahun_justi_dirnet', 'renewal_cycle', 'renewal_pattern',
        'status_dokumen', 'status', 'status_perpanjangan', 'nomor_pks_baru', 'no_pks_baru',
        'periode_awal_baru', 'start_date_baru', 'periode_akhir_baru', 'end_date_baru',
        'aging_days', 'lama_proses_saat_ini_hari', 'process_aging_days',
        'harga_baru', 'total_harga_baru', 'penawaran_1', 'nego_1', 'penawaran_2', 'nego_2',
        'penawaran_3', 'nego_3', 'nomor_pks_existing', 'no_pks_lama', 'periode_awal_existing',
        'start_date_lama', 'periode_akhir_existing', 'end_date_lama', 'harga_lama', 'total_harga_existing',
        'nomor_surat', 'keterangan', 'update_by', 'tanggal',
        'tp_vendor', 'tp_v', 'nop', 'region', 'alamat', 'address', 'kabupaten', 'kota', 'kecamatan',
        'latitude', 'longitude', 'longtitude', 'lat', 'lon', 'lng', 'height_combat', 'height_combat_m',
        'height_rf', 'date_connect', 'status_justifikasi', 'justifikasi'
    ]);
    function renderSourceDetails(d) {
        const details = sourceDetails(d);
        const rows = Object.entries(details)
            .filter(([key, value]) => {
                if (key.startsWith('_') || value === null || value === '' || typeof value === 'object') return false;
                // Formula Excel adalah artefak sumber data, bukan informasi
                // yang dapat dibaca pengguna di modal detail.
                if (typeof value === 'string' && value.trim().startsWith('=')) return false;
                const cleanKey = key.toLowerCase().replace(/[\s\/\-]+/g, '_');
                if (isPnlKey(cleanKey)) return false;
                if (['pks_status', 'status_masa_sewa', 'status_masa_sewa2'].includes(cleanKey)) return false;
                if (KNOWN_COMBAT_KEYS.has(cleanKey)) return false;
                return true;
            })
            .map(([key, value]) => {
                const displayValue = infrastructureStageDuration.formatSourceDate(key, value);
                return detailField(key.replaceAll('_', ' '), displayValue);
            })
            .join('');
        return rows ? `<details class="combat-detail-card combat-detail-extra"><summary class="combat-detail-card-head">Kolom tambahan dari data Combat</summary><div class="combat-detail-card-body"><dl class="combat-detail-fields">${rows}</dl></div></details>` : '';
    }

    function combatIcon(name, tone = '') {
        const paths = {
            tower: '<path d="M12 2v20M5 22 12 3l7 19M7 16h10M9 11h6"/>',
            calendar: '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/>',
            renewal: '<path d="M20 7v5h-5M4 17v-5h5"/><path d="M5.5 9A7 7 0 0 1 18 6l2 1M4 17l2 1a7 7 0 0 0 12.5-3"/>',
            document: '<path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>',
            chart: '<path d="M4 20V4M4 20h16M7 15l4-4 3 2 5-6"/>',
            pin: '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
            edit: '<path d="M12 20h9M4 16l-.5 4.5L8 20l11-11-4-4L4 16ZM13 7l4 4"/>',
            list: '<path d="M4 6h16M4 12h16M4 18h16"/>',
            clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        };
        return `<span class="combat-detail-icon${tone ? ` is-${tone}` : ''}" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">${paths[name]}</svg></span>`;
    }
    function detailField(label, value, wide = false) {
        return `<div class="combat-detail-field${wide ? ' is-wide' : ''}"><dt>${esc(label)}</dt><dd>${esc(value)}</dd></div>`;
    }
    function detailCard(title, subtitle, icon, body, action = '', tone = '') {
        return `<section class="combat-detail-card"><div class="combat-detail-card-head"><div class="combat-detail-card-heading">${combatIcon(icon, tone)}<div><h3>${title}</h3><small>${subtitle}</small></div></div>${action}</div><div class="combat-detail-card-body">${body}</div></section>`;
    }
    function moneyLabel(value) { return value === null || value === undefined || value === '' ? '-' : `Rp ${fmtMoney(value)}`; }
    function coordinates(d) {
        const rawLat = detailValue(d, 'latitude', 'lat');
        const rawLon = detailValue(d, 'longitude', 'longtitude', 'lng', 'lon');
        if (rawLat === null || rawLon === null) return null;
        const lat = Number(String(rawLat).replace(',', '.'));
        const lon = Number(String(rawLon).replace(',', '.'));
        return Number.isFinite(lat) && Number.isFinite(lon) && lat >= -90 && lat <= 90 && lon >= -180 && lon <= 180 && !(lat === 0 && lon === 0)
            ? { lat, lon } : null;
    }

    function currentStage(d) {
        const status = String(d.status_dokumen || d.status_perpanjangan || '').trim();
        const code = status.match(/^([A-L])\./i)?.[1]?.toUpperCase();
        if (code === 'A') return 'Negosiasi';
        if (['B', 'C'].includes(code)) return 'BAK';
        if (['D', 'E', 'F'].includes(code)) return 'PKS';
        if (['G', 'H'].includes(code)) return 'Pending PKS';
        if (['I', 'J'].includes(code)) return 'Finance';
        if (['K', 'L'].includes(code)) return 'Drop';
        if (/paid|bayar/i.test(status)) return 'Paid';
        if (/drop|dismantle|relokasi/i.test(status)) return 'Drop';
        if (/financ|reimburse|budget/i.test(status)) return 'Finance';
        if (/pending.*pks|pks.*pending/i.test(status)) return 'Pending PKS';
        if (/pks|legal/i.test(status)) return 'PKS';
        if (/bak/i.test(status)) return 'BAK';
        if (/nego/i.test(status)) return 'Negosiasi';
        return null;
    }
    function renderTimeline(d) {
        const current = currentStage(d);
        const currentLabel = current === 'PKS' ? 'PKS/BAK ke Owner'
            : current === 'Pending PKS' ? 'PKS ke Legal' : current;
        const stages = [
            ['Negosiasi', 'proses_negosiasi_at', 'tgl_negosiasi', 'tanggal_negosiasi'],
            ['BAK', 'tgl_bak_baru', 'tanggal_bak', 'tgl_bak'],
            ['PKS/BAK ke Owner', 'proses_pks_owner_at', 'tgl_pks_bak_ke_owner', 'tgl_pks_ke_owner', 'tgl_pksbak_ke_owner'],
            ['PKS ke Legal', 'proses_pks_legal_at', 'tgl_pks_ke_legal', 'tgl_pending_pks'],
            ['Finance', 'proses_budget_at', 'tgl_budget', 'tgl_finance'],
            ['Paid', 'proses_paid_at', 'tgl_paid'],
        ];
        if (detailValue(d, 'proses_drop_at', 'tgl_drop') || current === 'Drop') {
            stages.push(['Drop', 'proses_drop_at', 'tgl_drop']);
        }
        const timelineStages = stages.map(([label, ...keys]) => [label, detailValue(d, ...keys)]);
        return `<div class="combat-detail-timeline" style="--combat-step-count:${stages.length}">${timelineStages.map(([label, date], index) => {
            const formattedDate = infrastructureStageDuration.formatDate(date);
            const duration = infrastructureStageDuration.forStage(timelineStages, index, { terminal: label === 'Paid' || label === 'Drop' });
            const durationMarkup = duration
                ? `<span class="combat-detail-duration">${esc(duration.label)}</span>` : '';
            return `<div class="combat-detail-step${label === currentLabel ? ' is-current' : formattedDate ? ' is-recorded' : ''}"><strong>${label}</strong><span>${formattedDate || (label === currentLabel ? 'Tahap saat ini' : '')}</span>${durationMarkup}</div>`;
        }).join('')}</div><div class="combat-detail-muted">${current ? `Tahap saat ini: ${esc(current)}. ` : 'Tahap proses belum tercatat. '}Lama tahap dihitung sampai tahap berikutnya atau hari ini.</div>`;
    }
    function renderHistory(history, d) {
        const rows = history.length ? history : [{ id: d.id, year: d.tahun_justi_dirnet, site_name: d.site_name, status_justifikasi: detailValue(d, 'status_justifikasi', 'justifikasi'), status_dokumen: d.status_dokumen }];
        const body = `<div class="table-responsive"><table class="combat-detail-history"><thead><tr><th>Tahun</th><th>Site Name</th><th>Status Justifikasi</th><th>Status Dokumen</th></tr></thead><tbody>${rows.map(row => `<tr><td>${esc(row.year)}</td><td>${esc(row.site_name)}</td><td>${esc(row.status_justifikasi || 'Belum tercatat')}</td><td>${esc(row.status_dokumen)}</td></tr>`).join('')}</tbody></table></div>`;
        return detailCard('Riwayat Nama & Tahun Combat', 'Catatan tahun untuk Site ID yang sama.', 'calendar', body, '', 'violet');
    }
    function renderDetail(d, history = []) {
        const statusSite = detailValue(d, 'status');
        const statusCombat = detailValue(d, 'status_combat');
        const justifikasi = detailValue(d, 'status_justifikasi', 'justifikasi');
        const location = [detailValue(d, 'kabupaten', 'kota', 'area'), detailValue(d, 'region')].filter(Boolean).join(', ') || 'Lokasi belum tercatat';
        const address = detailValue(d, 'address', 'alamat');
        const point = coordinates(d);
        const years = [...new Set(history.map(item => Number(item.year)).filter(Number.isFinite))].sort((a, b) => a - b);
        const yearRange = years.length > 1 ? `${years[0]} – ${years[years.length - 1]}` : d.tahun_justi_dirnet || '-';
        const renewalCount = Math.max(0, years.length - 1);
        const renewalPattern = String(detailValue(d, 'renewal_pattern', 'renewal_cycle') || '').trim();
        const renewalLabel = /^\d+\s*x?\s*renewal/i.test(renewalPattern) ? renewalPattern : /^new$/i.test(renewalPattern) ? '0x' : `${renewalCount}x`;
        const renewalNote = renewalPattern ? `Pola: ${esc(renewalPattern)}` : 'Dihitung dari riwayat tahun';
        const statusTone = /off\s*air|non.?operational|dismantle|relokasi/i.test(String(statusSite || '')) ? 'is-danger' : /on\s*air|active/i.test(String(statusSite || '')) ? 'is-success' : '';
        const editButton = d.edit_url ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-combat-edit data-url="${esc(d.edit_url)}">${combatIcon('edit')} Edit</button>` : '';
        const info = `<dl class="combat-detail-fields">
            ${detailField('Site ID', d.site_code)}${detailField('Region', detailValue(d, 'region'))}
            ${detailField('Site Name', d.site_name)}${detailField('Kabupaten / Kota', detailValue(d, 'kabupaten', 'kota', 'area'))}
            ${detailField('Type / Combat Type', [detailValue(d, 'type'), detailValue(d, 'combat_type')].filter(Boolean).join(' / '))}${detailField('Kecamatan', detailValue(d, 'kecamatan'))}
            ${detailField('Status Site', statusSite)}${detailField('Alamat', address)}
            ${detailField('Tahun Combat', d.tahun_justi_dirnet)}${detailField('Latitude', point?.lat)}
            ${detailField('NOP', detailValue(d, 'nop'))}${detailField('Longitude', point?.lon)}
            ${detailField('TP / Vendor', detailValue(d, 'tp', 'tp_vendor', 'tp_v'))}${detailField('Status Combat', statusCombat)}
        </dl>`;
        const locationBody = `${infrastructureSiteMap.render(point, address, detailValue(d, 'kabupaten', 'kota', 'area'))}<dl class="combat-detail-fields mt-3">${detailField('Alamat Lengkap', address, true)}${detailField('Region', detailValue(d, 'region'))}${detailField('Kabupaten / Kota', detailValue(d, 'kabupaten', 'kota', 'area'))}${detailField('Height Combat', detailValue(d, 'height_combat_m', 'height_combat'))}${detailField('Height RF', detailValue(d, 'height_rf'))}${detailField('Date Connect', fmtDate(detailValue(d, 'date_connect')))}</dl>`;
        const documents = `<dl class="combat-detail-fields">
            ${detailField('Status Dokumen', d.status_dokumen)}${detailField('Mulai Tahap Proses', fmtDate(d.process_started_at))}
            ${detailField('Status Perpanjangan', d.status_perpanjangan)}${detailField('Process Aging', d.process_aging_days == null ? null : `${d.process_aging_days} hari`)}
            ${detailField('PKS Status', d.pks_status_label)}${detailField('Status Masa Sewa', leaseStatus(d))}
            ${detailField('No. PKS Baru', d.no_pks_baru)}${detailField('No. PKS Lama', d.no_pks_lama)}
            ${detailField('Start Date Baru', fmtDate(d.start_date_baru))}${detailField('End Date Baru', fmtDate(d.end_date_baru))}
            ${detailField('Start Date Lama', fmtDate(d.start_date_lama))}${detailField('End Date Lama', fmtDate(d.end_date_lama))}
            ${detailField('Harga Baru', moneyLabel(d.harga_baru))}${detailField('Total Harga Baru', moneyLabel(d.total_harga_baru))}
            ${detailField('Harga Lama', moneyLabel(d.harga_lama))}${detailField('Masa Sewa', d.lease_duration)}
            ${detailField('Nomor Surat', d.nomor_surat)}${detailField('Update By', d.update_by)}
            ${detailField('Keterangan', d.keterangan, true)}
        </dl>`;
        const negotiation = `<dl class="combat-detail-fields">${[1, 2, 3].map(i => `${detailField(`Penawaran ${i}`, moneyLabel(d[`penawaran_${i}`]))}${detailField(`Nego ${i}`, moneyLabel(d[`nego_${i}`]))}`).join('')}</dl>`;
        return `<div class="combat-detail">
            <div class="combat-detail-hero infrastructure-site-hero" data-site-hero="${encodeURIComponent(String(d.site_code || d.site_name || d.id || 'site'))}" data-site-photo-code="${encodeURIComponent(String(d.site_code || ''))}"><div class="combat-detail-identity"><div class="combat-detail-logo">${combatIcon('tower')}</div><div><span class="combat-detail-eyebrow">COMBAT SITE</span><h2 class="combat-detail-name">${esc(d.site_code)} · ${esc(d.site_name)}</h2><div class="combat-detail-meta"><span>${combatIcon('pin')} ${esc(location)}</span><span>${combatIcon('list')} ${esc(detailValue(d, 'nop'))}</span></div></div></div><div class="combat-detail-badges"><span class="combat-detail-badge ${statusTone}">${esc(statusSite || 'Status site belum tercatat')}</span><span class="combat-detail-badge ${justifikasi ? 'is-warning' : ''}">${esc(justifikasi || 'Justifikasi belum tercatat')}</span></div></div>
            <div class="combat-detail-stats">
                <div class="combat-detail-stat">${combatIcon('calendar')}<div><div class="combat-detail-stat-label">Tahun Combat</div><div class="combat-detail-stat-value">${esc(yearRange)}</div><div class="combat-detail-stat-note">${years.length ? `${years.length} tahun tercatat` : 'Tahun belum tercatat'}</div></div></div>
                <div class="combat-detail-stat">${combatIcon('renewal', 'green')}<div><div class="combat-detail-stat-label">Jumlah Renewal</div><div class="combat-detail-stat-value">${esc(renewalLabel)}</div><div class="combat-detail-stat-note">${renewalNote}</div></div></div>
                <div class="combat-detail-stat">${combatIcon('document', 'violet')}<div><div class="combat-detail-stat-label">Status Dokumen</div><div class="combat-detail-stat-value">${esc(d.status_dokumen)}</div></div></div>
                <div class="combat-detail-stat">${combatIcon('chart', 'rose')}<div><div class="combat-detail-stat-label">Status Justifikasi</div><div class="combat-detail-stat-value">${esc(justifikasi || 'Belum tercatat')}</div></div></div>
            </div>
            <div class="combat-detail-grid"><div class="combat-detail-column">
                ${detailCard('Informasi Utama', 'Detail dasar site dan kepemilikan.', 'list', info, editButton)}
                ${renderHistory(history, d)}
                ${detailCard('Lokasi Site', 'Informasi geografis dan lokasi site.', 'pin', locationBody, '', 'green')}
                ${renderSourceDetails(d)}
            </div><div class="combat-detail-column">
                ${detailCard('Progres Perpanjangan', 'Tahapan dari status dokumen dan tanggal yang tercatat.', 'renewal', renderTimeline(d), '', 'rose')}
                ${detailCard('Financial Performance', 'Revenue, cost, dan PnL bulanan yang tersedia.', 'chart', '<div data-site-performance></div>')}
                ${detailCard('Dokumen & Proses', 'Informasi dokumen, PKS, dan masa sewa.', 'document', documents, '', 'violet')}
                ${detailCard('Penawaran & Negosiasi', 'Nilai per tahap dari data Combat.', 'clock', negotiation, '', 'amber')}
            </div></div></div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');
    let currentDetail = null;
    let detailRequest = 0;
    let refreshTableOnClose = false;

    function showCurrentDetail(message = '') {
        if (!currentDetail) return;
        const { site, history } = currentDetail;
        $('#detail-title').text(`Detail Combat ${site.site_code || ''}`);
        $('#detail-body').html(`${message ? `<div class="alert alert-success" role="status">${esc(message)}</div>` : ''}${renderDetail(site, history)}`);
        infrastructureSiteHero.apply(document.getElementById('detail-body'));
        window.renderInfrastructureSitePerformance(
            document.querySelector('#detail-body [data-site-performance]'),
            site.performance
        );
        $('#detail-body').scrollTop(0);
    }

    async function loadDetail(url, fallback = null, message = '') {
        const requestId = ++detailRequest;
        $('#detail-body').html('<div class="text-center text-body-secondary py-5">Memuat detail Combat...</div>');
        try {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Detail Combat tidak dapat dimuat. Coba lagi.');
            }
            const data = await response.json();
            if (requestId !== detailRequest) return;
            currentDetail = { site: data.site, history: Array.isArray(data.history) ? data.history : [] };
            showCurrentDetail(message);
        } catch (error) {
            if (requestId !== detailRequest) return;
            currentDetail = fallback ? { site: fallback, history: [] } : null;
            if (currentDetail) showCurrentDetail();
            $('#detail-body').prepend(`<div class="alert alert-danger" role="alert">${esc(error.message)}</div>`);
        }
    }

    $('#detailModal').on('hidden.bs.modal', function () {
        detailRequest++;
        currentDetail = null;
        if (refreshTableOnClose) table.ajax.reload(null, false);
        refreshTableOnClose = false;
    });

    $('#detail-body').on('click', '[data-combat-edit]', async function () {
        const url = this.dataset.url;
        $('#detail-body').html('<div class="text-center text-body-secondary py-5">Memuat formulir edit...</div>');
        try {
            const response = await fetch(`${url}?modal=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Formulir edit tidak dapat dibuka. Periksa sesi dan hak akses Anda.');
            const formMarkup = $('<div>').html(await response.text());
            if (!formMarkup.find('[data-combat-edit-form]').length) throw new Error('Sesi telah berakhir. Muat ulang halaman untuk melanjutkan.');
            formMarkup.find('[id]').each(function () { this.id = `combat-modal-${this.id}`; });
            formMarkup.find('label[for]').each(function () { this.htmlFor = `combat-modal-${this.htmlFor}`; });
            $('#detail-body').html(`<div class="combat-detail-edit"><h3 class="mb-3">Edit Combat ${esc(currentDetail?.site?.site_code)}</h3></div>`);
            $('#detail-body .combat-detail-edit').append(formMarkup.contents());
            $('#detail-body').scrollTop(0);
            $('#detail-body [data-combat-edit-form] input:not([type="hidden"])').first().trigger('focus');
        } catch (error) {
            showCurrentDetail();
            $('#detail-body').prepend(`<div class="alert alert-danger" role="alert">${esc(error.message)}</div>`);
        }
    });

    $('#detail-body').on('click', '[data-combat-cancel-edit]', function () { showCurrentDetail(); });

    $('#detail-body').on('submit', '[data-combat-edit-form]', async function (event) {
        event.preventDefault();
        const form = this;
        const submit = form.querySelector('[type="submit"]');
        const errorBox = form.querySelector('[data-combat-form-errors]');
        errorBox.classList.add('d-none');
        form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
        submit.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Sesi telah berakhir. Muat ulang halaman untuk melanjutkan.');
            }
            const data = await response.json();
            if (response.status === 422) {
                errorBox.textContent = Object.values(data.errors || {}).flat().join(' ') || 'Periksa kembali isian formulir.';
                errorBox.classList.remove('d-none');
                for (const name of Object.keys(data.errors || {})) {
                    const field = Array.from(form.elements).find(element => element.name === name);
                    if (field) field.classList.add('is-invalid');
                }
                errorBox.scrollIntoView({ block: 'nearest' });
                return;
            }
            if (!response.ok) throw new Error(data.message || 'Perubahan tidak dapat disimpan.');
            refreshTableOnClose = true;
            await loadDetail(currentDetail.site.detail_url, null, data.message || 'Perubahan berhasil disimpan.');
        } catch (error) {
            errorBox.textContent = error.message || 'Koneksi terputus. Coba lagi.';
            errorBox.classList.remove('d-none');
        } finally {
            submit.disabled = false;
        }
    });

    $('#combat-table tbody').on('click', '.aksi-cell button, .aksi-cell a', function (e) { e.stopPropagation(); });

    $('#combat-table tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('.aksi-cell').length > 0) return;
        const row = table.row(this).data();
        if (!row) return;
        $('#detail-title').text(`Detail Combat ${row.site_code || ''}`);
        detailModal.show(this);
        loadDetail(row.detail_url, row);
    });
});
</script>
@endpush
