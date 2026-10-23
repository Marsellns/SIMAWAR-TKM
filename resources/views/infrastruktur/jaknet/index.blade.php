@extends('layouts.app')

@section('title', 'Sewa Lahan (Jaknet & Dapot) — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — Sewa Lahan (Jaknet & Dapot)</h1>
        @include('infrastruktur.partials.entry-actions', ['dataset' => 'jaknet'])
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data Sewa Lahan Jaknet &amp; Dapot">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                <input class="form-check-input" type="checkbox" id="toggle-unlock" @checked(! request()->boolean('show_unlock'))>
                <label class="form-check-label small fw-semibold text-nowrap" for="toggle-unlock">Sembunyikan Site Unlock</label>
            </div>
            <a href="{{ route('infrastruktur.jaknet.export-excel') }}" id="btn-export" class="btn btn-sm btn-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="jaknet-table" class="display align-middle text-nowrap" style="width:100%">
                <thead><tr>
                    <th>No</th><th>Site ID</th><th>Site Name</th><th>No. PKS</th>
                    <th>Tanggal Mulai</th><th>Tanggal Berakhir</th><th>Contact Person</th>
                </tr></thead>
            </table>
        </div>
    </div>

    {{-- Modal detail --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Jaknet <span id="detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'jaknet'])
@endsection

@push('styles')
<style>
    #jaknet-table tbody tr { cursor: pointer; }
    .detail-block table { margin-bottom: 0; font-size: 0.82rem; }
    .detail-block th { width: 200px; white-space: nowrap; }
    .detail-block .section-title {
        font-weight: 600; font-size: 0.75rem;
        text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--brand); padding: 0.5rem 0.75rem;
        background: var(--brand-light);
    }
    .form-check-input:checked { background-color: var(--brand); border-color: var(--brand); }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const exportBaseUrl = @json(route('infrastruktur.jaknet.export-excel'));

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_code', name: 'site_code' },
        { data: 'site_name', name: 'site_name' },
        { data: 'no_pks', name: 'no_pks' },
        { data: 'tanggal_mulai', name: 'tanggal_mulai',
          render: (d) => (!d || d === '-') ? '<span class="badge text-bg-secondary">-</span>' : d },
        { data: 'tanggal_berakhir', name: 'tanggal_berakhir',
          render: (d) => (!d || d === '-') ? '<span class="badge text-bg-secondary">-</span>' : d },
        { data: 'contact_person', name: 'contact_person' },
    ];

    const table = new DataTable('#jaknet-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('infrastruktur.jaknet.data')),
                data: (d) => { d.hide_unlock = $('#toggle-unlock').is(':checked') ? 1 : 0; } },
        columns,
        order: [],
        pageLength: 10,
    });

    $('#toggle-unlock').on('change', function () {
        table.ajax.reload();
        $('#btn-export').attr('href', exportBaseUrl + '?hide_unlock=' + ($(this).is(':checked') ? 1 : 0));
    });
    $('#btn-export').attr('href', exportBaseUrl + '?hide_unlock=1');
    $('#page-size').on('change', function () { table.page.len(parseInt($(this).val())).draw(); });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }
    function fmtDate(v) { if (!v) return '-'; const d = new Date(v); return isNaN(d) ? esc(v) : d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); }

    function renderDetail(d) {
        return `<div class="detail-block">
            <table class="table table-bordered table-sm">
                <tr><td colspan="4" class="section-title">Detail Kontrak</td></tr>
                <tr><th>Contact Address</th><td colspan="3">${esc(d.contact_address)}</td></tr>
                <tr><th>Telp</th><td>${esc(d.telp)}</td><th>Tahun Berakhir</th><td>${esc(d.tahun_berakhir)}</td></tr>
                <tr><th>Nilai</th><td>${esc(d.nilai)}</td><th>Nilai/Thn</th><td>${esc(d.nilai_per_tahun)}</td></tr>
                <tr><th>Tgl Update</th><td>${fmtDate(d.tgl_update)}</td>
                    <th>Site Unlock</th><td>${d.is_site_unlock ? '<span class="badge text-bg-warning">Ya</span>' : '<span class="badge text-bg-secondary">Tidak</span>'}</td></tr>
            </table>
        </div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');

    $('#jaknet-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;
        $('#detail-title').text(row.site_code ?? '');
        $('#detail-body').html(renderDetail(row));
        detailModal.show();
    });
});
</script>
@endpush
