@extends('layouts.app')

@section('title', 'Recurring (ANT & Ipas) — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — Recurring (ANT & Ipas)</h1>
        @include('infrastruktur.partials.entry-actions', ['dataset' => 'recurring-ipas'])
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data Recurring ANT &amp; IPAS">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('infrastruktur.recurring-ipas.export-excel') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 text-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                    Excel
                </a>
                <a href="{{ route('infrastruktur.recurring-ipas.export-csv') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 text-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                    CSV
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="recurring-table" class="display align-middle text-nowrap" style="width:100%">
                <thead><tr>
                    <th>No</th><th>Site ID</th><th>Site Name</th><th>Alamat</th>
                </tr></thead>
            </table>
        </div>
    </div>

    {{-- Modal detail --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Recurring <span id="detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'recurring-ipas'])
@endsection

@push('styles')
<style>
    #recurring-table tbody tr { cursor: pointer; }
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
    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_code', name: 'site_code' },
        { data: 'site_name', name: 'site_name' },
        { data: 'alamat', name: 'alamat' },
    ];

    const table = new DataTable('#recurring-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('infrastruktur.recurring-ipas.data')) },
        columns,
        order: [],
        pageLength: 10,
    });

    $('#page-size').on('change', function () { table.page.len(parseInt($(this).val())).draw(); });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }
    function fmtMoney(v) { return v == null || v === '' ? '-' : new Intl.NumberFormat('id-ID').format(v); }
    function fmtDate(v) { if (!v) return '-'; const d = new Date(v); return isNaN(d) ? esc(v) : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }

    function renderDetail(d) {
        return `<div class="detail-block">
            <table class="table table-bordered table-sm">
                <tr><td colspan="4" class="section-title">Detail Contract</td></tr>
                <tr><th>Contract Description</th><td colspan="3">${esc(d.contract_description)}</td></tr>
                <tr><th>Source ID</th><td>${esc(d.source_id)}</td><th>SOW ID</th><td>${esc(d.sow_id)}</td></tr>
                <tr><th>SOW Detail</th><td colspan="3">${esc(d.sow_detail)}</td></tr>
                <tr><th>Year Amount</th><td>${fmtMoney(d.year_amount)}</td><th>Start Date</th><td>${fmtDate(d.start_date)}</td></tr>
                <tr><th>End Date</th><td>${fmtDate(d.end_date)}</td><th></th><td></td></tr>
            </table>
        </div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');

    $('#recurring-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;
        $('#detail-title').text(row.site_code ?? '');
        $('#detail-body').html(renderDetail(row));
        detailModal.show();
    });
});
</script>
@endpush
