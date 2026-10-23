@extends('layouts.app')

@section('title', 'Anomali Tagihan Inbuilding — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Anomali Tagihan Listrik Inbuilding</h1>
            <p class="text-body-secondary mb-0 small">
                Monitoring lonjakan tagihan inbuilding dengan kenaikan di atas 50% dibanding periode sebelumnya.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="#" id="btn-export" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Alert Threshold --}}
    <div class="alert alert-warning border-warning d-flex align-items-center gap-2 py-2 mb-3" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5m.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/></svg>
        <div class="small">
            <strong>Threshold Anomali:</strong> Tabel ini memfilter dan menampilkan data tagihan listrik inbuilding dengan lonjakan kenaikan <strong>lebih dari 50% (> 50%)</strong>.
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Anomali Inbuilding">
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

                {{-- Filter Bulan --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="filter-bulan" class="form-label mb-0 small fw-semibold text-nowrap">Bulan:</label>
                    <select id="filter-bulan" class="form-select form-select-sm" style="width:auto">
                        <option value="">Semua Bulan</option>
                        @foreach ([1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'] as $num => $nama)
                            <option value="{{ $num }}">{{ $nama }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter Tahun --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="filter-tahun" class="form-label mb-0 small fw-semibold text-nowrap">Tahun:</label>
                    <select id="filter-tahun" class="form-select form-select-sm" style="width:auto">
                        <option value="">Semua Tahun</option>
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="text-body-secondary small">
                Total: <span id="total-rows-info" class="fw-semibold text-dark">-</span> anomali terdeteksi
            </div>
        </div>
    </div>

    {{-- Main DataTable --}}
    <div class="card">
        <div class="card-body">
            <table id="anomali-inbuilding-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID</th>
                        <th>Periode Sebelumnya</th>
                        <th>Tagihan Sebelumnya</th>
                        <th>Periode Saat Ini</th>
                        <th>Tagihan Saat Ini</th>
                        <th>Selisih</th>
                        <th class="text-center">Kenaikan (%)</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Detail Anomali Inbuilding --}}
    <div class="modal fade" id="anomaliIbcDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Penjelasan Detail Anomali Inbuilding — <span id="anomali-site-title" class="fw-bold"></span></h5>
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
    #anomali-inbuilding-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const exportBaseUrl = @json(route('electricity.inbuilding.anomali.export-excel'));

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_id', name: 'site_id' },
        { data: 'periode_sebelumnya', name: 'periode_sebelumnya', className: 'text-center' },
        { data: 'tagihan_sebelumnya', name: 'tagihan_sebelumnya', className: 'text-end' },
        { data: 'periode_saat_ini', name: 'periode_saat_ini', className: 'text-center' },
        { data: 'tagihan_saat_ini', name: 'tagihan_saat_ini', className: 'text-end fw-semibold' },
        { data: 'selisih', name: 'selisih', className: 'text-end text-danger' },
        { data: 'kenaikan_persen', name: 'kenaikan_persen', className: 'text-center' },
    ];

    const table = new DataTable('#anomali-inbuilding-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.inbuilding.anomali.data')),
            data: function (d) {
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

    $('#filter-bulan, #filter-tahun').on('change', function () {
        table.draw();
        updateExportLink();
    });

    function updateExportLink() {
        const params = new URLSearchParams({
            bulan: $('#filter-bulan').val() || '',
            tahun: $('#filter-tahun').val() || '',
        });
        $('#btn-export').attr('href', exportBaseUrl + '?' + params.toString());
    }

    updateExportLink();

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    const detailModal = new bootstrap.Modal('#anomaliIbcDetailModal');

    $('#anomali-inbuilding-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#anomali-site-title').text(row.site_id ?? '-');

        const html = `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Informasi Site Inbuilding</td></tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(row.site_id)}</span></td>
                    <th>Periode Anomali</th><td><strong>${esc(row.periode_saat_ini)}</strong></td>
                </tr>
            </table>

            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Komparasi Tagihan & Lonjakan Biaya</td></tr>
                <tr>
                    <th>Periode Sebelumnya</th><td>${esc(row.periode_sebelumnya)}</td>
                    <th>Tagihan Sebelumnya</th><td class="text-end">${esc(row.tagihan_sebelumnya)}</td>
                </tr>
                <tr>
                    <th>Periode Saat Ini</th><td><strong>${esc(row.periode_saat_ini)}</strong></td>
                    <th>Tagihan Saat Ini</th><td class="text-end fw-bold text-primary">${esc(row.tagihan_saat_ini)}</td>
                </tr>
                <tr>
                    <th>Kenaikan Nominal</th><td colspan="3" class="text-end fw-bold text-danger">+${esc(row.selisih)}</td>
                </tr>
                <tr>
                    <th>Lonjakan Persentase (%)</th><td colspan="3" class="text-center">${row.kenaikan_persen}</td>
                </tr>
            </table>

            <div class="alert alert-warning border-warning mb-0 py-2">
                <div class="fw-semibold small mb-1">Analisis Kontekstual & Rekomendasi:</div>
                <div class="small">
                    Tagihan listrik IBC pada site inbuilding ini mengalami kenaikan di atas <strong>50%</strong> dibandingkan periode sebelumnya.
                    Disarankan memverifikasi invoice dari Building Management (BM), memeriksa angka meteran awal dan akhir, serta memastikan tidak ada kebocoran atau penambahan perangkat tanpa otorisasi.
                </div>
            </div>
        </div>`;

        $('#anomali-modal-body').html(html);
        detailModal.show();
    });
});
</script>
@endpush
