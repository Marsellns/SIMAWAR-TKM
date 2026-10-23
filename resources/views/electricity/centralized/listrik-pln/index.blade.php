@extends('layouts.app')

@section('title', 'Listrik PLN — Electricity Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">Electricity — Listrik PLN</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'listrik-pln'])
        <a href="{{ route('electricity.centralized.upload-flagging') }}" class="btn btn-outline-brand btn-sm">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
            Upload Data Flagging
        </a>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Listrik PLN">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="filter-year" class="form-label mb-0 small fw-semibold text-nowrap">Pilih Tahun:</label>
                <select id="filter-year" class="form-select form-select-sm" style="min-width: 105px;">
                    <option value="">Semua Tahun</option>
                    @foreach ($years as $year)
                        <option value="{{ $year }}" @selected(isset($selectedTahun) && $selectedTahun == $year)>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="filter-month" class="form-label mb-0 small fw-semibold text-nowrap">Pilih Bulan:</label>
                <select id="filter-month" class="form-select form-select-sm" style="min-width: 135px;">
                    <option value="">Semua Bulan</option>
                    @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $month => $monthName)
                        <option value="{{ $month }}" @selected(isset($selectedBulan) && $selectedBulan == $month)>{{ $monthName }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="filter-nop" class="form-label mb-0 small fw-semibold text-nowrap">NOP:</label>
                <select id="filter-nop" class="form-select form-select-sm" style="min-width: 170px;">
                    <option value="">Semua NOP</option>
                    @foreach ($nops as $nop)
                        <option value="{{ $nop }}" @selected(isset($selectedNop) && $selectedNop === $nop)>NOP {{ $nop }}</option>
                    @endforeach
                </select>
            </div>
            <a href="{{ route('electricity.centralized.listrik-pln.export-excel') }}" id="btn-export" class="btn btn-sm btn-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="listrik-pln-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        @role('admin')
                            <th>Aksi</th>
                        @endrole
                        <th>ID Pelanggan</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Alamat</th>
                        <th>Status Aktif Site</th>
                        <th>Nama Pelanggan</th>
                        <th>Daya (VA)</th>
                        <th>Phasa</th>
                        <th>Status AMR</th>
                        <th>Gol Tarif</th>
                        <th>Unit Layanan PLN</th>
                        <th>Jenis Bayar</th>
                        <th>TP Owner</th>
                        <th>NOP</th>
                        <th>Update By</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Detail Listrik PLN (Pop-up saat baris tabel diklik) --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Listrik PLN — <span id="detail-title" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="detail-body">
                    <div class="text-center text-body-secondary py-4">Memuat data...</div>
                </div>
                <div class="modal-footer">
                    <div id="detail-actions" class="me-auto d-flex gap-2"></div>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    #listrik-pln-table tbody tr { cursor: pointer; }
    .aksi-cell { white-space: nowrap; }
    .aksi-cell .btn { padding: 0.2rem 0.5rem; font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const exportBaseUrl = @json(route('electricity.centralized.listrik-pln.export-excel'));
    const editUrlTemplate = @json(url('electricity/centralized/listrik-pln/__ID__/edit'));
    const boramUrlTemplate = @json(url('electricity/centralized/listrik-pln/__ID__/bongkar-rampung/create'));

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
        { data: 'alamat', name: 'alamat' },
        {
            data: 'status_aktif_site',
            name: 'status_aktif_site',
            render: function(d) {
                if (!d || d === '-') return '-';
                const isAktif = String(d).toLowerCase().includes('aktif') && !String(d).toLowerCase().includes('tidak');
                return `<span class="badge ${isAktif ? 'badge-aktif' : 'badge-nonaktif'}">${$('<div>').text(d).html()}</span>`;
            }
        },
        { data: 'nama_pelanggan', name: 'nama_pelanggan' },
        { data: 'daya_va', name: 'daya_va', className: 'text-end' },
        { data: 'phasa', name: 'phasa' },
        { data: 'status_amr', name: 'status_amr' },
        { data: 'gol_tarif', name: 'gol_tarif' },
        { data: 'unit_layanan_pln', name: 'unit_layanan_pln' },
        { data: 'jenis_bayar', name: 'jenis_bayar' },
        { data: 'tp_owner', name: 'tp_owner' },
        { data: 'nop', name: 'nop' },
        { data: 'update_by', name: 'update_by' },
        { data: 'tanggal', name: 'tanggal' },
    );

    const table = new DataTable('#listrik-pln-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.centralized.listrik-pln.data')),
            data: function (d) {
                d.tahun = $('#filter-year').val();
                d.bulan = $('#filter-month').val();
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

    function updateExportUrl() {
        const params = new URLSearchParams();
        const tahun = $('#filter-year').val();
        const bulan = $('#filter-month').val();
        const nop = $('#filter-nop').val();
        if (tahun) params.set('tahun', tahun);
        if (bulan) params.set('bulan', bulan);
        if (nop) params.set('nop', nop);
        $('#btn-export').attr('href', `${exportBaseUrl}?${params.toString()}`);
    }

    updateExportUrl();

    $('#filter-year, #filter-month, #filter-nop').on('change', function () {
        updateExportUrl();
        table.draw();
    });

    // Helper formatting
    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    function renderDetail(d) {
        const isAktif = String(d.status_aktif_site || '').toLowerCase().includes('aktif') && !String(d.status_aktif_site || '').toLowerCase().includes('tidak');
        const statusBadge = `<span class="badge ${isAktif ? 'badge-aktif' : 'badge-nonaktif'}">${esc(d.status_aktif_site)}</span>`;
        const alamatFull = d.alamat_full && d.alamat_full !== '-' ? d.alamat_full : (d.alamat ?? '-');

        return `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Informasi Site & Pelanggan PLN</td></tr>
                <tr>
                    <th>ID Pelanggan</th><td><span class="fw-bold">${esc(d.id_pelanggan)}</span></td>
                    <th>Status Aktif Site</th><td>${statusBadge}</td>
                </tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(d.site_id)}</span></td>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(d.site_name)}</span></td>
                </tr>
                <tr>
                    <th>Nama Pelanggan</th><td colspan="3">${esc(d.nama_pelanggan)}</td>
                </tr>
                <tr>
                    <th>Alamat Lengkap</th><td colspan="3">${esc(alamatFull)}</td>
                </tr>
            </table>

            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Spesifikasi Daya & Layanan PLN</td></tr>
                <tr>
                    <th>Daya (VA)</th><td><span class="fw-semibold">${esc(d.daya_va)} VA</span></td>
                    <th>Phasa</th><td>${esc(d.phasa)}</td>
                </tr>
                <tr>
                    <th>Golongan Tarif</th><td><span class="badge text-bg-secondary">${esc(d.gol_tarif)}</span></td>
                    <th>Status AMR</th><td>${esc(d.status_amr)}</td>
                </tr>
                <tr>
                    <th>Unit Layanan PLN</th><td>${esc(d.unit_layanan_pln)}</td>
                    <th>Jenis Bayar</th><td>${esc(d.jenis_bayar)}</td>
                </tr>
            </table>

            <table class="table table-bordered table-sm mb-0">
                <tr><td colspan="4" class="section-title">Administrasi & Pencatatan</td></tr>
                <tr>
                    <th>TP Owner</th><td>${esc(d.tp_owner)}</td>
                    <th>NOP (Objek Pajak)</th><td>${esc(d.nop)}</td>
                </tr>
                <tr>
                    <th>Update By</th><td>${esc(d.update_by)}</td>
                    <th>Tanggal Update</th><td>${esc(d.tanggal)}</td>
                </tr>
            </table>
        </div>`;
    }

    const detailModal = new bootstrap.Modal('#detailModal');

    // Mencegah klik tombol aksi / link / form memicu modal
    $('#listrik-pln-table tbody').on('click', '.aksi-cell, .aksi-cell a, .aksi-cell button, button, a', function (e) {
        e.stopPropagation();
    });

    const statusPembayaranUrlTemplate = @json(url('electricity/centralized/listrik-pln/__ID__/status-pembayaran'));
    const grafikUrlTemplate = @json(url('electricity/centralized/listrik-pln/__ID__/grafik'));

    // Klik pada baris mana saja untuk menampilkan Pop-up Detail
    $('#listrik-pln-table tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('.aksi-cell, button, a').length > 0) return;
        const row = table.row(this).data();
        if (!row) return;

        $('#detail-title').text((row.id_pelanggan ?? '-') + ' (' + (row.site_id ?? '-') + ' — ' + (row.site_name ?? '-') + ')');
        $('#detail-body').html(renderDetail(row));

        if (row.id) {
            const spUrl = statusPembayaranUrlTemplate.replace('__ID__', row.id);
            const grUrl = grafikUrlTemplate.replace('__ID__', row.id);
            const editUrl = editUrlTemplate.replace('__ID__', row.id);
            const boramUrl = boramUrlTemplate.replace('__ID__', row.id);

            let actionsHtml = `
                <a href="${spUrl}" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M3 4h10v2H3V4zm0 4h10v2H3V8zm0 4h7v2H3v-2z"/></svg>
                    Status Pembayaran
                </a>
                <a href="${grUrl}" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.707l-4.146 4.147a.5.5 0 0 1-.708 0L7 6.707l-3.646 3.647a.5.5 0 0 1-.708-.708l4-4a.5.5 0 0 1 .708 0L9.5 7.793 13.293 4H10.5a.5.5 0 0 1-.5-.5Z"/></svg>
                    Grafik Tagihan
                </a>
            `;

            if (isAdmin) {
                actionsHtml += `
                    <a href="${editUrl}" class="btn btn-warning btn-sm">Edit Data</a>
                    <a href="${boramUrl}" class="btn btn-outline-brand btn-sm">+ Tambah Boram</a>
                `;
            }

            $('#detail-actions').html(actionsHtml);
        } else {
            $('#detail-actions').empty();
        }

        detailModal.show();
    });
});
</script>
@endpush
