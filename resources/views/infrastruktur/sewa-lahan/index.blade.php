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
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Sewa Lahan <span id="detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body">
                    <div class="text-center text-body-secondary">Memuat...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'sewa-lahan'])
@endsection

@push('styles')
<style>
    #sewa-table tbody tr { cursor: pointer; }
    .detail-block table { margin-bottom: 0; font-size: 0.82rem; }
    .detail-block th { width: 200px; white-space: nowrap; }
    .detail-block .section-title {
        font-weight: 600; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--brand); padding: 0.5rem 0.75rem;
        background: var(--brand-light);
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
        'nomor_pks_existing', 'no_pks_lama', 'periode_awal_existing', 'start_date_lama',
        'periode_akhir_existing', 'end_date_lama', 'harga_existing_th', 'harga_lama',
        'keterangan', 'update_by', 'tgl_update',
        'area', 'nop', 'ownership', 'vendor', 'masa_sewath', 'masa_sewa_th'
    ]);

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
                if (['pks_status', 'status_masa_sewa', 'status_masa_sewa2'].includes(cleanKey)) return false;
                if (KNOWN_SEWA_KEYS.has(cleanKey)) return false;
                return true;
            })
            .map(([key, value]) => `<tr><th>${esc(key.replaceAll('_', ' '))}</th><td colspan="3">${esc(value)}</td></tr>`)
            .join('');
        return rows ? `<table class="table table-bordered table-sm mb-0 mt-2"><tr><td colspan="4" class="section-title">Detail Kolom Tambahan</td></tr>${rows}</table>` : '';
    }

    function renderDetail(d) {
        const details = d.source_details || {};
        const area = detailValue(d, 'area') || '-';
        const nop = detailValue(d, 'nop') || '-';
        const ownership = detailValue(d, 'ownership') || '-';
        const vendor = detailValue(d, 'vendor', 'tp') || '-';
        const masaSewa = d.lease_duration || '-';
        const statusSite = detailValue(d, 'status') || '-';

        return `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">Info Site</td></tr>
                <tr><th>Site ID</th><td>${esc(d.site_code)}</td><th>Site Name</th><td>${esc(d.site_name)}</td></tr>
                <tr><th>Tahun Renewal</th><td>${esc(d.tahun_renewal)}</td><th>Status Dokumen</th><td>${esc(d.status_dokumen)}</td></tr>
                <tr><th>Status Site</th><td>${esc(statusSite)}</td><th>Status Perpanjangan</th><td>${esc(d.status_perpanjangan)}</td></tr>
            </table>
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">Info Lahan & Vendor</td></tr>
                <tr><th>Area</th><td>${esc(area)}</td><th>NOP</th><td>${esc(nop)}</td></tr>
                <tr><th>Ownership</th><td>${esc(ownership)}</td><th>Vendor</th><td>${esc(vendor)}</td></tr>
                <tr><th>Masa Sewa / TH</th><td colspan="3">${esc(masaSewa)}</td></tr>
            </table>
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">PKS Baru</td></tr>
                <tr><th>No PKS Baru</th><td>${esc(d.no_pks_baru)}</td><th>Start Date Baru</th><td>${fmtDate(d.start_date_baru)}</td></tr>
                <tr><th>End Date Baru</th><td>${fmtDate(d.end_date_baru)}</td><th>Harga Baru</th><td>${fmtMoney(d.harga_baru)}</td></tr>
                <tr><th>Total Harga Baru</th><td colspan="3">${fmtMoney(d.total_harga_baru)}</td></tr>
            </table>
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">BAK & SIP</td></tr>
                <tr><th>No BAK Baru</th><td>${esc(d.no_bak_baru)}</td><th>Tgl BAK Baru</th><td>${fmtDate(d.tgl_bak_baru)}</td></tr>
                <tr><th>Mulai Tahap Proses</th><td>${fmtDate(d.process_started_at)}</td><th>Process Aging</th><td>${d.process_aging_days == null ? '-' : esc(d.process_aging_days + ' hari')}</td></tr>
                <tr><th>No SIP</th><td>${esc(d.no_sip)}</td><th>Tgl Terima SIP</th><td>${fmtDate(d.tgl_terima_sip)}</td></tr>
            </table>
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">Penawaran & Negosiasi</td></tr>
                <tr><th>Penawaran 1</th><td>${fmtMoney(d.penawaran_1)}</td><th>Nego 1</th><td>${fmtMoney(d.nego_1)}</td></tr>
                <tr><th>Penawaran 2</th><td>${fmtMoney(d.penawaran_2)}</td><th>Nego 2</th><td>${fmtMoney(d.nego_2)}</td></tr>
                <tr><th>Penawaran 3</th><td>${fmtMoney(d.penawaran_3)}</td><th>Nego 3</th><td>${fmtMoney(d.nego_3)}</td></tr>
            </table>
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">PKS Lama</td></tr>
                <tr><th>No PKS Lama</th><td>${esc(d.no_pks_lama)}</td><th>Start Date Lama</th><td>${fmtDate(d.start_date_lama)}</td></tr>
                <tr><th>End Date Lama</th><td>${fmtDate(d.end_date_lama)}</td><th>Harga Lama</th><td>${fmtMoney(d.harga_lama)}</td></tr>
            </table>
            <table class="table table-bordered table-sm mb-2">
                <tr><td colspan="4" class="section-title">Lain-lain</td></tr>
                <tr><th>Keterangan</th><td colspan="3">${esc(d.keterangan)}</td></tr>
                <tr><th>Update By</th><td>${esc(d.update_by)}</td><th>Tgl Update</th><td>${fmtDate(d.tgl_update)}</td></tr>
            </table>
            ${renderSourceDetails(d)}
            <div class="mt-3" data-site-performance></div>
        </div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');

    $('#sewa-table tbody').on('click', '.aksi-cell button, .aksi-cell a', function (e) { e.stopPropagation(); });

    $('#sewa-table tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('.aksi-cell').length > 0) return;
        const row = table.row(this).data();
        if (!row) return;
        $('#detail-title').text(row.site_code ?? '');
        $('#detail-body').html(renderDetail(row));
        window.renderInfrastructureSitePerformance(
            document.querySelector('#detail-body [data-site-performance]'),
            row.performance
        );
        detailModal.show();
    });
});
</script>
@endpush
