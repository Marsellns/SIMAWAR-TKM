@extends('layouts.app')

@section('title', 'Payment IBC — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Payment IBC</h1>
            <p class="text-body-secondary mb-0 small">
                Data pembayaran tagihan listrik inbuilding gabungan (Status: Done & Pending).
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'input-ibc'])
            <a href="{{ route('electricity.inbuilding.listrik-inbuilding.index') }}" class="btn btn-outline-secondary btn-sm">
                Master Listrik Inbuilding
            </a>
            <a href="#" id="btn-export" class="btn btn-success btn-sm d-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Payment IBC">
        <div class="card-body py-2 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                {{-- Page Size --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                    <select id="page-size" class="form-select form-select-sm" style="width:auto">
                        @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                            <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Status (Done / Pending) --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="filter-status" class="form-label mb-0 small fw-semibold text-nowrap">Status:</label>
                    <select id="filter-status" class="form-select form-select-sm" style="min-width: 140px;">
                        <option value="">Semua Status</option>
                        <option value="Done" @selected(request('status') === 'Done')>Done</option>
                        <option value="Pending" @selected(request('status') === 'Pending')>Pending</option>
                    </select>
                </div>

                {{-- Filter Bulan --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="filter-bulan" class="form-label mb-0 small fw-semibold text-nowrap">Bulan:</label>
                    <select id="filter-bulan" class="form-select form-select-sm" style="width:auto">
                        <option value="">Semua Bulan</option>
                        @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $num => $nama)
                            <option value="{{ $num }}" @selected((string) request('bulan') === (string) $num)>{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Tahun --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="filter-tahun" class="form-label mb-0 small fw-semibold text-nowrap">Tahun:</label>
                    <select id="filter-tahun" class="form-select form-select-sm" style="width:auto">
                        <option value="">Semua Tahun</option>
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}" @selected($yr == request('tahun', $years[0] ?? now()->year))>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="text-body-secondary small">
                Total: <span id="total-rows-info" class="fw-semibold text-dark">-</span> data
            </div>
        </div>
    </div>

    {{-- Main DataTable --}}
    <div class="card">
        <div class="card-body">
            <table id="payment-ibc-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Nama BM</th>
                        <th>Daya</th>
                        <th>Status</th>
                        <th>Jumlah Tagihan</th>
                        <th>Invoice</th>
                        <th>Update By</th>
                        <th>Tanggal Update Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Detail Popup --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Payment IBC — <span id="detail-title" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body">
                    <div class="text-center text-body-secondary py-3">Memuat detail...</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    #payment-ibc-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const exportBaseUrl = @json(route('electricity.inbuilding.payment.export-excel'));

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'nama_bm', name: 'nama_bm' },
        { data: 'daya', name: 'daya', className: 'text-end' },
        { data: 'status', name: 'status', className: 'text-center' },
        { data: 'jumlah_tagihan', name: 'jumlah_tagihan', className: 'text-end fw-semibold' },
        { data: 'invoice', name: 'invoice' },
        { data: 'update_by', name: 'update_by' },
        { data: 'tanggal_update_status', name: 'tanggal_update_status', className: 'text-center' },
    ];

    const table = new DataTable('#payment-ibc-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.inbuilding.payment.data')),
            data: function (d) {
                d.status = $('#filter-status').val();
                d.bulan = $('#filter-bulan').val();
                d.tahun = $('#filter-tahun').val();
            }
        },
        columns: columns,
        order: [],
        pageLength: 10,
        drawCallback: function (settings) {
            $('#total-rows-info').text(settings.fnRecordsTotal());
        }
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    $('#filter-status, #filter-bulan, #filter-tahun').on('change', function () {
        table.draw();
        updateExportLink();
    });

    function updateExportLink() {
        const params = new URLSearchParams({
            status: $('#filter-status').val() || '',
            bulan: $('#filter-bulan').val() || '',
            tahun: $('#filter-tahun').val() || '',
        });
        $('#btn-export').attr('href', exportBaseUrl + '?' + params.toString());
    }

    updateExportLink();

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    function renderDetail(d) {
        const rawStatus = $('<div>').html(d.status || '').text().trim() || d.status || '-';
        const isDone = String(rawStatus).toLowerCase().includes('done');
        const statusBadge = `<span class="badge ${isDone ? 'badge-profit' : 'badge-anomaly'}">${esc(rawStatus)}</span>`;

        return `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(d.site_id)}</span></td>
                    <th>Status Payment</th><td>${statusBadge}</td>
                </tr>
                <tr>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(d.site_name)}</span></td>
                    <th>Nama BM</th><td>${esc(d.nama_bm)}</td>
                </tr>
                <tr>
                    <th>Daya</th><td>${esc(d.daya)}</td>
                    <th>Invoice</th><td>${esc(d.invoice)}</td>
                </tr>
                <tr>
                    <th>Jumlah Tagihan</th><td colspan="3" class="fw-bold text-primary fs-6">${esc(d.jumlah_tagihan)}</td>
                </tr>
                <tr>
                    <th>Update By</th><td>${esc(d.update_by)}</td>
                    <th>Tanggal Update Status</th><td>${esc(d.tanggal_update_status)}</td>
                </tr>
            </table>
        </div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');

    $('#payment-ibc-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#detail-title').text((row.site_id ?? '-') + ' (' + (row.site_name ?? '-') + ')');
        $('#detail-body').html(renderDetail(row));
        detailModal.show();
    });
});
</script>
@endpush
