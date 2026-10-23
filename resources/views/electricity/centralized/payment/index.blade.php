@extends('layouts.app')

@section('title', 'Payment — Electricity Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Electricity — Payment</h1>
            <p class="text-body-secondary mb-0 small">
                Data pembayaran tagihan listrik PLN (Payment Done & Payment Pending).
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-outline-secondary btn-sm">
                Data Listrik PLN
            </a>
            @role('admin')
            <a href="{{ route('electricity.centralized.payment.upload-page') }}" class="btn btn-brand btn-sm">Upload Excel</a>
            @endrole
        </div>
    </div>

    @role('admin')
    <div class="modal fade" id="paymentUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('electricity.centralized.payment.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Upload Payment Done / Pending</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">
                        <p class="small text-body-secondary">Gunakan file sesuai format folder Payment Done atau Payment Pending. Data pada status dan periode yang sama akan diperbarui.</p>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select" required><option value="Done">Done</option><option value="Pending">Pending</option></select></div>
                            <div class="col-md-4"><label class="form-label">Bulan</label><select name="bulan" class="form-select" required>@foreach([1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'] as $n=>$name)<option value="{{ $n }}">{{ $name }}</option>@endforeach</select></div>
                            <div class="col-md-4"><label class="form-label">Tahun</label><input type="number" name="tahun" class="form-control" value="{{ now()->year }}" min="2000" max="2100" required></div>
                        </div>
                        <div class="mt-3"><label class="form-label">File Excel</label><input type="file" name="payment_file" class="form-control" accept=".xls,.xlsx" required></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-brand btn-sm">Upload dan Perbarui</button></div>
                </form>
            </div>
        </div>
    </div>
    @endrole

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Payment Electricity">
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

            {{-- Filter Status (Done / Pending) --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-status" class="form-label mb-0 small fw-semibold text-nowrap">Status:</label>
                <select id="filter-status" class="form-select form-select-sm" style="min-width: 130px;">
                    <option value="">Semua Status</option>
                    <option value="Done">Done</option>
                    <option value="Pending">Pending</option>
                </select>
            </div>

            {{-- Filter Bulan --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-bulan" class="form-label mb-0 small fw-semibold text-nowrap">Bulan:</label>
                <select id="filter-bulan" class="form-select form-select-sm" style="min-width: 130px;">
                    <option value="">Semua Bulan</option>
                    @foreach ([
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ] as $num => $name)
                        <option value="{{ $num }}" @selected($selectedBulan === $num)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Tahun --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-tahun" class="form-label mb-0 small fw-semibold text-nowrap">Tahun:</label>
                <select id="filter-tahun" class="form-select form-select-sm" style="min-width: 110px;">
                    <option value="">Semua Tahun</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($selectedTahun === $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div class="d-flex align-items-center gap-2">
                <label for="filter-nop" class="form-label mb-0 small fw-semibold text-nowrap">NOP:</label>
                <select id="filter-nop" class="form-select form-select-sm" style="min-width: 150px;">
                    <option value="">Semua NOP</option>
                    @foreach ($nops as $nop)
                        <option value="{{ $nop }}" @selected($selectedNop === $nop)>{{ $nop }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Download Excel --}}
            <a href="{{ route('electricity.centralized.payment.export-excel') }}" id="btn-export" class="btn btn-sm btn-outline-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-body">
            <table id="payment-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Pelanggan</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Daya</th>
                        <th>Phasa</th>
                        <th>Gol Tarif</th>
                        <th>Unit PLN</th>
                        <th>Harga</th>
                        <th>Update By</th>
                        <th>Tanggal Status</th>
                        <th>Status</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Pop-up Detail Payment --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Payment PLN — <span id="detail-title" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body">
                    <div class="text-center text-body-secondary py-4">Memuat data...</div>
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
    #payment-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const exportBaseUrl = @json(route('electricity.centralized.payment.export-excel'));

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'id_pelanggan', name: 'id_pelanggan' },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'daya', name: 'daya', className: 'text-end' },
        { data: 'phasa', name: 'phasa' },
        { data: 'gol_tarif', name: 'gol_tarif' },
        { data: 'unit_pln', name: 'unit_pln' },
        { data: 'harga', name: 'harga', className: 'text-end' },
        { data: 'update_by', name: 'update_by' },
        { data: 'tanggal_status', name: 'tanggal_status' },
        { data: 'status', name: 'status', className: 'text-center' },
    ];

    const table = new DataTable('#payment-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.centralized.payment.data')),
            data: function (d) {
                d.status = $('#filter-status').val();
                d.bulan = $('#filter-bulan').val();
                d.tahun = $('#filter-tahun').val();
                d.nop = $('#filter-nop').val();
            }
        },
        columns: columns,
        order: [],
        pageLength: 10,
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    // Auto-reload on filter change
    $('#filter-status, #filter-bulan, #filter-tahun, #filter-nop').on('change', function () {
        table.draw();
        updateExportLink();
    });

    function updateExportLink() {
        const params = new URLSearchParams({
            status: $('#filter-status').val() || '',
            bulan: $('#filter-bulan').val() || '',
            tahun: $('#filter-tahun').val() || '',
            nop: $('#filter-nop').val() || '',
        });
        $('#btn-export').attr('href', exportBaseUrl + '?' + params.toString());
    }

    updateExportLink();

    // Helper formatting
    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    function renderDetail(d) {
        const rawStatus = $('<div>').html(d.status || '').text().trim() || d.status || '-';
        const isDone = String(rawStatus).toLowerCase().includes('done');
        const statusBadge = `<span class="badge ${isDone ? 'bg-success' : 'bg-warning text-dark'}">${esc(rawStatus)}</span>`;

        return `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr>
                    <th>ID Pelanggan</th><td><span class="fw-bold">${esc(d.id_pelanggan)}</span></td>
                    <th>Status Payment</th><td>${statusBadge}</td>
                </tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(d.site_id)}</span></td>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(d.site_name)}</span></td>
                </tr>
                <tr>
                    <th>Daya (VA)</th><td>${esc(d.daya)}</td>
                    <th>Phasa</th><td>${esc(d.phasa)}</td>
                </tr>
                <tr>
                    <th>Golongan Tarif</th><td>${esc(d.gol_tarif)}</td>
                    <th>Unit Layanan PLN</th><td>${esc(d.unit_pln)}</td>
                </tr>
                <tr>
                    <th>Nominal Harga</th><td colspan="3" class="fw-bold text-primary fs-6">${esc(d.harga)}</td>
                </tr>
                <tr>
                    <th>Update By</th><td>${esc(d.update_by)}</td>
                    <th>Tanggal Status</th><td>${esc(d.tanggal_status)}</td>
                </tr>
            </table>
        </div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');

    // Row Click Popup Modal
    $('#payment-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#detail-title').text((row.id_pelanggan ?? '-') + ' (' + (row.site_id ?? '-') + ')');
        $('#detail-body').html(renderDetail(row));
        detailModal.show();
    });
});
</script>
@endpush
