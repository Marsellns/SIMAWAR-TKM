@extends('layouts.app')

@section('title', 'Anomali Tagihan — Electricity Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Electricity — Anomali Tagihan</h1>
            <p class="text-body-secondary mb-0 small">
                Deteksi anomali tagihan listrik PLN dengan lonjakan kenaikan di atas ambang batas 50% (> 50%).
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-outline-secondary btn-sm">
                Data Listrik PLN
            </a>
            @role('admin')
            <a href="{{ route('electricity.centralized.anomali.upload-page') }}" class="btn btn-brand btn-sm">Upload Excel</a>
            @endrole
        </div>

        @role('admin')
        <div class="modal fade" id="anomaliUploadModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
                <form method="POST" action="{{ route('electricity.centralized.anomali.upload') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header"><h5 class="modal-title">Upload Anomali Tagihan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body"><p class="small text-body-secondary">Upload file sesuai format folder Dataset/03 Electricity/Centralized/Anomali Tagihan. Data lama akan diganti dengan file terbaru.</p><input type="file" name="anomali_file" class="form-control" accept=".xls,.xlsx" required></div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-brand btn-sm">Upload dan Perbarui</button></div>
                </form>
            </div></div>
        </div>
        @endrole
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Anomali Tagihan">
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
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Filter Tahun --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-tahun" class="form-label mb-0 small fw-semibold text-nowrap">Tahun:</label>
                <select id="filter-tahun" class="form-select form-select-sm" style="min-width: 110px;">
                    <option value="">Semua Tahun</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 small">
                Threshold: Kenaikan &gt; 50%
            </span>

            {{-- Download Excel --}}
            <a href="{{ route('electricity.centralized.anomali.export-excel') }}" id="btn-export" class="btn btn-sm btn-success ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-body">
            <table id="anomali-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Pelanggan</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Bulan</th>
                        <th>Tahun</th>
                        <th>Tagihan Sebelumnya</th>
                        <th>Tagihan Saat Ini</th>
                        <th>Selisih</th>
                        <th>Kenaikan (%)</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Detail Anomali --}}
    <div class="modal fade" id="anomaliDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Penjelasan Detail Anomali Tagihan — <span id="anomali-site-title" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="anomali-modal-body">
                    <div class="text-center text-body-secondary py-3">Memuat detail anomali...</div>
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
    #anomali-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const exportBaseUrl = @json(route('electricity.centralized.anomali.export-excel'));

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'id_pelanggan', name: 'id_pelanggan' },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'bulan', name: 'bulan' },
        { data: 'tahun', name: 'tahun' },
        { data: 'tagihan_sebelumnya', name: 'tagihan_sebelumnya', className: 'text-end' },
        { data: 'tagihan_saat_ini', name: 'tagihan_saat_ini', className: 'text-end fw-semibold' },
        { data: 'selisih', name: 'selisih', className: 'text-end text-danger' },
        { data: 'kenaikan_persen', name: 'kenaikan_persen', className: 'text-center' },
    ];

    const table = new DataTable('#anomali-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.centralized.anomali.data')),
            data: function (d) {
                d.bulan = $('#filter-bulan').val();
                d.tahun = $('#filter-tahun').val();
            }
        },
        columns: columns,
        order: [],
        pageLength: 10,
    });

    function updateExportUrl() {
        const params = new URLSearchParams();
        const bulan = $('#filter-bulan').val();
        const tahun = $('#filter-tahun').val();

        if (bulan) params.append('bulan', bulan);
        if (tahun) params.append('tahun', tahun);

        const fullUrl = exportBaseUrl + (params.toString() ? '?' + params.toString() : '');
        $('#btn-export, #btn-export-header').attr('href', fullUrl);
    }

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    $('#filter-bulan, #filter-tahun').on('change', function () {
        table.draw();
        updateExportUrl();
    });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    const anomaliModal = new bootstrap.Modal('#anomaliDetailModal');

    $('#anomali-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#anomali-site-title').text((row.site_id ?? '-') + ' (' + (row.site_name ?? '-') + ')');

        const html = `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Informasi Site & Pelanggan PLN</td></tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(row.site_id)}</span></td>
                    <th>ID Pelanggan</th><td><span class="fw-bold">${esc(row.id_pelanggan)}</span></td>
                </tr>
                <tr>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(row.site_name)}</span></td>
                    <th>Periode Anomali</th><td><strong>${esc(row.bulan)} ${esc(row.tahun)}</strong></td>
                </tr>
            </table>

            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Komparasi Tagihan & Lonjakan Biaya</td></tr>
                <tr>
                    <th>Tagihan Sebelumnya</th><td class="text-end">${esc(row.tagihan_sebelumnya)}</td>
                    <th>Tagihan Saat Ini</th><td class="text-end fw-bold text-primary">${esc(row.tagihan_saat_ini)}</td>
                </tr>
                <tr>
                    <th>Kenaikan Nominal</th><td class="text-end fw-bold text-danger">+${esc(row.selisih)}</td>
                    <th>Lonjakan (%)</th><td class="text-center">${row.kenaikan_persen}</td>
                </tr>
            </table>

            <div class="alert alert-warning border-warning mb-0 py-2">
                <div class="fw-semibold small mb-1">Analisis Kontekstual & Rekomendasi:</div>
                <div class="small">
                    Tagihan listrik PLN pada site ini mengalami lonjakan lebih dari <strong>50%</strong> dibandingkan periode bulan sebelumnya.
                    Disarankan melakukan audit konsumsi daya harian (AMR), verifikasi histori angka meteran kWh, atau pemeriksaan instalasi perangkat pemancar di site.
                </div>
            </div>
        </div>`;

        $('#anomali-modal-body').html(html);
        anomaliModal.show();
    });
});
</script>
@endpush
