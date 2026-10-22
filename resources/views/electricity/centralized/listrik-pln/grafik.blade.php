@extends('layouts.app')

@section('title', 'Grafik Tagihan — ' . $listrikPln->id_pelanggan . ' — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="text-decoration-none">Listrik PLN</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Grafik Tagihan</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0">Grafik Tagihan Listrik — <span class="text-brand fw-bold">{{ $listrikPln->id_pelanggan }}</span></h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('electricity.centralized.listrik-pln.status-pembayaran.index', $listrikPln) }}" class="btn btn-outline-brand btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M14 1a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M3 4h10v2H3V4zm0 4h10v2H3V8zm0 4h7v2H3v-2z"/></svg>
                Lihat Sub-Tabel Status Pembayaran
            </a>
            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-outline-secondary btn-sm">
                ← Kembali ke Listrik PLN
            </a>
        </div>
    </div>

    {{-- Customer Summary Card --}}
    <div class="card mb-3 border-0 shadow-sm pln-customer-summary">
        <div class="card-body py-3">
            <div class="row g-3 text-sm">
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Site ID & Name</span>
                    <strong class="text-main">{{ $listrikPln->site_id }}</strong> — {{ $listrikPln->site_name }}
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Nama Pelanggan</span>
                    <strong class="text-main">{{ $listrikPln->nama_pelanggan ?? '-' }}</strong>
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Daya / Phasa / Tarif</span>
                    <strong class="text-main">{{ $listrikPln->daya_va ? number_format($listrikPln->daya_va) . ' VA' : '-' }}</strong> | {{ $listrikPln->phasa ?? '-' }} ({{ $listrikPln->gol_tarif ?? '-' }})
                </div>
                <div class="col-md-3 col-sm-6">
                    <span class="text-body-secondary small d-block">Unit Layanan PLN</span>
                    <strong class="text-main">{{ $listrikPln->unit_layanan_pln ?? '-' }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Year Selector & Metric Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="select-year" class="form-label mb-0 fw-semibold text-nowrap">Pilih Tahun (Year):</label>
                <select id="select-year" class="form-select form-select-sm" style="width: auto; min-width: 130px;">
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected($y == $selectedYear)>Tahun {{ $y }}</option>
                    @endforeach
                </select>
                <span id="loading-indicator" class="spinner-border spinner-border-sm text-brand d-none" role="status"></span>
            </div>
        </div>

        {{-- Metric Cards --}}
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <span class="text-body-secondary small fw-medium">Total Tagihan Tahun <span class="badge-selected-year">{{ $selectedYear }}</span></span>
                    <h5 id="metric-total" class="mb-0 mt-1 fw-bold text-main">Rp 0</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <span class="text-body-secondary small fw-medium">Rata-rata / Bulan</span>
                    <h5 id="metric-avg" class="mb-0 mt-1 fw-bold text-main">Rp 0</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <span class="text-body-secondary small fw-medium">Tagihan Tertinggi</span>
                    <h5 id="metric-highest" class="mb-0 mt-1 fw-bold text-main">Rp 0</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card h-100">
                <div class="card-body py-3">
                    <span class="text-body-secondary small fw-medium">Jumlah Bulan Terdata</span>
                    <h5 id="metric-months" class="mb-0 mt-1 fw-bold text-secondary">0 / 12 Bulan</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart Container --}}
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>Grafik Tagihan Bulanan (Januari – Desember <span class="badge-selected-year">{{ $selectedYear }}</span>)</span>
        </div>
        <div class="card-body">
            <div style="position: relative; height: 380px; width: 100%;">
                <canvas id="tagihanChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Modal Pop-up Detail Bulan (Muncul saat bar grafik diklik) --}}
    <div class="modal fade" id="detailBulanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Tagihan: <span id="popup-bulan-title" class="fw-bold text-brand"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="popup-bulan-body">
                    <table class="table table-bordered table-sm mb-0">
                        <tr><th style="width: 160px;">ID Pelanggan</th><td id="pop-id-pelanggan"></td></tr>
                        <tr><th>Site ID & Name</th><td id="pop-site"></td></tr>
                        <tr><th>Periode Tagihan</th><td id="pop-periode" class="fw-bold"></td></tr>
                        <tr><th>Nominal Tagihan</th><td id="pop-nominal" class="fw-bold text-brand fs-6"></td></tr>
                        <tr><th>Status / Remark</th><td id="pop-remark"></td></tr>
                        <tr><th>Daya & Tarif</th><td id="pop-daya-tarif"></td></tr>
                        <tr><th>Unit Layanan PLN</th><td id="pop-unit"></td></tr>
                        <tr><th>Update By / Tgl</th><td id="pop-update"></td></tr>
                    </table>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <a href="{{ route('electricity.centralized.listrik-pln.status-pembayaran.index', $listrikPln) }}" class="btn btn-outline-brand btn-sm">
                        Buka di Sub-Tabel Status Pembayaran
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/chart.js/4.4.6/chart.umd.min.js') }}"></script>
<script>
$(function () {
    const dataApiUrl = @json(route('electricity.centralized.listrik-pln.grafik-data', $listrikPln));
    const popupModal = new bootstrap.Modal('#detailBulanModal');
    const palette = window.SimasterChartPalette;

    let chartInstance = null;
    let currentDetails = [];

    function initChart(labels, values) {
        const ctx = document.getElementById('tagihanChart').getContext('2d');

        const barColors = palette.distinctColors(labels.length);

        if (chartInstance) {
            chartInstance.destroy();
        }

        function getThemeColor(variable) {
            return getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
        }

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Nominal Tagihan (Rp)',
                    data: values,
                    backgroundColor: barColors,
                    hoverBackgroundColor: barColors,
                    borderColor: barColors,
                    borderWidth: 0,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 45,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        backgroundColor: getThemeColor('--bg-surface'),
                        titleColor: getThemeColor('--text-main'),
                        bodyColor: getThemeColor('--text-main'),
                        borderColor: getThemeColor('--border-color'),
                        borderWidth: 1,
                        callbacks: {
                            label: function (context) {
                                const percentage = window.SimasterChartMetrics.format(
                                    window.SimasterChartMetrics.chartJs(context)
                                );

                                return [
                                    ' Tagihan: Rp ' + new Intl.NumberFormat('id-ID').format(context.raw || 0),
                                    ' Persentase total tagihan: ' + percentage,
                                ];
                            },
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: getThemeColor('--text-muted'),
                            font: { weight: '600', family: 'Inter' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: getThemeColor('--border-color') },
                        ticks: {
                            color: getThemeColor('--text-muted'),
                            callback: function (value) {
                                if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'M';
                                if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'k';
                                return 'Rp ' + value;
                            }
                        }
                    }
                },
                onClick: function (event, elements) {
                    if (elements && elements.length > 0) {
                        const index = elements[0].index;
                        showMonthDetail(index);
                    }
                }
            }
        });
    }

    function showMonthDetail(monthIndex) {
        if (!currentDetails || !currentDetails[monthIndex]) return;
        const item = currentDetails[monthIndex];
        const selectedYear = $('#select-year').val();

        $('#popup-bulan-title').text(item.nama_bulan + ' ' + selectedYear);
        $('#pop-id-pelanggan').text(item.id_pelanggan || '-');
        $('#pop-site').text((item.site_id || '-') + ' — ' + (item.site_name || '-'));
        $('#pop-periode').text(item.nama_bulan + ' ' + selectedYear);
        $('#pop-nominal').text(item.nominal_format || 'Rp 0');
        $('#pop-remark').html(
            item.nominal > 0
                ? `<span class="badge bg-success">${$('<div>').text(item.remark || 'Lunas').html()}</span>`
                : `<span class="badge bg-secondary">${$('<div>').text(item.remark || 'Belum Ada Tagihan').html()}</span>`
        );
        $('#pop-daya-tarif').text((item.daya_va || '-') + ' / ' + (item.gol_tarif || '-'));
        $('#pop-unit').text(item.unit_pln || '-');
        $('#pop-update').text((item.update_by || '-') + ' (' + (item.tanggal || '-') + ')');

        popupModal.show();
    }

    function loadChartData(year) {
        $('#loading-indicator').removeClass('d-none');
        $('.badge-selected-year').text(year);

        $.ajax({
            url: dataApiUrl,
            method: 'GET',
            data: { tahun: year },
            success: function (res) {
                $('#loading-indicator').addClass('d-none');
                $('#metric-total').text(res.summary.total);
                $('#metric-avg').text(res.summary.rata_rata);
                $('#metric-highest').text(res.summary.tertinggi);
                $('#metric-months').text(res.summary.bulan_terisi);

                currentDetails = res.details;
                initChart(res.labels, res.values);
            },
            error: function () {
                $('#loading-indicator').addClass('d-none');
                alert('Gagal memuat data grafik tagihan.');
            }
        });
    }

    // Event change year selector
    $('#select-year').on('change', function () {
        loadChartData($(this).val());
    });

    window.addEventListener('simaster:theme-changed', function () {
        if (chartInstance && currentDetails.length) {
            initChart(
                currentDetails.map(item => item.nama_bulan),
                currentDetails.map(item => item.nominal)
            );
        }
    });

    // Initial load
    loadChartData($('#select-year').val());
});
</script>
@endpush
