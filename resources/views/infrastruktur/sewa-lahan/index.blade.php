@extends('layouts.app')

@section('title', 'Sewa Lahan — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — Sewa Lahan</h1>
        @include('infrastruktur.partials.entry-actions', ['dataset' => 'sewa-lahan'])
    </div>
    @include('infrastruktur.partials.scope-filter', ['scope' => 'sewa'])

    @include('infrastruktur.partials.analytics', ['scope' => 'sewa'])

    {{-- Container Data Tabel Infrastruktur --}}
    <div id="infrastructure-data" class="infrastructure-table-section mt-4 {{ request()->filled('filter_field') || request()->filled('ownership_scope') || (request()->filled('tahun') && request('tahun') !== 'all') || request()->boolean('unique_sites') ? '' : 'd-none' }}">
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
                <a href="{{ route('infrastruktur.sewa-lahan.export-excel') }}" id="btn-export" class="btn btn-sm btn-outline-brand ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                    Excel
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <table id="sewa-table" class="display align-middle text-nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            @role('admin') <th>Aksi</th> @endrole
                            <th>Site ID</th>
                            <th>Site Name</th>
                            <th>Tahun Renewal</th>
                            <th>Status Site</th>
                            <th>Status Dokumen</th>
                            <th>Status Perpanjangan</th>
                            <th>Area</th>
                            <th>NOP</th>
                            <th>Ownership</th>
                            <th>Vendor</th>
                            <th>Periode Awal Baru</th>
                            <th>Periode Akhir Baru</th>
                            <th>Masa Sewa</th>
                            <th>Harga Baru / Tahun</th>
                            <th>No PKS Baru</th>
                            <th>Total Harga Baru</th>
                            <th>Tanggal BAK</th>
                            <th>Process Aging</th>
                            <th>No PKS Existing</th>
                            <th>Periode Akhir Existing</th>
                            <th>Total Harga Existing</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    {{-- Modal detail --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable sewa-detail-dialog">
            <div class="modal-content">
                <div class="modal-header sewa-detail-header">
                    <h5 class="modal-title"><span class="sewa-detail-heading-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M5 22l7-18 7 18M7 16h10M9 11h6"/></svg></span> Detail Sewa Lahan <span id="detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body sewa-detail-body" id="detail-body">
                    <div class="text-center text-body-secondary">Memuat...</div>
                </div>
            </div>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'sewa-lahan'])
@endsection

@push('styles')
<style>
    #sewa-table tbody tr { cursor: pointer; }
    #detailModal .sewa-detail-dialog { max-width: min(1180px, calc(100vw - 2rem)); }
    #detailModal .modal-content { border: 0; border-radius: 18px; overflow: hidden; }
    #detailModal .sewa-detail-header { padding: 1rem 1.5rem; border-bottom-color: var(--bs-border-color); }
    #detailModal .sewa-detail-heading-icon { display: inline-grid; place-items: center; width: 2rem; height: 2rem; margin-right: .4rem; border-radius: .65rem; color: #2563eb; background: #eff6ff; }
    #detailModal .sewa-detail-body { padding: 1.5rem; background: var(--bs-tertiary-bg); }
    .sewa-detail-intro { display: flex; flex-wrap: wrap; justify-content: space-between; gap: .75rem; align-items: center; margin-bottom: 1.25rem; }
    .sewa-detail-site { font-size: 1.6rem; font-weight: 750; letter-spacing: -.025em; line-height: 1.1; overflow-wrap: anywhere; }
    .sewa-detail-name { font-weight: 600; margin-top: .2rem; overflow-wrap: anywhere; }
    .sewa-detail-muted { color: var(--bs-secondary-color); font-size: .82rem; }
    .sewa-detail-intro .sewa-detail-muted .sewa-detail-icon { width: 1rem; height: 1rem; background: transparent; vertical-align: middle; }
    .sewa-detail-chip { display: inline-block; max-width: 100%; padding: .28rem .6rem; border-radius: 99px; background: #e8f6ee; color: #087443; font-size: .76rem; font-weight: 700; overflow-wrap: anywhere; }
    .sewa-detail-chip.is-warning { background: #fff4df; color: #a55b00; }
    .sewa-detail-chip.is-danger { background: #ffe4e6; color: #be123c; }
    .sewa-detail-chip.is-neutral { background: var(--bs-secondary-bg); color: var(--bs-body-color); }
    .sewa-detail-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; margin-bottom: .9rem; }
    .sewa-detail-stat, .sewa-detail-card { background: var(--bs-body-bg); border: 1px solid var(--bs-border-color); border-radius: 12px; box-shadow: 0 2px 8px rgba(15, 23, 42, .025); min-width: 0; }
    .sewa-detail-stat { padding: .9rem 1rem; border-top: 3px solid #93c5fd; }
    .sewa-detail-stat-inner { display: flex; align-items: flex-start; gap: .7rem; }
    .sewa-detail-stat:nth-child(2) { border-top-color: #c4b5fd; }
    .sewa-detail-stat:nth-child(3) { border-top-color: #fbbf24; }
    .sewa-detail-stat:nth-child(4) { border-top-color: #6ee7b7; }
    .sewa-detail-label { color: var(--bs-secondary-color); font-size: .72rem; text-transform: uppercase; letter-spacing: .055em; font-weight: 700; }
    .sewa-detail-stat-value { margin-top: .35rem; font-weight: 700; overflow-wrap: anywhere; }
    .sewa-detail-icon { flex: none; display: inline-grid; place-items: center; width: 2rem; height: 2rem; border-radius: .6rem; color: #2563eb; background: #eff6ff; }
    .sewa-detail-icon svg { width: 1rem; height: 1rem; }
    .sewa-detail-icon.is-green { color: #059669; background: #ecfdf5; }
    .sewa-detail-icon.is-amber { color: #d97706; background: #fffbeb; }
    .sewa-detail-icon.is-violet { color: #7c3aed; background: #f5f3ff; }
    .sewa-detail-grid { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: .9rem; align-items: start; }
    .sewa-detail-column { display: grid; gap: .9rem; min-width: 0; }
    .sewa-detail-card { padding: 1.1rem; }
    .sewa-detail-card h3 { margin: 0 0 1rem; font-size: .95rem; font-weight: 700; }
    .sewa-detail-card-head { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: 1rem; }
    .sewa-detail-card-head h3 { display: flex; align-items: center; gap: .5rem; margin: 0; }
    .sewa-detail-card-head .sewa-detail-icon { width: 1.65rem; height: 1.65rem; }
    .sewa-detail-card-head .sewa-detail-icon svg { width: .9rem; height: .9rem; }
    .sewa-detail-card-head .btn { white-space: nowrap; }
    .sewa-detail-card-head .btn .sewa-detail-icon { width: 1rem; height: 1rem; margin-right: .15rem; background: transparent; vertical-align: middle; }
    .sewa-detail-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem 1.2rem; margin: 0; }
    .sewa-detail-field { min-width: 0; padding-bottom: .55rem; border-bottom: 1px solid var(--bs-border-color); }
    .sewa-detail-field dt { color: var(--bs-secondary-color); font-size: .74rem; font-weight: 500; }
    .sewa-detail-field dd { margin: .2rem 0 0; font-size: .84rem; font-weight: 650; overflow-wrap: anywhere; }
    .sewa-detail-wide { grid-column: 1 / -1; }
    .sewa-detail-map { min-height: 180px; border-radius: 10px; background: linear-gradient(135deg, #e8f1f7, #e3f5ed); display: grid; place-items: center; text-align: center; padding: 1rem; overflow: hidden; }
    .sewa-detail-map iframe { width: 100%; height: 220px; border: 0; }
    .sewa-detail-map-mark { color: #e11d48; font-size: 2rem; line-height: 1; }
    .sewa-detail-map-actions { margin-top: .5rem; display: flex; flex-wrap: wrap; gap: .5rem; justify-content: center; }
    .sewa-detail-timeline { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: .4rem; margin-top: .7rem; }
    .sewa-detail-step { position: relative; min-width: 0; padding-top: 2rem; text-align: center; color: var(--bs-secondary-color); font-size: .75rem; }
    .sewa-detail-step::before { content: ''; position: absolute; top: .4rem; left: calc(50% - .42rem); width: .84rem; height: .84rem; border-radius: 50%; background: #cbd5e1; z-index: 1; }
    .sewa-detail-step::after { content: ''; position: absolute; top: .77rem; left: 50%; width: 100%; height: 2px; background: #cbd5e1; }
    .sewa-detail-step:last-child::after { display: none; }
    .sewa-detail-step.is-recorded { color: var(--bs-body-color); }
    .sewa-detail-step.is-recorded::before { background: #e11d48; box-shadow: 0 0 0 4px #ffe4e6; }
    .sewa-detail-step strong { display: block; font-size: .78rem; }
    .sewa-detail-step span { display: block; margin-top: .25rem; overflow-wrap: anywhere; }
    .sewa-detail-extra { margin-top: .9rem; }
    .sewa-detail-extra summary { cursor: pointer; font-weight: 650; }
    .sewa-detail-extra .sewa-detail-fields { margin-top: 1rem; }
    .sewa-detail-performance { margin-top: .9rem; }
    .sewa-detail-edit-heading { display: flex; align-items: center; justify-content: space-between; gap: .75rem; margin-bottom: 1rem; }
    .sewa-detail-edit-heading h2 { margin: 0; font-size: 1.15rem; font-weight: 700; }
    .sewa-detail-edit-form { padding: 1.25rem; border: 1px solid var(--bs-border-color); border-radius: 12px; background: var(--bs-body-bg); }
    @media (max-width: 850px) {
        .sewa-detail-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sewa-detail-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 580px) {
        #detailModal .sewa-detail-dialog { max-width: calc(100vw - 1rem); margin: .5rem auto; }
        #detailModal .sewa-detail-header, #detailModal .sewa-detail-body { padding: 1rem; }
        .sewa-detail-stats { gap: .5rem; }
        .sewa-detail-stat { padding: .75rem; }
        .sewa-detail-fields { grid-template-columns: 1fr; }
        .sewa-detail-timeline { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: .8rem; }
        .sewa-detail-step:nth-child(3)::after { display: none; }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const exportBaseUrl = @json(route('infrastruktur.sewa-lahan.export-excel'));
    const tableInitiallyVisible = !$('#infrastructure-data').hasClass('d-none');

    const urlParams = new URLSearchParams(window.location.search);
    const currentFilter = {
        field: urlParams.get('filter_field') || '',
        value: urlParams.get('filter_value') || ''
    };

    // Sinkronisasi tahun jika ada di URL
    const initialTahun = urlParams.get('tahun');
    if (initialTahun && $('#filter-tahun option[value="' + initialTahun + '"]').length) {
        $('#filter-tahun').val(initialTahun);
        $('#btn-export').attr('href', exportBaseUrl + '?tahun=' + initialTahun);
    }

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
        $('#infrastructure-data').addClass('d-none');
    });

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];
    if (isAdmin) {
        columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false });
    }
    columns.push(
        { data: 'site_code', name: 'site_code' },
        { data: 'site_name', name: 'site_name' },
        { data: 'tahun_renewal', name: 'tahun_renewal' },
        detailTextColumn('status'),
        { data: 'status_dokumen', name: 'status_dokumen' },
        { data: 'status_perpanjangan', name: 'status_perpanjangan', defaultContent: '-' },
        detailTextColumn('area'),
        detailTextColumn('nop'),
        detailTextColumn('ownership'),
        detailTextColumn('vendor', 'tp'),
        dateColumn('start_date_baru'),
        dateColumn('end_date_baru'),
        { data: 'lease_duration', name: 'lease_duration', orderable: false, searchable: false, defaultContent: '-' },
        moneyColumn('harga_baru'),
        { data: 'no_pks_baru', name: 'no_pks_baru', defaultContent: '-' },
        moneyColumn('total_harga_baru'),
        dateColumn('tgl_bak_baru'),
        { data: 'process_aging_days', name: 'process_aging_days', orderable: false, searchable: false,
            render: (value, type) => type === 'display' ? (value === null || value === undefined ? '-' : `${value} hari`) : value },
        { data: 'no_pks_lama', name: 'no_pks_lama', defaultContent: '-' },
        dateColumn('end_date_lama'),
        detailMoneyColumn('total_harga_existing'),
    );

    const tableOptions = {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('infrastruktur.sewa-lahan.data')), data: (d) => {
            d.tahun = $('#filter-tahun').val();
            d.filter_field = currentFilter.field;
            d.filter_value = currentFilter.value;
            d.ownership_scope = new URLSearchParams(window.location.search).get('ownership_scope') || '';
            d.unique_sites = new URLSearchParams(window.location.search).get('unique_sites') || '';
        } },
        columns,
        order: [],
        pageLength: 10,
    };
    if (!tableInitiallyVisible) tableOptions.deferLoading = 0;
    const table = new DataTable('#sewa-table', tableOptions);

    $(document).on('infrastructure-filter', function (e, data) {
        $('#infrastructure-data').removeClass('d-none');
        currentFilter.field = (data && data.filterField) || '';
        currentFilter.value = (data && data.filterValue) || '';

        if (currentFilter.field === 'all_records') {
            const selectedYear = new URLSearchParams(window.location.search).get('tahun') || 'all';
            $('#filter-tahun').val(selectedYear);
            $('#btn-export').attr('href', exportBaseUrl + '?tahun=' + selectedYear);
            table.search('').columns().search('');
        }

        if (currentFilter.field === 'tahun') {
            $('#filter-tahun').val(currentFilter.value || 'all');
            $('#btn-export').attr('href', exportBaseUrl + '?tahun=' + $('#filter-tahun').val());
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
    function detailValue(row, ...keys) {
        const details = row?.source_details || {};
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
    function detailMoneyColumn(...keys) {
        return {
            data: null, orderable: false, searchable: false,
            render: (_, type, row) => {
                const value = detailValue(row, ...keys);
                return type === 'display' ? (value ? 'Rp ' + fmtMoney(value) : '-') : value;
            }
        };
    }
    function dateColumn(key) {
        return {
            data: key, name: key,
            render: (value, type) => type === 'display' ? fmtDate(value) : value
        };
    }

    function isPnlKey(key) {
        const k = key.toLowerCase();
        return /^(rev|cost|pnl|revenue|margin|profit)/i.test(k)
            || /_202[0-9]/.test(k)
            || /-(25|26)/.test(k)
            || /(jan|feb|mar|apr|mei|may|jun|jul|aug|agu|sep|okt|oct|nov|des|dec)/i.test(k);
    }

    const KNOWN_SEWA_KEYS = new Set([
        'no', 'aksi', 'site_id', 'site_code', 'site_name', 'year', 'tahun_renewal',
        'status_dokumen', 'status', 'status_perpanjangan', 'nomor_pks_baru', 'no_pks_baru',
        'new_period_awal', 'start_date_baru', 'new_period_akhir', 'end_date_baru',
        'harga_baruth', 'harga_baru_th', 'harga_baru', 'total_harga_baru',
        'no_bak_baru', 'tanggal_bak', 'tgl_bak_baru', 'no_sip', 'tgl_terima_sip',
        'penawaran_1', 'nego_1', 'penawaran_2', 'nego_2', 'penawaran_3', 'nego_3',
        'tanggal_penawaran_1', 'tanggal_nego_1', 'tanggal_penawaran_2', 'tanggal_nego_2',
        'tanggal_penawaran_3', 'tanggal_nego_3', 'tgl_penawaran_1', 'tgl_nego_1',
        'tgl_penawaran_2', 'tgl_nego_2', 'tgl_penawaran_3', 'tgl_nego_3',
        'tanggal_negosiasi_1', 'tanggal_negosiasi_2', 'tanggal_negosiasi_3',
        'tgl_negosiasi_1', 'tgl_negosiasi_2', 'tgl_negosiasi_3',
        'nomor_pks_existing', 'no_pks_lama', 'periode_awal_existing', 'start_date_lama',
        'periode_akhir_existing', 'end_date_lama', 'harga_existing_th', 'harga_lama',
        'keterangan', 'update_by', 'tgl_update',
        'area', 'nop', 'ownership', 'vendor', 'tp', 'sitename', 'masa_sewath', 'masa_sewa_th'
    ]);

    function detailField(label, value, wide = false) {
        return `<div class="sewa-detail-field${wide ? ' sewa-detail-wide' : ''}"><dt>${esc(label)}</dt><dd>${esc(value)}</dd></div>`;
    }

    function moneyLabel(value) {
        return value === null || value === undefined || value === '' ? '-' : `Rp ${fmtMoney(value)}`;
    }

    function coordinates(d) {
        const latValue = detailValue(d, 'latitude', 'lat');
        const lonValue = detailValue(d, 'longitude', 'lng', 'lon');
        if (latValue === null || lonValue === null) return null;
        const lat = Number(latValue);
        const lon = Number(lonValue);
        return Number.isFinite(lat) && Number.isFinite(lon) && Math.abs(lat) <= 90 && Math.abs(lon) <= 180
            && (lat !== 0 || lon !== 0) ? { lat, lon } : null;
    }

    function renderSourceDetails(d) {
        const details = d.source_details || {};
        const rows = Object.entries(details)
            .filter(([key, value]) => {
                if (key.startsWith('_') || value === null || value === '') return false;
                // Formula Excel adalah artefak sumber data, bukan informasi
                // yang dapat dibaca pengguna di modal detail.
                if (typeof value === 'string' && value.trim().startsWith('=')) return false;
                const cleanKey = key.toLowerCase().replace(/[\s\/\-]+/g, '_');
                if (isPnlKey(cleanKey)) return false;
                if (['pks_status', 'status_masa_sewa', 'status_masa_sewa2', 'address', 'alamat', 'latitude', 'longitude', 'lat', 'lon', 'lng', 'region', 'kabupaten', 'kecamatan', 'total_harga_existing', 'tindak_lanjut'].includes(cleanKey)) return false;
                if (KNOWN_SEWA_KEYS.has(cleanKey)) return false;
                return true;
            })
            .map(([key, value]) => detailField(key.replaceAll('_', ' '), value))
            .join('');
        return rows ? `<details class="sewa-detail-card sewa-detail-extra"><summary>Kolom tambahan dari sumber data</summary><dl class="sewa-detail-fields">${rows}</dl></details>` : '';
    }

    function sewaIcon(name, tone = '') {
        const paths = {
            status: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m10.5 12 1 1 2-2"/>',
            document: '<path d="M7 3h7l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M14 3v5h5M9 12h6M9 16h6"/>',
            clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            pin: '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
            contract: '<path d="M7 3h10a2 2 0 0 1 2 2v16l-3-2-4 2-4-2-3 2V5a2 2 0 0 1 2-2Z"/><path d="M9 9h6M9 13h6"/>',
            edit: '<path d="M12 20h9M4 16l-.5 4.5L8 20l11-11-4-4L4 16ZM13 7l4 4"/>',
        };
        return `<span class="sewa-detail-icon${tone ? ` is-${tone}` : ''}" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">${paths[name]}</svg></span>`;
    }

    function renderDetail(d) {
        const statusSite = detailValue(d, 'status');
        const address = detailValue(d, 'address', 'alamat');
        const region = detailValue(d, 'region');
        const kabupaten = detailValue(d, 'kabupaten');
        const kecamatan = detailValue(d, 'kecamatan');
        const location = [kecamatan, kabupaten].filter(Boolean).join(', ') || region || 'Lokasi belum tersedia';
        const point = coordinates(d);
        const mapUrl = point ? `https://www.openstreetmap.org/?mlat=${point.lat}&mlon=${point.lon}#map=16/${point.lat}/${point.lon}` : '';
        const stages = [
            ['Penawaran 1', 'penawaran_1'], ['Nego 1', 'nego_1'],
            ['Penawaran 2', 'penawaran_2'], ['Nego 2', 'nego_2'],
            ['Penawaran 3', 'penawaran_3'], ['Nego 3', 'nego_3'],
        ];
        const timeline = stages.map(([label, key]) => {
            const amount = d[key];
            const date = detailValue(d, `tanggal_${key}`);
            const hasAmount = amount !== null && amount !== undefined && amount !== '';
            const recorded = hasAmount || date !== null;
            return `<div class="sewa-detail-step${recorded ? ' is-recorded' : ''}"><strong>${label}</strong><span>${date ? fmtDate(date) : recorded ? 'Tanggal belum tersedia' : 'Belum tercatat'}</span>${hasAmount ? `<span>${esc(moneyLabel(amount))}</span>` : ''}</div>`;
        }).join('');
        const chipClass = value => {
            if (!value) return ' is-neutral';
            if (/off\s*air|non.?operational|non.?aktif|dismantle|unlock|relokasi/i.test(String(value))) return ' is-danger';
            if (/^on\s*air$|^active$|^operational$/i.test(String(value))) return '';
            return ' is-neutral';
        };
        const editButton = d.edit_url
            ? `<button type="button" class="btn btn-sm btn-outline-secondary" data-sewa-edit data-url="${esc(d.edit_url)}">${sewaIcon('edit')} Edit</button>`
            : '';

        return `
        <div class="sewa-detail-intro">
            <div><div class="sewa-detail-site">${esc(d.site_code)}</div><div class="sewa-detail-name">${esc(d.site_name)}</div><div class="sewa-detail-muted">${sewaIcon('pin', 'green')} ${esc(location)}</div></div>
            <span class="sewa-detail-chip${chipClass(statusSite)}">${esc(statusSite || 'Status site belum tersedia')}</span>
        </div>
        <div class="sewa-detail-stats">
            <div class="sewa-detail-stat"><div class="sewa-detail-stat-inner">${sewaIcon('status', 'green')}<div><div class="sewa-detail-label">Status Site</div><div class="sewa-detail-stat-value">${esc(statusSite)}</div></div></div></div>
            <div class="sewa-detail-stat"><div class="sewa-detail-stat-inner">${sewaIcon('document')}<div><div class="sewa-detail-label">Status Dokumen</div><div class="sewa-detail-stat-value">${esc(d.status_dokumen)}</div></div></div></div>
            <div class="sewa-detail-stat"><div class="sewa-detail-stat-inner">${sewaIcon('clock', 'amber')}<div><div class="sewa-detail-label">Status Perpanjangan</div><div class="sewa-detail-stat-value">${esc(d.status_perpanjangan)}</div></div></div></div>
            <div class="sewa-detail-stat"><div class="sewa-detail-stat-inner">${sewaIcon('pin', 'green')}<div><div class="sewa-detail-label">Lokasi Site</div><div class="sewa-detail-stat-value">${esc(location)}</div></div></div></div>
        </div>
        <div class="sewa-detail-grid">
            <div class="sewa-detail-column">
                <section class="sewa-detail-card"><div class="sewa-detail-card-head"><h3>${sewaIcon('document')} Informasi Utama</h3>${editButton}</div><dl class="sewa-detail-fields">
                    ${detailField('Site ID', d.site_code)}${detailField('Vendor', detailValue(d, 'vendor', 'tp'))}
                    ${detailField('Nama Site', d.site_name)}${detailField('Status Site', statusSite)}
                    ${detailField('Tahun Renewal', d.tahun_renewal)}${detailField('Status Dokumen', d.status_dokumen)}
                    ${detailField('Area', detailValue(d, 'area'))}${detailField('Status Perpanjangan', d.status_perpanjangan)}
                    ${detailField('Kepemilikan', detailValue(d, 'ownership'))}${detailField('Masa Sewa', d.lease_duration)}
                    ${detailField('NOP', detailValue(d, 'nop'))}
                </dl></section>
                <section class="sewa-detail-card"><div class="sewa-detail-card-head"><h3>${sewaIcon('contract', 'violet')} PKS Baru / Renewal</h3></div><dl class="sewa-detail-fields">
                    ${detailField('Nomor PKS Baru', d.no_pks_baru, true)}
                    ${detailField('Mulai', fmtDate(d.start_date_baru))}${detailField('Berakhir', fmtDate(d.end_date_baru))}
                    ${detailField('Harga Baru / Tahun', moneyLabel(d.harga_baru))}${detailField('Total Harga Baru', moneyLabel(d.total_harga_baru))}
                </dl></section>
            </div>
            <div class="sewa-detail-column">
                <section class="sewa-detail-card"><div class="sewa-detail-card-head"><h3>${sewaIcon('pin', 'green')} Lokasi Site</h3></div>
                    <div class="sewa-detail-map" data-sewa-map>${point ? `<div><div class="sewa-detail-map-mark" aria-hidden="true">●</div><strong>Koordinat tersedia</strong><div class="sewa-detail-muted">Peta dimuat saat diminta</div><div class="sewa-detail-map-actions"><button type="button" class="btn btn-sm btn-outline-primary" data-sewa-load-map data-lat="${point.lat}" data-lon="${point.lon}">Tampilkan peta</button></div></div>` : '<span class="sewa-detail-muted">Koordinat site belum tersedia</span>'}</div>
                    <dl class="sewa-detail-fields mt-3">
                        ${detailField('Alamat Lengkap', address, true)}${detailField('Region', region)}
                        ${detailField('Kabupaten / Kota', kabupaten)}${detailField('Kecamatan', kecamatan)}
                        ${detailField('Koordinat', point ? `${point.lat}, ${point.lon}` : null, true)}
                    </dl>
                    ${point ? `<a class="btn btn-sm btn-outline-primary mt-2" href="${mapUrl}" target="_blank" rel="noopener noreferrer">Lihat di Maps ↗</a>` : ''}
                </section>
                <section class="sewa-detail-card"><div class="sewa-detail-card-head"><h3>${sewaIcon('contract', 'violet')} PKS Existing</h3></div><dl class="sewa-detail-fields">
                    ${detailField('Nomor PKS Existing', d.no_pks_lama, true)}
                    ${detailField('Mulai', fmtDate(d.start_date_lama))}${detailField('Berakhir', fmtDate(d.end_date_lama))}
                    ${detailField('Harga / Tahun', moneyLabel(d.harga_lama))}${detailField('Total Harga Existing', moneyLabel(detailValue(d, 'total_harga_existing')))}
                </dl></section>
            </div>
        </div>
        <section class="sewa-detail-card mt-3"><div class="sewa-detail-card-head"><h3>${sewaIcon('clock', 'amber')} Proses Perpanjangan</h3></div><div class="sewa-detail-muted">Tahap berwarna menandakan tanggal atau nilai penawaran/negosiasi sudah tercatat di Excel.</div><div class="sewa-detail-timeline">${timeline}</div>
            <details class="sewa-detail-extra"><summary>Dokumen & proses</summary><dl class="sewa-detail-fields">
                ${detailField('Nomor BAK Baru', d.no_bak_baru)}${detailField('Tanggal BAK Baru', fmtDate(d.tgl_bak_baru))}
                ${detailField('Nomor SIP', d.no_sip)}${detailField('Tanggal Terima SIP', fmtDate(d.tgl_terima_sip))}
                ${detailField('Mulai Tahap Proses', fmtDate(d.process_started_at))}${detailField('Lama Proses', d.process_aging_days == null ? null : `${d.process_aging_days} hari`)}
                ${detailField('Tindak Lanjut', detailValue(d, 'tindak_lanjut'), true)}
            </dl></details>
        </section>
        <details class="sewa-detail-card sewa-detail-extra"><summary>Catatan & pembaruan</summary><dl class="sewa-detail-fields">
            ${detailField('Keterangan', d.keterangan, true)}${detailField('Diperbarui oleh', d.update_by)}${detailField('Tanggal Pembaruan', fmtDate(d.tgl_update))}
        </dl></details>
        ${renderSourceDetails(d)}
        <div class="sewa-detail-performance" data-site-performance></div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');
    let currentDetailRow = null;

    function showCurrentDetail() {
        if (!currentDetailRow) return;
        $('#detail-body').html(renderDetail(currentDetailRow));
        window.renderInfrastructureSitePerformance(
            document.querySelector('#detail-body [data-site-performance]'),
            currentDetailRow.performance
        );
    }

    $('#detail-body').on('click', '[data-sewa-edit]', async function () {
        const url = this.dataset.url;
        $('#detail-body').html('<div class="text-center text-body-secondary py-5">Memuat formulir edit...</div>');
        try {
            const response = await fetch(`${url}?modal=1`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error('Formulir edit tidak dapat dibuka. Periksa sesi dan hak akses Anda.');
            const formMarkup = $('<div>').html(await response.text());
            if (!formMarkup.find('[data-sewa-edit-form]').length) throw new Error('Sesi telah berakhir. Muat ulang halaman untuk melanjutkan.');
            formMarkup.find('[id]').each(function () { this.id = `sewa-modal-${this.id}`; });
            formMarkup.find('label[for]').each(function () { this.htmlFor = `sewa-modal-${this.htmlFor}`; });
            $('#detail-body').html(`<div class="sewa-detail-edit-heading"><h2>Edit Sewa Lahan ${esc(currentDetailRow.site_code)}</h2></div><div class="sewa-detail-edit-form"></div>`);
            $('#detail-body .sewa-detail-edit-form').append(formMarkup.contents());
            $('#detail-body').scrollTop(0);
            $('#detail-body [data-sewa-edit-form] input:not([type="hidden"])').first().trigger('focus');
        } catch (error) {
            showCurrentDetail();
            $('#detail-body').prepend(`<div class="alert alert-danger" role="alert">${esc(error.message)}</div>`);
        }
    });

    $('#detail-body').on('click', '[data-sewa-cancel-edit]', showCurrentDetail);

    $('#detail-body').on('submit', '[data-sewa-edit-form]', async function (event) {
        event.preventDefault();
        const form = this;
        const submit = form.querySelector('[type="submit"]');
        const errorBox = form.querySelector('[data-sewa-form-errors]');
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
            $('#detail-body').html(`<div class="alert alert-success mb-0" role="status">${esc(data.message || 'Perubahan berhasil disimpan.')}</div><button type="button" class="btn btn-outline-secondary mt-3" data-bs-dismiss="modal">Tutup</button>`);
            table.ajax.reload(null, false);
        } catch (error) {
            errorBox.textContent = error.message || 'Koneksi terputus. Coba lagi.';
            errorBox.classList.remove('d-none');
        } finally {
            submit.disabled = false;
        }
    });

    $('#detail-body').on('click', '[data-sewa-load-map]', function () {
        const lat = Number(this.dataset.lat);
        const lon = Number(this.dataset.lon);
        if (!Number.isFinite(lat) || !Number.isFinite(lon)) return;
        const delta = 0.008;
        const bbox = [lon - delta, lat - delta, lon + delta, lat + delta].join(',');
        const iframe = document.createElement('iframe');
        iframe.title = 'Peta lokasi site';
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'no-referrer';
        iframe.src = `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${lat}%2C${lon}`;
        $(this).closest('[data-sewa-map]').empty().append(iframe);
    });

    $('#sewa-table tbody').on('click', '.aksi-cell button, .aksi-cell a', function (e) { e.stopPropagation(); });

    $('#sewa-table tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('.aksi-cell').length > 0) return;
        const row = table.row(this).data();
        if (!row) return;
        currentDetailRow = row;
        $('#detail-title').text(row.site_code ?? '');
        $('#detail-body').html(renderDetail(row));
        $('#detailModal').one('shown.bs.modal', function () {
            showCurrentDetail();
        });
        detailModal.show(this);
    });
});
</script>
@endpush

