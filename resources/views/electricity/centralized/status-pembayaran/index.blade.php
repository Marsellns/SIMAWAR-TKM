@extends('layouts.app')

@section('title', 'Status Pembayaran — ' . $listrikPln->id_pelanggan . ' — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="text-decoration-none">Listrik PLN</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Status Pembayaran</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">Status Pembayaran PLN — <span class="text-brand fw-bold">{{ $listrikPln->id_pelanggan }}</span></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('electricity.centralized.listrik-pln.grafik', $listrikPln) }}" class="btn btn-outline-brand btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.707l-4.146 4.147a.5.5 0 0 1-.708 0L7 6.707l-3.646 3.647a.5.5 0 0 1-.708-.708l4-4a.5.5 0 0 1 .708 0L9.5 7.793 13.293 4H10.5a.5.5 0 0 1-.5-.5Z"/></svg>
                Lihat Grafik Tagihan
            </a>
            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-outline-secondary btn-sm">
                ← Kembali ke Listrik PLN
            </a>
        </div>
    </div>

    {{-- Customer Summary Card --}}
    <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);">
        <div class="card-body py-3">
            <div class="row g-3 text-sm">
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Site ID & Name</span>
                    <strong class="text-dark">{{ $listrikPln->site_id }}</strong> — {{ $listrikPln->site_name }}
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Nama Pelanggan</span>
                    <strong class="text-dark">{{ $listrikPln->nama_pelanggan ?? '-' }}</strong>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Daya / Phasa / Tarif</span>
                    <strong class="text-dark">{{ $listrikPln->daya_va ? number_format($listrikPln->daya_va) . ' VA' : '-' }}</strong> | {{ $listrikPln->phasa ?? '-' }} ({{ $listrikPln->gol_tarif ?? '-' }})
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Unit Layanan PLN</span>
                    <strong class="text-dark">{{ $listrikPln->unit_layanan_pln ?? '-' }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data Status Pembayaran">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            @role('admin')
            <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahStatus">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                Tambah Status Pembayaran
            </button>
            @endrole
            <a href="{{ route('electricity.centralized.listrik-pln.status-pembayaran.export-excel', $listrikPln) }}" class="btn btn-sm btn-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Tabel Utama Sub-Fitur --}}
    <div class="card">
        <div class="card-body">
            <table id="status-pembayaran-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        @role('admin')
                            <th>Aksi</th>
                        @endrole
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>Harga</th>
                        <th>Remark</th>
                        <th>Update By</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Tambah Status Pembayaran --}}
    <div class="modal fade" id="modalTambahStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('electricity.centralized.listrik-pln.status-pembayaran.store', $listrikPln) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Status Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status-periode" class="form-label fw-semibold">Bulan & Tahun <span class="text-danger">*</span></label>
                            <input type="month" id="status-periode" class="form-control form-control-sm"
                                value="{{ date('Y-m') }}" required>
                            <input type="hidden" name="bulan" value="{{ date('n') }}">
                            <input type="hidden" name="tahun" value="{{ date('Y') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="harga" class="form-control form-control-sm" placeholder="Contoh: 15000000" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Remark / Status</label>
                            <input type="text" name="remark" class="form-control form-control-sm" placeholder="Contoh: Lunas / Terbayar via Bank">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand btn-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modal Edit Status Pembayaran --}}
    <div class="modal fade" id="modalEditStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditStatus" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Status Pembayaran (<span id="edit-periode-title"></span>)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Harga (Rp) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" id="edit-harga" name="harga" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Remark / Status</label>
                            <input type="text" id="edit-remark" name="remark" class="form-control form-control-sm">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tanggal</label>
                            <input type="date" id="edit-tanggal" name="tanggal" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand btn-sm">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const updateUrlBase = @json(url('electricity/centralized/status-pembayaran'));

    const namaBulanMap = {
        1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
        5: 'Mei', 6: 'Juni', 7 => 'Juli', 8: 'Agustus',
        9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember'
    };

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];
    if (isAdmin) {
        columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false });
    }
    columns.push(
        { data: 'bulan', name: 'bulan' },
        { data: 'tahun', name: 'tahun' },
        { data: 'harga', name: 'harga', className: 'text-end' },
        { data: 'remark', name: 'remark' },
        { data: 'update_by', name: 'update_by' },
        { data: 'tanggal', name: 'tanggal' },
    );

    const table = new DataTable('#status-pembayaran-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('electricity.centralized.listrik-pln.status-pembayaran.data', $listrikPln)) },
        columns: columns,
        order: [],
        pageLength: 10,
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    // Handle Edit Button Click
    const modalEditStatus = new bootstrap.Modal('#modalEditStatus');
    $('#status-pembayaran-table tbody').on('click', '.btn-edit-sp', function () {
        const btn = $(this);
        const id = btn.data('id');
        const bulan = btn.data('bulan');
        const tahun = btn.data('tahun');
        const harga = btn.data('harga');
        const remark = btn.data('remark');
        const tanggal = btn.data('tanggal');

        $('#formEditStatus').attr('action', updateUrlBase + '/' + id);
        $('#edit-periode-title').text((namaBulanMap[bulan] || ('Bulan ' + bulan)) + ' ' + tahun);
        $('#edit-harga').val(harga);
        $('#edit-remark').val(remark || '');
        $('#edit-tanggal').val(tanggal || '');

        modalEditStatus.show();
    });
});
</script>
<script>
$(function () {
    $('#status-periode').on('change', function () {
        const [tahun, bulan] = this.value.split('-');
        $('input[name="tahun"]').val(tahun);
        $('input[name="bulan"]').val(Number(bulan));
    });
});
</script>
@endpush
