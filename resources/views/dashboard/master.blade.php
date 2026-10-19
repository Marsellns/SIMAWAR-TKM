@extends('layouts.app')

@section('title', 'Dashboard')

@push('styles')
<style>
.enterprise-tabs-nav + svg {
    display: none;
}
.kpi-clickable {
    cursor: pointer;
    user-select: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}
.kpi-clickable:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 24px rgba(37, 99, 235, 0.12);
    border-color: #2563eb !important;
}
.kpi-clickable:active {
    transform: translateY(-1px);
}
.site-owner-legend-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.site-owner-legend-row:hover {
    background-color: rgba(37, 99, 235, 0.08);
}
.site-owner-modal .modal-dialog {
    width: 75vw;
    max-width: 75vw;
    height: 75vh;
    margin: 12.5vh auto;
}
.site-owner-modal .modal-content {
    height: 100%;
    overflow: hidden;
}
.site-owner-modal .modal-body {
    min-height: 0;
    overflow: hidden;
}
.site-owner-modal #drawer-table-wrapper {
    min-height: 0;
    overflow-y: auto;
}
.site-owner-modal #drawer-table {
    width: 100%;
}
.dashboard-tab-panel .enterprise-kpi,
.dashboard-tab-panel .chart-card {
    opacity: 0;
    transform: translateY(14px);
}
.dashboard-tab-panel.dashboard-ready .enterprise-kpi,
.dashboard-tab-panel.dashboard-ready .chart-card {
    animation: dashboard-card-enter 650ms cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.chart-card {
    transition: opacity 300ms ease, transform 300ms ease, box-shadow 250ms ease;
}
.chart-card.is-loading {
    opacity: 0.62;
}
@keyframes dashboard-card-enter {
    from { opacity: 0; transform: translateY(14px); }
    to { opacity: 1; transform: translateY(0); }
}
@media (prefers-reduced-motion: reduce) {
    .dashboard-tab-panel .enterprise-kpi,
    .dashboard-tab-panel .chart-card {
        opacity: 1;
        transform: none;
        animation: none !important;
        transition: none;
    }
}
[data-bs-theme="dark"] .kpi-clickable:hover {
    border-color: #3b82f6 !important;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.5);
}
[data-bs-theme="dark"] .site-owner-legend-row:hover {
    background-color: rgba(59, 130, 246, 0.15);
}
</style>
@endpush

@section('content')
    {{-- Header with dashboard navigation tabs --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 page-hero">
        <div>
            <h1 class="h4 mb-1">Grafik Dashboard</h1>
            <p class="text-body-secondary small mb-0">Ringkasan grafik finansial dan operasional SIMAWAR.</p>
        </div>

        {{-- Main dashboard navigation tabs --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="enterprise-tabs-nav">
                <button type="button" class="enterprise-tab-btn active" data-tab="overview">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3z"/></svg>
                    System Overview
                </button>
                <button type="button" class="enterprise-tab-btn" data-tab="analytics">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M0 0h1v15h15v1H0V0Zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.707l-4.146 4.147a.5.5 0 0 1-.708 0L7 6.707l-4.146 4.147a.5.5 0 0 1-.708-.708l4.5-4.5a.5.5 0 0 1 .708 0L9.5 7.793l3.646-3.647H10.5a.5.5 0 0 1-.5-.5Z"/></svg>
                    Detailed Analytics &amp; Reports
                </button>
            </div>

                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2zm15 2h-4v3h4V4zm0 4h-4v3h4V8zm0 4h-4v3h3a1 1 0 0 0 1-1v-2zm-5 3v-3H6v3h4zm-5 0v-3H1v2a1 1 0 0 0 1 1h3zm-4-4h4V8H1v3zm0-4h4V4H1v3zm5-3v3h4V4H6zm4 4H6v3h4V8z"/></svg>
        </div>
    </div>

    {{-- Universal Filter Bar --}}
    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 12px;" data-simaster-filter-panel="Filter Dashboard">
        <div class="card-body py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-light border text-secondary fw-semibold">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" class="me-1" viewBox="0 0 16 16"><path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5v-2z"/></svg>
                        Filter Finansial &amp; Operasional:
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="dashboard-year" class="form-label mb-0 small text-body-secondary">Tahun</label>
                    <select id="dashboard-year" class="form-select form-select-sm" style="min-width: 100px; border-radius: 8px;" disabled></select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="dashboard-month" class="form-label mb-0 small text-body-secondary">Bulan</label>
                    <select id="dashboard-month" class="form-select form-select-sm" style="min-width: 130px; border-radius: 8px;" disabled></select>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label for="dashboard-nop" class="form-label mb-0 small text-body-secondary">NOP</label>
                    <select id="dashboard-nop" class="form-select form-select-sm" style="min-width: 160px; border-radius: 8px;" disabled></select>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         TAB 1: SYSTEM OVERVIEW (SCREEN 1)
         ========================================================================= --}}
    <div id="tab-content-overview" class="dashboard-tab-panel">
        {{-- Top 4 KPI Cards --}}
        <div class="row g-3 mb-4">
            {{-- Card 1: P&L sites --}}
            <div class="col-xl-3 col-md-6">
                <div class="enterprise-kpi kpi-clickable" id="kpi-card-sites" role="button" tabindex="0">
                    <div class="module-kpi-title"><img src="{{ asset('assets/modules/dashboard.svg') }}" alt="" width="35" height="35"><span>Total Site P&amp;L</span></div>
                    <div class="stat-number fs-3 mb-1" id="kpi-sites-count">{{ number_format($siteCount) }}</div>
                </div>
            </div>

            {{-- Card 2: PLN customers --}}
            <div class="col-xl-3 col-md-6">
                <div class="enterprise-kpi kpi-clickable" id="kpi-card-pln" role="button" tabindex="0">
                    <div class="module-kpi-title"><img src="{{ asset('assets/modules/electricity.svg') }}" alt="" width="35" height="35"><span>Pelanggan PLN</span></div>
                    <div class="stat-number fs-3 mb-1" id="kpi-pln-pelanggan">{{ number_format($listrikCount) }}</div>
                </div>
            </div>

            {{-- Card 3: Sewa Lahan and Combat sites --}}
            <div class="col-xl-3 col-md-6">
                <div class="enterprise-kpi kpi-clickable" id="kpi-card-infra" role="button" tabindex="0">
                    <div class="module-kpi-title"><img src="{{ asset('assets/modules/infrastructure.svg') }}" alt="" width="35" height="35"><span>Site Sewa Lahan &amp; Combat</span></div>
                    <div class="stat-number fs-3 mb-1" id="kpi-infra-count">{{ number_format($infraTotal) }}</div>
                </div>
            </div>

            {{-- Card 4: Purchase orders --}}
            <div class="col-xl-3 col-md-6">
                <div class="enterprise-kpi kpi-clickable" id="kpi-card-po" role="button" tabindex="0">
                    <div class="module-kpi-title"><img src="{{ asset('assets/modules/purchase-order.svg') }}" alt="" width="35" height="35"><span>Total PO HQ</span></div>
                    <div class="stat-number fs-3 mb-1" id="kpi-po-count">{{ number_format($poCount) }}</div>
                </div>
            </div>
        </div>

        {{-- Main Row: Data Growth Bar Chart + Category Donut / Access Breakdown --}}
        <div class="row g-3 mb-4">
            {{-- Left Column: Data & Financial Growth --}}
            <div class="col-lg-8">
                <div class="enterprise-card h-100 chart-card">
                    <div class="enterprise-card-header">
                        <div>
                            <h2 class="enterprise-card-title">Data &amp; Financial Growth</h2>
                            <span class="text-body-secondary small" id="growth-chart-caption">Satuan: Miliar Rp</span>
                        </div>
                    </div>
                    <div class="enterprise-card-body">
                        <div id="chart-growth-bars" style="height: 340px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Data by Category (Status Site) & User Access Distribution --}}
            <div class="col-lg-4">
                <div class="enterprise-card h-100 chart-card">
                    <div class="enterprise-card-header">
                        <h2 class="enterprise-card-title">Data by Category</h2>
                    </div>
                    <div class="enterprise-card-body">
                        {{-- Donut Chart --}}
                        <div id="chart-category-donut" style="height: 200px; width: 100%;"></div>

                        {{-- User / Module Access Distribution Breakdown --}}
                        <div class="border-top pt-3 mt-2">
                            <div class="small fw-bold text-body-secondary text-uppercase mb-2 tracking-wider">Komposisi Data per Modul</div>

                            <a href="{{ route('pnl.index') }}" class="text-decoration-none d-block access-link-item mb-2">
                                <div class="access-row mb-1">
                                    <span class="access-label">Profit &amp; Loss</span>
                                    <span class="access-val" data-module-percentage="pnl">{{ $moduleDataComposition['pnl']['percentage'] }}% &rarr;</span>
                                </div>
                                <div class="kpi-progress-bar">
                                    <div class="kpi-progress-fill" data-module-progress="pnl" style="width: {{ $moduleDataComposition['pnl']['percentage'] }}%; background: #2563eb;"></div>
                                </div>
                            </a>

                            <a href="{{ route('electricity.centralized.listrik-pln.index') }}" id="dashboard-pln-link" class="text-decoration-none d-block access-link-item mb-2">
                                <div class="access-row mb-1">
                                    <span class="access-label">Electricity Centralized</span>
                                    <span class="access-val" data-module-percentage="electricity">{{ $moduleDataComposition['electricity']['percentage'] }}% &rarr;</span>
                                </div>
                                <div class="kpi-progress-bar">
                                    <div class="kpi-progress-fill" data-module-progress="electricity" style="width: {{ $moduleDataComposition['electricity']['percentage'] }}%; background: #3b82f6;"></div>
                                </div>
                            </a>

                            <a href="{{ route('infrastruktur.sewa-lahan.index') }}" class="text-decoration-none d-block access-link-item mb-2">
                                <div class="access-row mb-1">
                                    <span class="access-label">Infrastruktur Management</span>
                                    <span class="access-val" data-module-percentage="infrastructure">{{ $moduleDataComposition['infrastructure']['percentage'] }}% &rarr;</span>
                                </div>
                                <div class="kpi-progress-bar">
                                    <div class="kpi-progress-fill" data-module-progress="infrastructure" style="width: {{ $moduleDataComposition['infrastructure']['percentage'] }}%; background: #60a5fa;"></div>
                                </div>
                            </a>

                            <a href="{{ route('po-hq.index') }}" class="text-decoration-none d-block access-link-item mb-1">
                                <div class="access-row mb-1">
                                    <span class="access-label">PO HQ &amp; Lainnya</span>
                                    <span class="access-val" data-module-percentage="po">{{ $moduleDataComposition['po']['percentage'] }}% &rarr;</span>
                                </div>
                                <div class="kpi-progress-bar">
                                    <div class="kpi-progress-fill" data-module-progress="po" style="width: {{ $moduleDataComposition['po']['percentage'] }}%; background: #93c5fd;"></div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Site Owner Distribution Full Row --}}
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="enterprise-card chart-card">
                    <div class="enterprise-card-header">
                        <div>
                            <h2 class="enterprise-card-title">Distribusi Site Owner</h2>
                            <span class="text-body-secondary small" id="site-owner-caption"></span>
                        </div>
                        <a href="{{ route('data-potensi.site-owner.export-excel') }}"
                           id="site-owner-export"
                           class="btn btn-sm btn-outline-brand d-inline-flex align-items-center gap-1 text-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                            Excel
                        </a>
                    </div>
                    <div class="enterprise-card-body">
                        <div class="site-owner-layout">
                            <div class="site-owner-chart-area">
                                <div id="chart-site-owner" style="width: 100%; height: 320px;"></div>
                            </div>
                            <aside class="site-owner-legend-panel mt-3 pt-3 border-top" aria-label="Keterangan warna Site Owner">
                                <div class="small fw-semibold mb-2">Daftar Kontribusi Site Owner</div>
                                <div id="site-owner-legend" class="site-owner-legend"></div>
                            </aside>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="enterprise-card chart-card mb-4">
                                <div class="enterprise-card-header">
                                    <div>
                                        <h2 class="enterprise-card-title">Cakupan Data Pembayaran Listrik — Makro dan Inbuilding</h2>
                                        <span class="text-body-secondary small" id="electricity-payment-caption">Site dengan flagging pembayaran per bulan</span>
                                    </div>
                                </div>
                                <div class="enterprise-card-body">
                                    <div id="chart-electricity-payment" style="width: 100%; height: 430px;"></div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <div class="enterprise-card chart-card">
                                        <div class="enterprise-card-header">
                                            <div>
                                                <h2 class="enterprise-card-title">Tren Nominal Pembayaran Listrik — Makro dan Inbuilding</h2>
                                                <span class="text-body-secondary small" id="electricity-all-caption">Nilai flagging pembayaran dari dataset Listrik All</span>
                                            </div>
                                            <span class="badge text-bg-light border small">Listrik All dan Payment IBC</span>
                                        </div>
                                        <div class="enterprise-card-body">
                                            <div id="chart-electricity-all" style="width: 100%; height: 430px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================================
         TAB 2: DETAILED ANALYTICS & REPORTS (SCREEN 2)
         ========================================================================= --}}
    <div id="tab-content-analytics" class="dashboard-tab-panel d-none">
        {{-- Row 1: Financial Performance + Storage / Resource Allocation --}}
        <div class="row g-3 mb-4">
            {{-- Financial Combination Chart --}}
            <div class="col-lg-8">
                <div class="enterprise-card h-100 chart-card">
                    <div class="enterprise-card-header">
                        <div>
                            <h2 class="enterprise-card-title">Financial Performance</h2>
                            <span class="text-body-secondary small">Revenue dan Cost ditampilkan sebagai batang; Net PnL menunjukkan hasil bersih di sekitar garis nol</span>
                        </div>
                    </div>
                    <div class="enterprise-card-body">
                        <div id="chart-analytics-performance" style="height: 320px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            {{-- Storage / Infrastructure Allocation Donut --}}
            <div class="col-lg-4">
                <div class="enterprise-card h-100 chart-card">
                    <div class="enterprise-card-header">
                        <h2 class="enterprise-card-title">Infrastructure Allocation</h2>
                    </div>
                    <div class="enterprise-card-body position-relative">
                        <div id="chart-storage-allocation" style="height: 280px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: Peak Electricity Usage + Error / Anomaly Distribution --}}
        <div class="row g-3 mb-4">
            {{-- Peak Usage Bar Chart --}}
            <div class="col-lg-8">
                <div class="enterprise-card h-100 chart-card">
                    <div class="enterprise-card-header">
                        <div>
                            <h2 class="enterprise-card-title">Monthly Electricity Billing</h2>
                        </div>
                        <span class="badge text-bg-light border small">Listrik Centralized</span>
                    </div>
                    <div class="enterprise-card-body">
                        <div id="chart-peak-usage" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>

            {{-- Error & Anomaly Distribution Donut --}}
            <div class="col-lg-4">
                <div class="enterprise-card h-100 chart-card">
                    <div class="enterprise-card-header">
                        <h2 class="enterprise-card-title">Error &amp; Anomaly Distribution</h2>
                    </div>
                    <div class="enterprise-card-body position-relative">
                        <div id="chart-error-distribution" style="height: 300px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal for Site Owner Contributor List --}}
    <div class="modal fade site-owner-modal" tabindex="-1" id="siteDrawer" aria-labelledby="drawer-title" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-bottom pb-2">
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle small mb-1">Detail Site Kontributor</span>
                        <h5 class="modal-title" id="drawer-title">Daftar Site</h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('data-potensi.site-owner.export-excel') }}"
                           id="site-owner-detail-export"
                           class="btn btn-sm btn-outline-brand d-none d-inline-flex align-items-center gap-1 text-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                            Excel
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                </div>
                <div class="px-3 pt-3">
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-sm" id="drawer-search"
                               placeholder="Cari Site ID, Nama Site, atau Alamat..." autocomplete="off">
                        <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y border-0 text-body-secondary d-none" id="drawer-search-clear" style="margin-right: 4px;" title="Hapus pencarian">&times;</button>
                    </div>
                </div>
                <div class="modal-body d-flex flex-column pt-2">
                    <div id="drawer-count-info" class="small text-body-secondary mb-2"></div>
                    <div id="drawer-table-wrapper" class="table-responsive flex-grow-1">
                        <table class="table table-sm table-hover align-middle mb-0" id="drawer-table" style="font-size: 0.9rem;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Site ID</th>
                                    <th>Nama Site</th>
                                    <th>Alamat</th>
                                </tr>
                            </thead>
                            <tbody id="drawer-tbody">
                                <tr><td colspan="3" class="text-center py-4 text-body-secondary">Memuat data site...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="electricityPaymentModal" tabindex="-1" aria-labelledby="electricity-payment-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="electricity-payment-title">Site Listrik Belum Terbayar</h5>
                        <div class="small text-body-secondary" id="electricity-payment-detail-summary"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead><tr><th>Site ID</th><th>Nama Site</th><th>ID Pelanggan</th><th>NOP</th><th>Status</th></tr></thead>
                            <tbody id="electricity-payment-detail-body">
                                <tr><td colspan="5" class="text-center text-body-secondary">Memuat data...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('electricity.partials.payment-detail-modal')
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script src="{{ asset('js/electricity-comparison-charts.js') }}"></script>

<script>
$(function () {
    const chartApiUrl = "{{ route('dashboard.chart-data') }}";
    const routesMap = {
        pnl: "{{ route('pnl.index') }}",
        pnlData: "{{ route('pnl.data') }}",
        pln: "{{ route('electricity.centralized.listrik-pln.index') }}",
        payment: "{{ route('electricity.centralized.payment.index') }}",
        plnAnomali: "{{ route('electricity.centralized.anomali.index') }}",
        ibcAnomali: "{{ route('electricity.inbuilding.anomali.index') }}",
        sewaLahan: "{{ route('infrastruktur.sewa-lahan.index') }}",
        infrastructure: "{{ route('infrastruktur.index') }}",
        combat: "{{ route('infrastruktur.combat.index') }}",
        recurringIpas: "{{ route('infrastruktur.recurring-ipas.index') }}",
        jaknet: "{{ route('infrastruktur.jaknet.index') }}",
        bapss: "{{ route('infrastruktur.bapss.index') }}",
        uploadFile: "{{ route('infrastruktur.upload-file.index') }}",
        poHq: "{{ route('po-hq.index') }}",
        siteOwnerExport: "{{ route('data-potensi.site-owner.export-excel') }}"
    };

    const monthNames = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    let availablePeriods = [];
    let selectedPeriod = null;
    let cachedChartData = null;
    let chartRequest = null;
    let chartRequestId = 0;
    let paymentDetailRequest = null;
    let paymentDetailRequestId = 0;
    const chartInstances = {};

    let currentTab = 'overview';

    const palette = window.SimasterChartPalette;

    // Warna peran tetap konsisten, sementara pie dan bar satu-seri memakai
    // palet bersama yang lebih bervariasi.
    const corpColors = {
        primary: palette.financial.revenue,
        primaryDark: '#1d4ed8',
        secondary: palette.series[1],
        accent: palette.series[2],
        light: '#93c5fd',
        soft: '#dbeafe',
        success: palette.financial.profit,
        danger: palette.financial.loss,
        warning: palette.status.warning,
        slate: '#64748b'
    };

    function getThemeStyles() {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        return {
            isDark,
            text: isDark ? '#cbd5e1' : '#334155',
            titleText: isDark ? '#f8fafc' : '#0f172a',
            gridLine: isDark ? '#475569' : '#cbd5e1',
            tooltipBg: isDark ? '#0f172a' : '#ffffff',
            tooltipBorder: isDark ? '#334155' : '#e2e8f0',
            tooltipText: isDark ? '#f8fafc' : '#1e293b',
            barBg: isDark ? '#1e293b' : '#eff6ff'
        };
    }

    // Tab Navigation Logic (2 Tabs: overview & analytics)
    $('.enterprise-tab-btn').on('click', function () {
        const tab = $(this).data('tab');
        if (tab === currentTab) return;
        currentTab = tab;

        $('.enterprise-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.dashboard-tab-panel').addClass('d-none');
        $(`#tab-content-${tab}`).removeClass('d-none');

        // Update titles
        if (tab === 'overview') {
            $('#current-view-title').text('System Overview');
            $('#current-view-subtitle').text('Real-time operational & financial metrics for SIMAWAR Telkomsel');
        } else if (tab === 'analytics') {
            $('#current-view-title').text('Detailed Analytics & Reports');
            $('#current-view-subtitle').text('Review system performance, storage metrics, and incident logs');
        }

        // Reflow all Highcharts in the active tab
        setTimeout(() => {
            Object.values(chartInstances).forEach(chart => {
                if (chart && typeof chart.reflow === 'function') {
                    chart.reflow();
                }
            });
        }, 80);
    });


    function chartCacheKey(period) {
        return `${period.tahun}-${period.bulan}-${period.nop || 'all'}`;
    }

    function setChartsLoading(isLoading) {
        $('.chart-card').toggleClass('is-loading', isLoading);
    }

    function upsertChart(key, containerId, options) {
        const existing = chartInstances[key];
        if (existing) {
            existing.update(options, true, true, { duration: 400 });
            existing.reflow();
            return existing;
        }
        chartInstances[key] = Highcharts.chart(containerId, options);
        return chartInstances[key];
    }

    // Highcharts Setup (Context menu / Exporting disabled globally)
    function applyHighchartsTheme() {
        const theme = getThemeStyles();
        Highcharts.setOptions({
            colors: palette.series,
            chart: {
                style: { fontFamily: 'system-ui, -apple-system, sans-serif' },
                backgroundColor: 'transparent',
                animation: { duration: 400 }
            },
            title: { text: null },
            credits: { enabled: false },
            exporting: { enabled: false }, // Hapus chart context menu
            tooltip: {
                backgroundColor: theme.tooltipBg,
                borderColor: theme.tooltipBorder,
                borderRadius: 8,
                shadow: true,
                style: { color: theme.tooltipText, fontSize: '12px' },
                formatter: function () {
                    const point = this.point || this;
                    const value = Number(point.y) || 0;
                    const percentage = window.SimasterChartMetrics.format(window.SimasterChartMetrics.highcharts(point));
                    const label = point.name || this.key || this.x || point.series.name;

                    return `<b>${label}</b><br><span style="color:${point.color}">●</span> ${point.series.name}: ` +
                        `<b>${value.toLocaleString('id-ID')}</b><br>Persentase total: <b>${percentage}</b>`;
                }
            }
        });
    }

    // Helper to navigate to PnL with params
    function navigateToPnl(tahun, bulan, nop, status = '') {
        const params = new URLSearchParams();
        if (tahun) params.set('tahun', tahun);
        // Kirim bulan=all jika semua bulan, atau nilai bulan spesifik, atau all jika null
        if (bulan === 'all' || bulan === null || bulan === undefined || bulan === '') {
            params.set('bulan', 'all');
        } else {
            params.set('bulan', bulan);
        }
        if (nop) params.set('nop', nop);
        if (status) params.set('status', status);
        window.location.href = `${routesMap.pnl}?${params.toString()}`;
    }

    // Buka rincian pembayaran pada periode yang sama dengan batang grafik.
    function navigateToElectricityPayments(tahun, bulan, nop) {
        const params = new URLSearchParams();
        if (tahun) params.set('tahun', tahun);
        // Kirim bulan spesifik jika ada, atau kosongkan (PLN mendukung Semua Bulan via select)
        if (bulan && bulan !== 'all' && bulan !== null) {
            params.set('bulan', bulan);
        }
        if (nop) params.set('nop', nop);
        window.location.href = `${routesMap.payment}?${params.toString()}`;
    }

    function navigateToPln(tahun, bulan, nop) {
        const params = new URLSearchParams();
        if (tahun) params.set('tahun', tahun);
        if (bulan && bulan !== 'all') params.set('bulan', bulan);
        if (nop) params.set('nop', nop);
        window.location.href = `${routesMap.pln}?${params.toString()}`;
    }

    function siteOwnerExportUrl(owner = '') {
        const params = new URLSearchParams();
        const nop = cachedChartData?.filters?.nop || '';

        if (nop) params.set('nop', nop);
        if (owner) params.set('site_owner', owner);

        const query = params.toString();
        return `${routesMap.siteOwnerExport}${query ? `?${query}` : ''}`;
    }

    // 1. Render Growth Bar Chart (Overview Screen 1) - Click navigates to PnL month data
    function renderGrowthBars(pnlData, theme) {
        const labels = pnlData.labels || [];
        const revData = pnlData.revenue || [];
        const costData = pnlData.cost || [];
        const pnlValues = pnlData.profit_loss || [];
        const periods = pnlData.periods || [];

        upsertChart('growthBars', 'chart-growth-bars', {
            chart: { type: 'column' },
            exporting: { enabled: false },
            xAxis: {
                categories: labels,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                lineColor: theme.gridLine
            },
            yAxis: {
                title: { text: 'Miliar Rupiah (Rp)', style: { color: theme.text, fontSize: '11px' } },
                gridLineColor: theme.gridLine,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                plotLines: [{
                    value: 0,
                    color: theme.text,
                    width: 1.5,
                    zIndex: 5,
                    label: { text: 'Rp 0', align: 'right', style: { color: theme.text, fontSize: '10px' } }
                }]
            },
            legend: {
                itemStyle: { color: theme.text, fontSize: '12px', fontWeight: '500' }
            },
            plotOptions: {
                column: {
                    borderRadius: 5,
                    borderWidth: 0,
                    pointPadding: 0.15,
                    groupPadding: 0.18,
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function () {
                                const p = periods[this.index];
                                if (p) {
                                    navigateToPnl(p.tahun, p.bulan, cachedChartData?.filters?.nop);
                                }
                            }
                        }
                    }
                }
            },
            series: [
                {
                    name: 'Revenue',
                    data: revData,
                    color: corpColors.primary
                },
                {
                    name: 'Total Cost',
                    data: costData,
                    color: corpColors.danger
                },
                {
                    name: 'Net PnL',
                    data: pnlValues.map(value => ({
                        y: Number(value) || 0,
                        color: Number(value) > 0 ? corpColors.success : corpColors.danger
                    }))
                }
            ]
        });
    }

    // 2. Render Category Donut (Overview Screen 1) - Click opens filtered PnL
    function renderCategoryDonut(pnlStatus, theme) {
        const statusColors = {
            'Profit Site': corpColors.success,
            'Loss Site': corpColors.danger,
            'Tidak Aktif': palette.financial.inactive,
        };

        upsertChart('categoryDonut', 'chart-category-donut', {
            chart: { type: 'pie' },
            exporting: { enabled: false },
            plotOptions: {
                pie: {
                    innerSize: '65%',
                    borderWidth: 0,
                    cursor: 'pointer',
                    dataLabels: { enabled: true, format: '{point.name}: {point.y} ({point.percentage:.1f}%)' },
                    showInLegend: true,
                    point: {
                        events: {
                            click: function () {
                                const statusMap = {
                                    'Profit Site': 'Profit',
                                    'Loss Site': 'Loss',
                                    'Tidak Aktif': 'TidakAktif',
                                };
                                const status = statusMap[this.name];
                                const filters = cachedChartData?.filters || {};
                                if (status) {
                                    navigateToPnl(
                                        filters.tahun,
                                        filters.all_months ? 'all' : filters.bulan,
                                        filters.nop,
                                        status
                                    );
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom',
                itemStyle: { color: theme.text, fontSize: '11px' }
            },
            series: [{
                name: 'Total Site',
                data: (pnlStatus || []).map((point, index) => ({
                    ...point,
                    color: statusColors[point.name] || palette.series[index % palette.series.length]
                }))
            }]
        });
    }

    // 3. Render Site Owners (Overview Screen 1) - Click filters or navigates to PnL by owner
    function renderSiteOwners(siteOwners, theme) {
        const items = siteOwners.items || [];
        const topOwners = items.slice(0, 10);
        const categories = topOwners.map(item => item.name);
        const values = topOwners.map(item => item.total);
        const selectedNop = cachedChartData?.filters?.nop || '';
        const totalSites = Number(siteOwners.total_sites || 0).toLocaleString('id-ID');
        $('#site-owner-caption').text(
            `${selectedNop ? `NOP: ${selectedNop} — ` : ''}Total ${totalSites} Site ID`
        );
        $('#site-owner-export').attr('href', siteOwnerExportUrl());

        upsertChart('siteOwners', 'chart-site-owner', {
            chart: { type: 'bar' },
            colors: palette.distinctColors(values.length),
            exporting: { enabled: false },
            xAxis: {
                categories: categories,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                lineColor: theme.gridLine
            },
            yAxis: {
                title: { text: 'Jumlah Site ID', style: { color: theme.text, fontSize: '11px' } },
                gridLineColor: theme.gridLine,
                labels: { style: { color: theme.text, fontSize: '11px' } }
            },
            legend: { enabled: false },
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    borderWidth: 0,
                    colorByPoint: true,
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function () {
                                const owner = categories[this.index];
                                if (owner) {
                                    openSiteDrawer(`Owner: ${owner}`);
                                }
                            }
                        }
                    }
                }
            },
            series: [{
                name: 'Sites',
                data: values
            }]
        });

        // Legend panel list
        const $legend = $('#site-owner-legend').empty();
        items.slice(0, 8).forEach(item => {
            $legend.append(`
                <div class="d-flex justify-content-between align-items-center small py-1 px-2 rounded border-bottom site-owner-legend-row" data-owner="${item.name}">
                    <span class="text-truncate me-2" style="max-width: 220px;">${item.name}</span>
                    <span class="fw-bold">${item.total.toLocaleString('id-ID')} <span class="text-secondary fw-normal">(${item.percentage}%)</span></span>
                </div>
            `);
        });

        $('#site-owner-legend').off('click', '.site-owner-legend-row').on('click', '.site-owner-legend-row', function () {
            const owner = $(this).data('owner');
            if (owner) {
                openSiteDrawer(`Owner: ${owner}`);
            }
        });
    }

    function renderElectricityPayment(data, theme) {
        const labels = data.period_labels || [];
        const activeSites = data.active_sites || [];
        const percentages = data.percentages || [];
        const paidCounts = data.paid_counts || [];
        const costs = data.costs || [];
        const periods = data.periods || [];
        const dataAvailable = data.data_available || [];

        const selectedNop = cachedChartData?.filters?.nop || '';
        const filterLabel = selectedNop ? ` — NOP: ${selectedNop}` : ' — Semua NOP';
        $('#electricity-payment-caption').text(
            `Site dengan flagging pembayaran / site pada snapshot${filterLabel} — ${labels[0] || 'Tidak ada periode'}${labels.length > 1 ? ` sampai ${labels[labels.length - 1]}` : ''}. Bulan tanpa data tidak dihitung sebagai 0%.`
        );

        upsertChart('electricityPayment', 'chart-electricity-payment', {
            chart: {
                type: 'column',
                spacingTop: 24,
                scrollablePlotArea: { minWidth: 760, scrollPositionX: 0 }
            },
            exporting: { enabled: false },
            xAxis: {
                categories: labels,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                lineColor: theme.gridLine
            },
            yAxis: {
                min: 0,
                // Reserve headroom above 100% so data labels stay inside the plot.
                max: 120,
                endOnTick: false,
                tickPositions: [0, 25, 50, 75, 100],
                title: { text: 'Cakupan pada Snapshot (%)', style: { color: theme.text, fontSize: '11px' } },
                labels: { format: '{value}%', style: { color: theme.text, fontSize: '11px' } },
                gridLineColor: theme.gridLine
            },
            legend: { enabled: false },
            tooltip: {
                formatter: function () {
                    const i = this.point.index;
                    return `<b>${labels[i]}</b><br>` +
                        `Site dengan flagging: <b>${Number(paidCounts[i] || 0).toLocaleString('id-ID')}</b><br>` +
                        `Site pada snapshot: <b>${Number(activeSites[i] || 0).toLocaleString('id-ID')}</b><br>` +
                        `Persentase: <b>${dataAvailable[i] ? `${Number(percentages[i] || 0).toLocaleString('id-ID')}%` : 'Belum tersedia'}</b><br>` +
                        `Total biaya: <b>Rp ${Number(costs[i] || 0).toLocaleString('id-ID')}</b>` +
                        (!dataAvailable[i] ? '<br><span class="text-warning">Data pembayaran belum tersedia</span>' : '');
                }
            },
            plotOptions: {
                column: {
                    borderRadius: 5,
                    borderWidth: 0,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        formatter: function () {
                            const i = this.point.index;
                            // Two short, readable lines also fit narrow monthly columns.
                            return `${Number(paidCounts[i] || 0).toLocaleString('id-ID')}<br/>/ ${Number(activeSites[i] || 0).toLocaleString('id-ID')}`;
                        },
                        style: {
                            color: theme.text,
                            fontSize: '11px',
                            fontWeight: '600',
                            textOutline: 'none'
                        },
                        inside: false,
                        align: 'center',
                        y: -6,
                        crop: true,
                        overflow: 'justify'
                    },
                    point: {
                        events: {
                            click: function () {
                                const period = periods[this.index];
                                if (period && dataAvailable[this.index]) {
                                    const nop = cachedChartData?.filters?.nop || selectedPeriod?.nop || '';
                                    showElectricityPaymentDetail(period.tahun, period.bulan, nop);
                                }
                            }
                        }
                    }
                }
            },
            series: [{
                name: 'Cakupan Flagging',
                data: percentages.map((value, index) => ({
                    y: dataAvailable[index] ? Number(value) || 0 : null,
                    color: !dataAvailable[index]
                        ? '#94A3B8'
                        : (Number(value) >= 100 ? corpColors.success : corpColors.primary)
                }))
            }]
        });
    }

    function renderElectricityAll(data, theme) {
        const labels = data.labels || [];
        const costs = data.costs || [];
        const siteCounts = data.site_counts || [];
        const year = data.year || selectedPeriod?.tahun || '';
        const barColors = palette.distinctColors(costs.length);

        $('#electricity-all-caption').text(
            `Nilai flagging pembayaran dari ${Number(data.site_count || 0).toLocaleString('id-ID')} site — ${year}. Inquiry tidak dihitung sebagai pembayaran.`
        );

        upsertChart('electricityAll', 'chart-electricity-all', {
            chart: { type: 'column' },
            exporting: { enabled: false },
            xAxis: {
                categories: labels,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                lineColor: theme.gridLine
            },
            yAxis: {
                min: 0,
                title: { text: 'Total Biaya (Rp)', style: { color: theme.text, fontSize: '11px' } },
                labels: {
                    formatter: function () {
                        return `Rp ${(this.value / 1000000000).toLocaleString('id-ID')} M`;
                    },
                    style: { color: theme.text, fontSize: '11px' }
                },
                gridLineColor: theme.gridLine
            },
            legend: { enabled: false },
            tooltip: {
                formatter: function () {
                    const i = this.point.index;
                    const percentage = window.SimasterChartMetrics.format(window.SimasterChartMetrics.highcharts(this.point));
                    return `<b>${labels[i]} ${year}</b><br>` +
                        `Total biaya: <b>Rp ${Number(costs[i] || 0).toLocaleString('id-ID')}</b><br>` +
                        `Site memiliki tagihan: <b>${Number(siteCounts[i] || 0).toLocaleString('id-ID')}</b><br>` +
                        `Persentase total biaya: <b>${percentage}</b>`;
                }
            },
            series: [{
                name: 'Total Biaya Listrik',
                data: costs.map((value, index) => ({
                    y: Number(value) || 0,
                    color: barColors[index]
                }))
            }]
        });
    }

    function showElectricityPaymentDetail(year, month, nop) {
        const requestId = ++paymentDetailRequestId;
        if (paymentDetailRequest) paymentDetailRequest.abort();
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('electricityPaymentModal'));
        $('#electricity-payment-title').text(`Site Tanpa Flagging Pembayaran — ${monthNames[month - 1]} ${year}`);
        $('#electricity-payment-detail-summary').text('Memuat site pada snapshot yang belum memiliki nilai flagging pembayaran...');
        $('#electricity-payment-detail-body').html('<tr><td colspan="5" class="text-center text-body-secondary">Memuat data...</td></tr>');
        modal.show();

        paymentDetailRequest = $.ajax({
            url: "{{ route('dashboard.electricity-payment-detail') }}",
            method: 'GET',
            data: { tahun: year, bulan: month, nop: nop },
            timeout: 30000
        })
            .done(function (res) {
                if (requestId !== paymentDetailRequestId) return;
                const rows = (res.sites || []).map(site => `
                    <tr>
                        <td>${escapeHtml(site.site_id)}</td>
                        <td>${escapeHtml(site.site_name)}</td>
                        <td>${escapeHtml(site.id_pelanggan)}</td>
                        <td>${escapeHtml(site.nop)}</td>
                        <td><span class="badge text-bg-warning">Belum ada flagging</span></td>
                    </tr>
                `).join('');
                $('#electricity-payment-detail-summary').text(
                    `${Number(res.total || 0).toLocaleString('id-ID')} site pada snapshot belum memiliki flagging pembayaran` +
                    (res.limited ? ' (ditampilkan maksimal 5.000 site)' : '')
                );
                $('#electricity-payment-detail-body').html(
                    rows || '<tr><td colspan="5" class="text-center text-success">Semua site pada snapshot memiliki flagging pembayaran.</td></tr>'
                );
            })
            .fail(function (xhr) {
                if (requestId !== paymentDetailRequestId || xhr.statusText === 'abort') return;
                $('#electricity-payment-detail-summary').text(xhr.statusText === 'timeout'
                    ? 'Server terlalu lama merespons. Tutup rincian, lalu klik bulan untuk mencoba lagi.'
                    : 'Gagal mengambil detail pembayaran. Tutup rincian, lalu coba lagi.');
                $('#electricity-payment-detail-body').html('<tr><td colspan="5" class="text-center text-danger">Data tidak dapat dimuat.</td></tr>');
            })
            .always(function () {
                if (requestId === paymentDetailRequestId) paymentDetailRequest = null;
            });
    }

    $('#electricityPaymentModal').on('hidden.bs.modal', function () {
        ++paymentDetailRequestId;
        if (paymentDetailRequest) paymentDetailRequest.abort();
        paymentDetailRequest = null;
    });

    function escapeHtml(value) {
        return $('<div>').text(value == null || value === '' ? '-' : String(value)).html();
    }

    // 4. Render Analytics Financial Performance (Analytics Screen 2) - Click navigates to PnL month
    function renderAnalyticsPerformance(pnlData, theme) {
        const labels = pnlData.labels || [];
        const revData = pnlData.revenue || [];
        const costData = pnlData.cost || [];
        const pnlValues = pnlData.profit_loss || [];
        const periods = pnlData.periods || [];

        upsertChart('analyticsPerformance', 'chart-analytics-performance', {
            chart: { type: 'column' },
            exporting: { enabled: false },
            xAxis: {
                categories: labels,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                lineColor: theme.gridLine
            },
            yAxis: {
                title: { text: 'Miliar Rupiah (Rp)', style: { color: theme.text, fontSize: '11px' } },
                gridLineColor: theme.gridLine,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                plotLines: [{
                    value: 0,
                    color: theme.text,
                    width: 1.5,
                    zIndex: 5,
                    label: { text: 'Rp 0', align: 'right', style: { color: theme.text, fontSize: '10px' } }
                }]
            },
            plotOptions: {
                column: {
                    borderRadius: 5,
                    borderWidth: 0,
                    pointPadding: 0.1,
                    groupPadding: 0.15,
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function () {
                                const p = periods[this.index];
                                if (p) {
                                    navigateToPnl(p.tahun, p.bulan, cachedChartData?.filters?.nop);
                                }
                            }
                        }
                    }
                },
                spline: {
                    lineWidth: 3,
                    marker: { radius: 4 },
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function () {
                                const p = periods[this.index];
                                if (p) {
                                    navigateToPnl(p.tahun, p.bulan, cachedChartData?.filters?.nop);
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                itemStyle: { color: theme.text, fontSize: '12px' }
            },
            series: [
                { type: 'column', name: 'Revenue', data: revData, color: corpColors.primary },
                { type: 'column', name: 'Total Cost', data: costData, color: corpColors.danger },
                {
                    type: 'spline',
                    name: 'Net PnL',
                    data: pnlValues.map(value => ({
                        y: Number(value) || 0,
                    })),
                    color: corpColors.success,
                    zones: [
                        { value: 0, color: corpColors.danger },
                        { color: corpColors.success }
                    ]
                }
            ]
        });
    }

    // 5. Render Storage / Infrastructure Allocation Donut (Analytics Screen 2) - Click navigates to specific module
    function renderStorageAllocation(infraData, theme) {
        const moduleRouteMap = {
            'Sewa Lahan Renewal': routesMap.sewaLahan,
            'Combat Sites': routesMap.combat,
            'Recurring (ANT/Ipas)': routesMap.recurringIpas,
            'Sewa Lahan (Jaknet)': routesMap.jaknet,
            'BAPSS': routesMap.bapss,
            'Upload File PDF': routesMap.uploadFile
        };

        upsertChart('storageAllocation', 'chart-storage-allocation', {
            chart: { type: 'pie' },
            exporting: { enabled: false },
            plotOptions: {
                pie: {
                    innerSize: '68%',
                    borderWidth: 0,
                    cursor: 'pointer',
                    dataLabels: { enabled: false },
                    showInLegend: true,
                    point: {
                        events: {
                            click: function () {
                                const targetUrl = moduleRouteMap[this.name] || routesMap.sewaLahan;
                                window.location.href = targetUrl;
                            }
                        }
                    }
                }
            },
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom',
                itemStyle: { color: theme.text, fontSize: '10px' }
            },
            series: [{
                name: 'Items',
                data: (infraData || []).map((item, idx) => {
                    return { name: item.name, y: item.y, color: palette.series[idx % palette.series.length] };
                })
            }]
        });
    }

    // 6. Render Electricity Billing (Analytics Screen 2)
    function renderPeakUsage(plnData, theme) {
        const labels = plnData.labels || [];
        const values = plnData.values || [];
        const amounts = plnData.amounts || [];
        const siteCounts = plnData.site_counts || [];
        const recordCounts = plnData.record_counts || [];
        const periods = plnData.periods || [];

        const maxVal = Math.max(...values, 0);
        const barColors = palette.distinctColors(values.length);
        const dataWithPeak = values.map((v, index) => ({
            y: v,
            color: barColors[index],
            borderColor: (v === maxVal && maxVal > 0) ? corpColors.primaryDark : undefined,
            borderWidth: (v === maxVal && maxVal > 0) ? 2 : 0
        }));

        upsertChart('peakUsage', 'chart-peak-usage', {
            chart: { type: 'column' },
            exporting: { enabled: false },
            xAxis: {
                categories: labels,
                labels: { style: { color: theme.text, fontSize: '11px' } },
                lineColor: theme.gridLine
            },
            yAxis: {
                title: { text: 'Tagihan PLN (Rp Miliar)', style: { color: theme.text, fontSize: '11px' } },
                gridLineColor: theme.gridLine,
                labels: { style: { color: theme.text, fontSize: '11px' } }
            },
            legend: { enabled: false },
            tooltip: {
                formatter: function () {
                    const index = this.point.index;
                    return `<b>${labels[index]}</b><br>` +
                        `Total tagihan: <b>Rp ${Number(amounts[index] || 0).toLocaleString('id-ID')}</b><br>` +
                        `Site: <b>${Number(siteCounts[index] || 0).toLocaleString('id-ID')}</b><br>` +
                        `Rekening: <b>${Number(recordCounts[index] || 0).toLocaleString('id-ID')}</b>`;
                }
            },
            plotOptions: {
                column: {
                    borderRadius: 5,
                    borderWidth: 0,
                    cursor: 'pointer',
                    point: {
                        events: {
                            click: function () {
                                const p = periods[this.index];
                                if (p) {
                                    window.SimasterElectricityDetail.open('macro', p, 'billing', cachedChartData?.filters?.nop || '');
                                } else {
                                    window.location.href = routesMap.payment;
                                }
                            }
                        }
                    }
                }
            },
            series: [{
                name: 'Tagihan Listrik',
                data: dataWithPeak
            }]
        });
    }

    // 7. Render Error & Anomaly Distribution (Analytics Screen 2) - Click navigates to anomaly module
    function renderErrorDistribution(anomalies, theme) {
        const data = anomalies?.distribution || [];

        upsertChart('errorDistribution', 'chart-error-distribution', {
            chart: { type: 'pie' },
            exporting: { enabled: false },
            plotOptions: {
                pie: {
                    innerSize: '65%',
                    borderWidth: 0,
                    cursor: 'pointer',
                    dataLabels: { enabled: false },
                    showInLegend: true,
                    point: {
                        events: {
                            click: function () {
                                if (this.name.includes('PLN')) {
                                    window.location.href = routesMap.plnAnomali;
                                } else if (this.name.includes('Inbuilding')) {
                                    window.location.href = routesMap.ibcAnomali;
                                } else {
                                    window.location.href = routesMap.pnl;
                                }
                            }
                        }
                    }
                }
            },
            legend: {
                layout: 'horizontal',
                align: 'center',
                verticalAlign: 'bottom',
                itemStyle: { color: theme.text, fontSize: '11px' }
            },
            series: [{
                name: 'Kasus',
                data: data.map((point, index) => ({
                    ...point,
                    color: point.name.includes('Valid')
                        ? corpColors.success
                        : palette.series[(index + 3) % palette.series.length]
                }))
            }]
        });
    }

    // Render All Charts & UI
    function renderElectricityComparison(data, theme) {
        const renderer = window.SimasterElectricityComparison;
        $('#electricity-payment-caption').text(renderer.caption(data));
        $('#electricity-all-caption').text('Nominal pembayaran per bulan. Inquiry PLN dan transaksi IBC Pending tidak dihitung sebagai pembayaran. Bulan tanpa sumber ditandai belum tersedia.');
        const openDetail = function (key, period, metric) {
            if (!period) return;
            window.SimasterElectricityDetail.open(key, period, metric, cachedChartData?.filters?.nop || '');
        };
        upsertChart('electricityPayment', 'chart-electricity-payment', renderer.options(data, theme, 'sites', openDetail));
        upsertChart('electricityAll', 'chart-electricity-all', renderer.options(data, theme, 'amount', openDetail));
    }

    function renderAllCharts(res) {
        if (!res) return;
        cachedChartData = res;
        const global = res.global_summary || {};
        if (global.infraTotal !== undefined) $('#kpi-infra-count').text(Number(global.infraTotal).toLocaleString('id-ID'));
        if (global.poCount !== undefined) $('#kpi-po-count').text(Number(global.poCount).toLocaleString('id-ID'));
        Object.entries(global.moduleDataComposition || {}).forEach(([key, value]) => {
            $('[data-module-percentage="' + key + '"]').text(value.percentage + '% →');
            $('[data-module-progress="' + key + '"]').css('width', value.percentage + '%');
        });
        $('.dashboard-tab-panel').each(function () {
            $(this).find('.enterprise-kpi, .chart-card').each(function (index) {
                this.style.animationDelay = `${Math.min(index * 70, 420)}ms`;
            });
        }).addClass('dashboard-ready');
        const theme = getThemeStyles();
        applyHighchartsTheme();

        // Update KPI Summary on Screen 1
        const fin = res.financial_kpi || {};
        const plnKpi = res.pln_kpi || {};
        if (fin.total_sites !== undefined) {
            $('#kpi-sites-count').text(Number(fin.total_sites).toLocaleString('id-ID'));
        }

        if (plnKpi.pelanggan_count !== undefined) {
            $('#kpi-pln-pelanggan').text(Number(plnKpi.pelanggan_count).toLocaleString('id-ID'));
        }

        // Screen 1: Overview
        renderGrowthBars(res.pnl || {}, theme);
        renderCategoryDonut(res.pnl_status || [], theme);
        renderSiteOwners(res.site_owners || {}, theme);
        if (res.electricity_comparison) {
            renderElectricityComparison(res.electricity_comparison, theme);
        } else {
            renderElectricityPayment(res.electricity_payment || {}, theme);
            renderElectricityAll(res.electricity_all || {}, theme);
        }

        // Screen 2: Analytics
        renderAnalyticsPerformance(res.pnl || {}, theme);
        renderStorageAllocation(res.infra || [], theme);
        renderPeakUsage(res.pln || {}, theme);
        renderErrorDistribution(res.anomalies || {}, theme);
    }

    // Filter controls management
    function populateMonthOptions(tahun, selectedBulan) {
        const months = availablePeriods
            .filter(p => Number(p.tahun) === Number(tahun))
            .map(p => Number(p.bulan))
            .sort((a, b) => a - b);

        const $month = $('#dashboard-month').empty();
        $month.append($('<option>').val('all').text('Semua bulan'));
        months.forEach(b => {
            $month.append($('<option>').val(b).text(monthNames[b - 1]));
        });

        const bulan = selectedBulan === 'all'
            ? 'all'
            : months.includes(Number(selectedBulan))
            ? Number(selectedBulan)
            : months[months.length - 1];

        $month.val(bulan);
        return bulan;
    }

    function populateFilterControls(filters) {
        availablePeriods = filters.periods || [];
        selectedPeriod = {
            tahun: Number(filters.tahun),
            bulan: filters.all_months ? 'all' : Number(filters.bulan),
            nop: filters.nop || '',
        };

        const years = [...new Set(availablePeriods.map(p => Number(p.tahun)))].sort((a, b) => b - a);
        const $year = $('#dashboard-year').empty();
        years.forEach(t => $year.append($('<option>').val(t).text(t)));
        $year.val(selectedPeriod.tahun);

        selectedPeriod.bulan = populateMonthOptions(selectedPeriod.tahun, selectedPeriod.bulan);
        const $nop = $('#dashboard-nop').empty();
        $nop.append($('<option>').val('').text('Semua NOP'));
        (filters.nops || []).forEach(nop => $nop.append($('<option>').val(nop).text(nop)));
        $nop.val(selectedPeriod.nop);

        $('#dashboard-year, #dashboard-month').trigger('simaster:period-sync');
        $('#dashboard-year, #dashboard-month, #dashboard-nop').prop('disabled', false);
    }

    window.addEventListener('simaster:data-changed', function (event) { event.detail.handled = true; loadChartData(); });

    function loadChartData() {
        if (!selectedPeriod) return;
        const requestPeriod = { ...selectedPeriod };
        const key = chartCacheKey(requestPeriod);

        const requestId = ++chartRequestId;

        if (chartRequest) chartRequest.abort();
        $('#dashboard-year, #dashboard-month, #dashboard-nop').prop('disabled', true);
        setChartsLoading(true);

        chartRequest = $.ajax({
            url: chartApiUrl,
            method: 'GET',
            data: requestPeriod,
            success: function (res) {

                if (requestId !== chartRequestId || chartCacheKey(selectedPeriod) !== key) return;
                populateFilterControls(res.filters);
                renderAllCharts(res);
            },
            error: function (xhr) {
                if (requestId !== chartRequestId || xhr.statusText === 'abort' || chartCacheKey(selectedPeriod) !== key) return;
                const message = xhr.responseJSON?.message || 'Data dashboard tidak dapat dimuat.';
                cachedChartData = null;
                $('#electricity-payment-caption').text(message);
            },
            complete: function () {
                if (requestId === chartRequestId) {
                    setChartsLoading(false);
                    chartRequest = null;
                    $('#dashboard-year, #dashboard-month, #dashboard-nop').prop('disabled', false);
                }
            }
        });
    }

    $('#dashboard-year').on('change', function () {
        const tahun = Number(this.value);
        selectedPeriod = {
            tahun,
            bulan: populateMonthOptions(tahun, 'all'),
            nop: $('#dashboard-nop').val() || '',
        };
        loadChartData();
    });

    $('#dashboard-month').on('change', function () {
        selectedPeriod = {
            tahun: Number($('#dashboard-year').val()),
            bulan: this.value === 'all' ? 'all' : Number(this.value),
            nop: $('#dashboard-nop').val() || '',
        };
        loadChartData();
    });

    $('#dashboard-nop').on('change', function () {
        selectedPeriod = {
            tahun: Number($('#dashboard-year').val()),
            bulan: $('#dashboard-month').val() === 'all' ? 'all' : Number($('#dashboard-month').val()),
            nop: this.value || '',
        };
        loadChartData();
    });

    // Drawer Logic for PnL Site Contributors
    let currentDrawerItems = [];
    const siteDrawerEl = document.getElementById('siteDrawer');
    const siteDrawer = siteDrawerEl ? new bootstrap.Modal(siteDrawerEl) : null;

    function openSiteDrawer(statusName) {
        if (!siteDrawer || !cachedChartData) return;
        let statusFilter = '';
        let isOwnerFilter = false;
        let ownerName = '';

        if (statusName && statusName.startsWith('Owner: ')) {
            isOwnerFilter = true;
            ownerName = statusName.replace('Owner: ', '').trim();
        } else if (statusName && statusName.includes('Profit')) {
            statusFilter = 'profit';
        } else if (statusName && statusName.includes('Loss')) {
            statusFilter = 'loss';
        } else if (statusName && statusName.includes('Tidak')) {
            statusFilter = 'inactive';
        }

        const bulan = cachedChartData.filters.all_months
            ? cachedChartData.filters.status_bulan
            : cachedChartData.filters.bulan;
        const tahun = cachedChartData.filters.tahun;

        $('#drawer-title').text(isOwnerFilter ? `Site Kontributor — ${ownerName}` : `Site Kontributor: ${statusName}`);
        $('#site-owner-detail-export')
            .toggleClass('d-none', !isOwnerFilter)
            .attr('href', isOwnerFilter ? siteOwnerExportUrl(ownerName) : routesMap.siteOwnerExport);
        $('#drawer-tbody').html('<tr><td colspan="3" class="text-center py-4 text-body-secondary">Mengambil data site...</td></tr>');
        siteDrawer.show();

        const reqData = {
            bulan: bulan,
            tahun: tahun,
            status: statusFilter,
            nop: cachedChartData.filters.nop || '',
            owner: isOwnerFilter ? ownerName : '',
            length: 150
        };

        $.ajax({
            url: routesMap.pnlData,
            method: 'GET',
            data: reqData,
            success: function (resp) {
                currentDrawerItems = resp.data || [];
                renderDrawerRows(currentDrawerItems);
            },
            error: function () {
                $('#drawer-tbody').html('<tr><td colspan="3" class="text-center py-4 text-danger">Gagal memuat data site.</td></tr>');
            }
        });
    }

    function renderDrawerRows(items) {
        const $tbody = $('#drawer-tbody').empty();
        if (items.length === 0) {
            $tbody.html('<tr><td colspan="3" class="text-center py-4 text-body-secondary">Tidak ada data site yang sesuai.</td></tr>');
            $('#drawer-count-info').text('0 site ditemukan');
            return;
        }

        $('#drawer-count-info').text(`Menampilkan ${items.length} site kontributor`);
        items.forEach(site => {
            $tbody.append(`
                <tr>
                    <td class="fw-bold">${site.site_id || '—'}</td>
                    <td class="text-truncate" style="max-width: 170px;" title="${site.site_name || ''}">${site.site_name || '—'}</td>
                    <td class="text-wrap" style="min-width: 260px;">${site.alamat || '—'}</td>
                </tr>
            `);
        });
    }

    $('#drawer-search').on('input', function () {
        const query = (this.value || '').toLowerCase().trim();
        $('#drawer-search-clear').toggleClass('d-none', query === '');
        if (!query) {
            renderDrawerRows(currentDrawerItems);
            return;
        }
        const filtered = currentDrawerItems.filter(item =>
            (item.site_id && item.site_id.toLowerCase().includes(query)) ||
            (item.site_name && item.site_name.toLowerCase().includes(query)) ||
            (item.alamat && item.alamat.toLowerCase().includes(query))
        );
        renderDrawerRows(filtered);
    });

    $('#drawer-search-clear').on('click', function () {
        $('#drawer-search').val('').trigger('input').focus();
    });

    // KPI Cards Click Handlers
    $('#kpi-card-sites').on('click', function () {
        const p = selectedPeriod || {};
        navigateToPnl(p.tahun, p.bulan, p.nop);
    });

    $('#kpi-card-pln').on('click', function () {
        const p = selectedPeriod || {};
        navigateToPln(p.tahun, p.bulan, p.nop);
    });

    $('#dashboard-pln-link').on('click', function (event) {
        event.preventDefault();
        const p = selectedPeriod || {};
        navigateToPln(p.tahun, p.bulan, p.nop);
    });

    $('#kpi-card-infra').on('click', function () {
        window.location.href = routesMap.infrastructure;
    });

    $('#kpi-card-po').on('click', function () {
        window.location.href = routesMap.poHq;
    });

    // Theme changes re-rendering
    window.addEventListener('simaster:theme-changed', function () {
        if (cachedChartData) {
            renderAllCharts(cachedChartData);
        }
    });

    // Initial Load
    selectedPeriod = { tahun: null, bulan: null, nop: '' };
    loadChartData();
});
</script>
@endpush
