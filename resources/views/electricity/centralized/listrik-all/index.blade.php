@extends('layouts.app')

@section('title', 'Listrik All — Electricity Centralized — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Electricity — Listrik All</h1>
            <p class="text-body-secondary mb-0 small">
                Matriks nilai Flagging pembayaran per rekening PLN. Kolom Inquiry merupakan tagihan dan tidak dihitung sebagai pembayaran.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-outline-secondary btn-sm">
                Data Listrik PLN
            </a>
            @role('admin')
            <a href="{{ route('electricity.centralized.listrik-all.upload-page') }}" class="btn btn-brand btn-sm">Upload Excel</a>
            @endrole
        </div>

        @role('admin')
        <div class="modal fade" id="listrikAllUploadModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
            <form method="POST" action="{{ route('electricity.centralized.listrik-all.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Upload Listrik All</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body"><p class="small text-body-secondary">Upload file sesuai format Dataset/03 Electricity/Centralized/Listrik All. Data lama akan diganti dengan file terbaru.</p><input type="file" name="listrik_all_file" class="form-control" accept=".xls,.xlsx" required></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-brand btn-sm">Upload dan Perbarui</button></div>
            </form>
        </div></div></div>
        @endrole
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Listrik All">
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

            {{-- Filter Year (Tahun) --}}
            <div class="d-flex align-items-center gap-2">
                <label for="filter-tahun" class="form-label mb-0 small fw-semibold text-nowrap">Year (Tahun):</label>
                <select id="filter-tahun" class="form-select form-select-sm" style="min-width: 120px;">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($y == $selectedYear)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Download Excel --}}
            <a href="{{ route('electricity.centralized.listrik-all.export-excel', ['tahun' => $selectedYear]) }}" id="btn-export" class="btn btn-sm btn-outline-brand ms-auto d-inline-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Main Table --}}
    <div class="card">
        <div class="card-body">
            <table id="listrik-all-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>ID Pelanggan</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Gol Tarif</th>
                        <th>Unit PLN</th>
                        <th>Jan</th>
                        <th>Feb</th>
                        <th>Mar</th>
                        <th>Apr</th>
                        <th>Mei</th>
                        <th>Jun</th>
                        <th>Jul</th>
                        <th>Ags</th>
                        <th>Sep</th>
                        <th>Okt</th>
                        <th>Nov</th>
                        <th>Des</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal Detail Rekap 12 Bulan --}}
    <div class="modal fade" id="listrikAllDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Rekapitulasi Tagihan 12 Bulan — <span id="modal-site-title" class="fw-bold"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="modal-site-body">
                    <div class="text-center text-body-secondary py-3">Memuat data rincian...</div>
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
    #listrik-all-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
$(function () {
    const exportBaseUrl = @json(route('electricity.centralized.listrik-all.export-excel'));
    const palette = window.SimasterChartPalette;

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'id_pelanggan', name: 'id_pelanggan' },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'gol_tarif', name: 'gol_tarif' },
        { data: 'unit_pln', name: 'unit_pln' },
        { data: 'jan', name: 'jan', className: 'text-end' },
        { data: 'feb', name: 'feb', className: 'text-end' },
        { data: 'mar', name: 'mar', className: 'text-end' },
        { data: 'apr', name: 'apr', className: 'text-end' },
        { data: 'mei', name: 'mei', className: 'text-end' },
        { data: 'jun', name: 'jun', className: 'text-end' },
        { data: 'jul', name: 'jul', className: 'text-end' },
        { data: 'ags', name: 'ags', className: 'text-end' },
        { data: 'sep', name: 'sep', className: 'text-end' },
        { data: 'okt', name: 'okt', className: 'text-end' },
        { data: 'nov', name: 'nov', className: 'text-end' },
        { data: 'des', name: 'des', className: 'text-end' },
    ];

    const table = new DataTable('#listrik-all-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.centralized.listrik-all.data')),
            data: function (d) {
                d.tahun = $('#filter-tahun').val();
            }
        },
        columns: columns,
        order: [],
        pageLength: 10,
    });

    function updateExportUrl() {
        const tahun = $('#filter-tahun').val();
        $('#btn-export').attr('href', exportBaseUrl + '?tahun=' + tahun);
    }

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    $('#filter-tahun').on('change', function () {
        updateExportUrl();
        table.draw();
    });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }
    function parseNum(v) {
        if (!v || v === '-') return 0;
        const clean = String(v).replace(/[^0-9,-]/g, '').replace(',', '.');
        return parseFloat(clean) || 0;
    }
    function fmtRupiah(val) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
    }

    const detailModal = new bootstrap.Modal('#listrikAllDetailModal');
    let miniChart = null;

    $('#listrikAllDetailModal').on('shown.bs.modal', function () {
        if (miniChart) miniChart.reflow();
    });

    $('#listrik-all-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#modal-site-title').text((row.site_id ?? '-') + ' (' + (row.site_name ?? '-') + ')');

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        const mKeys = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'ags', 'sep', 'okt', 'nov', 'des'];

        const chartData = [];
        let totalYear = 0;
        let countActive = 0;

        mKeys.forEach((key, idx) => {
            const val = parseNum(row[key]);
            chartData.push(val);
            totalYear += val;
            if (val > 0) countActive++;
        });

        const avgMonth = countActive > 0 ? (totalYear / countActive) : 0;

        const html = `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Informasi Site & Pelanggan</td></tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(row.site_id)}</span></td>
                    <th>ID Pelanggan</th><td><span class="fw-bold">${esc(row.id_pelanggan)}</span></td>
                </tr>
                <tr>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(row.site_name)}</span></td>
                    <th>Gol. Tarif / Unit</th><td>${esc(row.gol_tarif)} / ${esc(row.unit_pln)}</td>
                </tr>
                <tr>
                    <th>Total Tagihan Setahun</th><td class="fw-bold text-primary">${fmtRupiah(totalYear)}</td>
                    <th>Rata-rata per Bulan</th><td class="fw-bold">${fmtRupiah(avgMonth)}</td>
                </tr>
            </table>

            {{-- Grafik 12 Bulan --}}
            <div class="card border mb-3">
                <div class="card-header py-2">
                    <span class="small fw-semibold">Grafik Diagram Batang Tagihan 12 Bulan (Tahun ${$('#filter-tahun').val()})</span>
                </div>
                <div class="card-body p-2">
                    <div id="modal-listrik-chart" style="height: 240px; width: 100%;"></div>
                </div>
            </div>
        </div>`;

        $('#modal-site-body').html(html);
        detailModal.show();

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#94A3B8' : '#64748B';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

        miniChart = Highcharts.chart('modal-listrik-chart', {
            chart: { type: 'column', backgroundColor: 'transparent' },
            colors: palette.distinctColors(chartData.length),
            title: { text: null },
            credits: { enabled: false },
            xAxis: {
                categories: months,
                labels: { style: { color: textColor, fontSize: '11px' } },
                lineColor: gridColor
            },
            yAxis: {
                title: { text: null },
                gridLineColor: gridColor,
                labels: {
                    formatter: function () { return (this.value / 1e6).toFixed(0) + 'jt'; },
                    style: { color: textColor, fontSize: '11px' }
                }
            },
            legend: { enabled: false },
            tooltip: {
                backgroundColor: isDark ? '#1E293B' : '#FFFFFF',
                borderColor: isDark ? '#334155' : '#CBD5E1',
                style: { color: isDark ? '#F8FAFC' : '#1E293B' },
                formatter: function () {
                    const percentage = window.SimasterChartMetrics.format(window.SimasterChartMetrics.highcharts(this.point));
                    return `<b>${this.x}</b>: ${fmtRupiah(this.y)}<br>Persentase total tagihan: <b>${percentage}</b>`;
                }
            },
            plotOptions: { column: { borderWidth: 0, colorByPoint: true, borderRadius: 3 } },
            series: [{
                name: 'Tagihan Listrik',
                data: chartData
            }]
        });
    });
});
</script>
@endpush
