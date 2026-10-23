@extends('layouts.app')

@section('title', 'Bongkar Rampung — Electricity Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Electricity — Bongkar Rampung</h1>
            <p class="text-body-secondary mb-0 small">
                Daftar site dan pelanggan PLN berstatus Bongkar Rampung (Boram).
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-outline-secondary btn-sm">
                Data Listrik PLN
            </a>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data Bongkar Rampung">
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

            @role('admin')
            <button type="button" class="btn btn-brand btn-sm" data-bs-toggle="modal" data-bs-target="#modalTambahBoram">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/></svg>
                Tambah Data Boram
            </button>
            <a href="{{ route('electricity.centralized.bongkar-rampung-mandiri.upload-page') }}" class="btn btn-outline-brand btn-sm">Upload Excel</a>
            @endrole

            {{-- Download Excel --}}
            <a href="{{ route('electricity.centralized.bongkar-rampung-mandiri.export-excel') }}" class="btn btn-sm btn-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>

        @role('admin')
        <div class="modal fade" id="boramUploadModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('electricity.centralized.bongkar-rampung-mandiri.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Upload Bongkar Rampung</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body"><p class="small text-body-secondary">Upload file sesuai format Dataset/03 Electricity/Centralized/Bongkar Rampung. Data lama akan diganti dengan file terbaru.</p><input type="file" name="boram_file" class="form-control" accept=".xls,.xlsx" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-brand btn-sm">Upload dan Perbarui</button></div>
            </form>
        </div></div></div>
        @endrole
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-body">
            <table id="boram-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        @role('admin')
                            <th>Aksi</th>
                        @endrole
                        <th>ID Pelanggan</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Tambah Boram --}}
    <div class="modal fade" id="modalTambahBoram" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('electricity.centralized.bongkar-rampung-mandiri.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Data Bongkar Rampung</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ID Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" name="id_pelanggan" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Site ID <span class="text-danger">*</span></label>
                            <input type="text" name="site_id" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Site Name</label>
                            <input type="text" name="site_name" class="form-control form-control-sm">
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

    {{-- Modal Edit Boram --}}
    <div class="modal fade" id="modalEditBoram" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="formEditBoram" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Data Bongkar Rampung</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">ID Pelanggan <span class="text-danger">*</span></label>
                            <input type="text" id="edit-id_pelanggan" name="id_pelanggan" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Site ID <span class="text-danger">*</span></label>
                            <input type="text" id="edit-site_id" name="site_id" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Site Name</label>
                            <input type="text" id="edit-site_name" name="site_name" class="form-control form-control-sm">
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

    {{-- Modal Detail Boram --}}
    <div class="modal fade" id="modalDetailBoram" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Site Bongkar Rampung — <span id="detail-site-title" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-boram-body">
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
    #boram-table tbody tr { cursor: pointer; }
    #modalDetailBoram .modal-dialog { width: min(75vw, 1200px); max-width: 75vw; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const updateUrlBase = @json(url('electricity/centralized/bongkar-rampung-mandiri'));

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];
    if (isAdmin) {
        columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false });
    }
    columns.push(
        { data: 'id_pelanggan', name: 'id_pelanggan' },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
    );

    const table = new DataTable('#boram-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('electricity.centralized.bongkar-rampung-mandiri.data')) },
        columns: columns,
        order: [],
        pageLength: 10,
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    const modalDetailBoram = new bootstrap.Modal('#modalDetailBoram');

    // Row Click Popup Modal (Mencegah klik tombol aksi)
    $('#boram-table tbody').on('click', '.btn-edit-boram, form, button, a', function (e) {
        e.stopPropagation();
    });

    $('#boram-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#detail-site-title').text((row.site_id ?? '-') + ' (' + (row.site_name ?? '-') + ')');

        const html = `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Informasi Site Bongkar Rampung (Boram)</td></tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(row.site_id)}</span></td>
                    <th>Status Site</th><td><span class="badge badge-loss">Bongkar Rampung</span></td>
                </tr>
                <tr>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(row.site_name)}</span></td>
                    <th>ID Pelanggan</th><td><span class="fw-bold">${esc(row.id_pelanggan)}</span></td>
                </tr>
            </table>

            <div class="alert alert-danger border-danger mb-0 py-2">
                <div class="fw-semibold small mb-1">Status Terminasi / Pembongkaran:</div>
                <div class="small">
                    Site ini telah didaftarkan dalam kategori <strong>Bongkar Rampung (Boram)</strong>. Tagihan dan pencatatan operasional PLN telah dihentikan/dinonaktifkan secara permanen.
                </div>
            </div>
        </div>`;

        $('#detail-boram-body').html(html);
        modalDetailBoram.show();
    });

    // Handle Edit Button
    const modalEditBoram = new bootstrap.Modal('#modalEditBoram');
    $('#boram-table tbody').on('click', '.btn-edit-boram', function () {
        const btn = $(this);
        const id = btn.data('id');
        const idPelanggan = btn.data('id_pelanggan');
        const siteId = btn.data('site_id');
        const siteName = btn.data('site_name');

        $('#formEditBoram').attr('action', updateUrlBase + '/' + id);
        $('#edit-id_pelanggan').val(idPelanggan);
        $('#edit-site_id').val(siteId);
        $('#edit-site_name').val(siteName || '');

        modalEditBoram.show();
    });
});
</script>
@endpush
