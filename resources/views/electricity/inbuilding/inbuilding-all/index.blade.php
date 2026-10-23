@extends('layouts.app')

@section('title', 'Inbuilding All — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Inbuilding All</h1>
            <p class="text-body-secondary mb-0 small">
                Matriks rekapitulasi 12 bulan (Januari – Desember) tagihan listrik seluruh site inbuilding.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="#" id="btn-export" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Filter Inbuilding All">
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

                {{-- Filter Tahun --}}
                <div class="d-flex align-items-center gap-2">
                    <label for="filter-tahun" class="form-label mb-0 small fw-semibold text-nowrap">Pilih Tahun:</label>
                    <select id="filter-tahun" class="form-select form-select-sm" style="width:auto">
                        @foreach ($years as $yr)
                            <option value="{{ $yr }}" @selected($loop->first)>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="text-body-secondary small">
                Total: <span id="total-rows-info" class="fw-semibold text-dark">-</span> site
            </div>
        </div>
    </div>

    {{-- Main DataTable --}}
    <div class="card">
        <div class="card-body">
            <table id="inbuilding-all-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Status</th>
                        <th>Nama BM</th>
                        <th>TP/NONTP</th>
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

    {{-- Modal Detail Rekap 12 Bulan Inbuilding --}}
    <div class="modal fade" id="inbuildingAllDetailModal" tabindex="-1" aria-hidden="true">
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
    #inbuilding-all-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
$(function () {
    const exportBaseUrl = @json(route('electricity.inbuilding.inbuilding-all.export-excel'));
    const palette = window.SimasterChartPalette;

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'status', name: 'status', className: 'text-center' },
        { data: 'nama_bm', name: 'nama_bm' },
        { data: 'tp_nontp', name: 'tp_nontp', className: 'text-center' },
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

    const table = new DataTable('#inbuilding-all-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: @json(route('electricity.inbuilding.inbuilding-all.data')),
            data: function (d) {
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

    $('#filter-tahun').on('change', function () {
        table.draw();
        updateExportLink();
    });

    function updateExportLink() {
        const params = new URLSearchParams({
            tahun: $('#filter-tahun').val() || @json((string) ($years[0] ?? now()->year)),
        });
        $('#btn-export').attr('href', exportBaseUrl + '?' + params.toString());
    }

    updateExportLink();

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }
    function parseNum(v) {
        if (!v || v === '-') return 0;
        const clean = String(v).replace(/[^0-9,-]/g, '').replace(',', '.');
        return parseFloat(clean) || 0;
    }
    function fmtRupiah(val) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(val || 0));
    }

    const detailModal = new bootstrap.Modal('#inbuildingAllDetailModal');
    let miniChart = null;

    $('#inbuildingAllDetailModal').on('shown.bs.modal', function () {
        if (miniChart) miniChart.reflow();
    });

    $('#inbuilding-all-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row) return;

        $('#modal-site-title').text((row.site_id ?? '-') + ' (' + (row.site_name ?? '-') + ')');

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        const mKeys = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'ags', 'sep', 'okt', 'nov', 'des'];

        const chartData = [];
        let totalYear = 0;
        let countActive = 0;

        mKeys.forEach((key) => {
            const val = parseNum(row[key]);
            chartData.push(val);
            totalYear += val;
            if (val > 0) countActive++;
        });

        const avgMonth = countActive > 0 ? (totalYear / countActive) : 0;

        const html = `
        <div class="detail-block">
            <table class="table table-bordered table-sm mb-3">
                <tr><td colspan="4" class="section-title">Informasi Site Inbuilding</td></tr>
                <tr>
                    <th>Site ID</th><td><span class="badge text-bg-light border">${esc(row.site_id)}</span></td>
                    <th>Status</th><td>${row.status}</td>
                </tr>
                <tr>
                    <th>Site Name</th><td><span class="fw-semibold">${esc(row.site_name)}</span></td>
                    <th>Nama BM</th><td>${esc(row.nama_bm)}</td>
                </tr>
                <tr>
                    <th>Tipe Pengelola</th><td><span class="badge text-bg-secondary">${esc(row.tp_nontp)}</span></td>
                    <th>Tahun Rekap</th><td><strong>Tahun ${$('#filter-tahun').val()}</strong></td>
                </tr>
                <tr>
                    <th>Total Tagihan Setahun</th><td class="fw-bold text-primary">${fmtRupiah(totalYear)}</td>
                    <th>Rata-rata per Bulan</th><td class="fw-bold">${fmtRupiah(avgMonth)}</td>
                </tr>
            </table>

            {{-- Grafik 12 Bulan Inbuilding --}}
            <div class="card border mb-3">
                <div class="card-header py-2">
                    <span class="small fw-semibold">Grafik Diagram Batang Tagihan 12 Bulan Inbuilding (${$('#filter-tahun').val()})</span>
                </div>
                <div class="card-body p-2">
                    <div id="modal-inbuilding-chart" style="height: 240px; width: 100%;"></div>
                </div>
            </div>
        </div>`;

        $('#modal-site-body').html(html);
        detailModal.show();

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const textColor = isDark ? '#94A3B8' : '#64748B';
        const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';

        miniChart = Highcharts.chart('modal-inbuilding-chart', {
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
                name: 'Tagihan Listrik Inbuilding',
                data: chartData
            }]
        });
    });
});
</script>
@endpush
