@extends('layouts.app')

@section('title', 'Electricity Monitoring')

@push('styles')
<style>
    .electricity-hero {
        background:
            radial-gradient(circle at 88% 20%, rgba(59, 130, 246, .18), transparent 34%),
            linear-gradient(135deg, #0f172a 0%, #172554 58%, #1d4ed8 140%);
        color: #fff;
        border: 0;
        border-radius: 18px;
        overflow: hidden;
        position: relative;
    }
    .electricity-hero::after {
        content: '';
        position: absolute;
        width: 220px;
        height: 220px;
        right: -85px;
        bottom: -120px;
        border: 28px solid rgba(255, 255, 255, .07);
        border-radius: 50%;
        pointer-events: none;
    }
    .electricity-eyebrow {
        color: #93c5fd;
        font-size: .73rem;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }
    .electricity-hero .btn-electricity-link {
        color: #e0f2fe;
        border-color: rgba(255, 255, 255, .24);
        background: rgba(255, 255, 255, .08);
        backdrop-filter: blur(6px);
    }
    .electricity-hero .btn-electricity-link:hover {
        color: #fff;
        border-color: rgba(255, 255, 255, .5);
        background: rgba(255, 255, 255, .14);
    }
    .electricity-kpi .kpi-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 11px;
        background: rgba(37, 99, 235, .1);
        color: #2563eb;
    }
    .electricity-kpi .stat-number {
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        line-height: 1.15;
    }
    .electricity-kpi.is-loading,
    .electricity-chart-card.is-loading {
        opacity: .58;
        pointer-events: none;
    }
    .electricity-chart-card {
        min-height: 100%;
        transition: opacity .2s ease;
    }
    [data-electricity-panel][hidden] { display: none !important; }
    .electricity-tabs-nav { display: flex; flex-wrap: wrap; gap: .25rem; }
    .electricity-chart {
        min-height: 330px;
        width: 100%;
    }
    .electricity-chart-sm { min-height: 300px; }
    .electricity-progress {
        height: 7px;
        border-radius: 999px;
        background: #e2e8f0;
        overflow: hidden;
    }
    .electricity-progress > span {
        display: block;
        height: 100%;
        width: 0;
        border-radius: inherit;
        background: linear-gradient(90deg, #16a34a, #22c55e);
        transition: width .35s ease;
    }
    .electricity-source-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: .4rem;
    }
    .electricity-summary-table td,
    .electricity-summary-table th,
    .electricity-anomaly-table td,
    .electricity-anomaly-table th {
        white-space: nowrap;
        vertical-align: middle;
    }
    .electricity-anomaly-table tbody tr { cursor: pointer; }
    .electricity-anomaly-table tbody tr:hover { background: rgba(37, 99, 235, .055); }
    .electricity-empty {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--bs-secondary-color);
        text-align: center;
    }
    [data-bs-theme="dark"] .electricity-kpi .kpi-icon {
        background: rgba(59, 130, 246, .18);
        color: #60a5fa;
    }
    [data-bs-theme="dark"] .electricity-progress { background: #1e293b; }
    [data-bs-theme="dark"] .electricity-anomaly-table tbody tr:hover { background: rgba(59, 130, 246, .1); }
    @media (max-width: 767.98px) {
        .electricity-chart { min-height: 285px; }
        .electricity-hero { border-radius: 14px; }
    }
    @media (prefers-reduced-motion: reduce) {
        .electricity-progress > span { transition: none; }
    }
</style>
@endpush

@section('content')
    <section class="electricity-hero shadow-sm mb-4 p-4">
        <div class="row align-items-center g-4 position-relative" style="z-index: 1;">
            <div class="col-lg-7">
                <div class="electricity-eyebrow mb-2">SIMAWAR · Electricity Monitoring</div>
                <h1 class="h2 fw-bold mb-0">Electricity Monitoring</h1>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <a href="{{ route('electricity.centralized.listrik-pln.index') }}" class="btn btn-sm btn-electricity-link">Master PLN</a>
                    <a href="{{ route('electricity.centralized.payment.index') }}" class="btn btn-sm btn-electricity-link">Payment PLN</a>
                    <a href="{{ route('electricity.inbuilding.listrik-inbuilding.index') }}" class="btn btn-sm btn-electricity-link">Master Inbuilding</a>
                    <a href="{{ route('electricity.inbuilding.payment.index') }}" class="btn btn-sm btn-electricity-link">Payment IBC</a>
                </div>
            </div>
        </div>
    </section>

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;" data-simaster-filter-panel="Filter Electricity Monitoring">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div>
                    <label for="electricity-scope" class="form-label small text-body-secondary mb-1">Cakupan</label>
                    <select id="electricity-scope" class="form-select form-select-sm" style="min-width: 155px;">
                        <option value="all">Semua Electricity</option>
                        <option value="centralized">Centralized</option>
                        <option value="inbuilding">Inbuilding</option>
                    </select>
                </div>
                <div>
                    <label for="electricity-year" class="form-label small text-body-secondary mb-1">Tahun</label>
                    <select id="electricity-year" class="form-select form-select-sm" style="min-width: 105px;" disabled></select>
                </div>
                <div>
                    <label for="electricity-month" class="form-label small text-body-secondary mb-1">Bulan</label>
                    <select id="electricity-month" class="form-select form-select-sm" style="min-width: 140px;" disabled></select>
                </div>
                <div>
                    <label for="electricity-nop" class="form-label small text-body-secondary mb-1">NOP <span class="fw-normal">(Centralized)</span></label>
                    <select id="electricity-nop" class="form-select form-select-sm" style="min-width: 170px;" disabled></select>
                </div>
                <button type="button" id="electricity-reset" class="btn btn-sm btn-outline-secondary">Reset Filter</button>
                <div class="ms-lg-auto small text-body-secondary pb-1" id="electricity-filter-caption">Memuat periode data…</div>
            </div>
        </div>
    </div>

    <div id="electricity-error" class="alert alert-danger d-none" role="alert"></div>

    <div class="enterprise-tabs-nav electricity-tabs-nav mb-4" role="tablist" aria-label="Submodul Electricity Monitoring">
        <button type="button" class="enterprise-tab-btn active" id="electricity-tab-overview" role="tab" aria-selected="true" aria-controls="electricity-panel-overview" data-electricity-tab="overview">Ringkasan &amp; Pembayaran</button>
        <button type="button" class="enterprise-tab-btn" id="electricity-tab-sites" role="tab" aria-selected="false" aria-controls="electricity-panel-sites" data-electricity-tab="sites" tabindex="-1">Site &amp; Kapasitas</button>
        <button type="button" class="enterprise-tab-btn" id="electricity-tab-anomalies" role="tab" aria-selected="false" aria-controls="electricity-panel-anomalies" data-electricity-tab="anomalies" tabindex="-1">Anomali Tagihan</button>
    </div>
    <section id="electricity-panel-overview" role="tabpanel" aria-labelledby="electricity-tab-overview" data-electricity-panel="overview">
    <div class="row g-3 mb-4" aria-live="polite">
        <div class="col-6 col-xl-2">
            <article class="enterprise-kpi electricity-kpi is-loading">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="summary-card-title text-body-secondary">Total Site</span>
                    <span class="kpi-icon" aria-hidden="true">⌂</span>
                </div>
                <div class="stat-number fw-bold" id="kpi-electricity-sites">—</div>
            </article>
        </div>
        <div class="col-6 col-xl-2">
            <article class="enterprise-kpi electricity-kpi is-loading">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="summary-card-title text-body-secondary">Kapasitas Daya</span>
                    <span class="kpi-icon" aria-hidden="true">ϟ</span>
                </div>
                <div class="stat-number fw-bold" id="kpi-electricity-capacity">—</div>
            </article>
        </div>
        <div class="col-6 col-xl-2">
            <article class="enterprise-kpi electricity-kpi is-loading">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="summary-card-title text-body-secondary">Total Tagihan</span>
                    <span class="kpi-icon" aria-hidden="true">Rp</span>
                </div>
                <div class="stat-number fw-bold" id="kpi-electricity-billed">—</div>
            </article>
        </div>
        <div class="col-6 col-xl-2">
            <article class="enterprise-kpi electricity-kpi is-loading">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="summary-card-title text-body-secondary">Pembayaran</span>
                    <span class="kpi-icon" aria-hidden="true">✓</span>
                </div>
                <div class="stat-number fw-bold" id="kpi-electricity-rate">—</div>
                <div class="electricity-progress mt-2"><span id="kpi-electricity-rate-bar"></span></div>
            </article>
        </div>
        <div class="col-6 col-xl-2">
            <article class="enterprise-kpi electricity-kpi is-loading">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="summary-card-title text-body-secondary">Anomali</span>
                    <span class="kpi-icon text-danger" aria-hidden="true">!</span>
                </div>
                <div class="stat-number fw-bold" id="kpi-electricity-anomalies">—</div>
            </article>
        </div>
        <div class="col-6 col-xl-2">
            <article class="enterprise-kpi electricity-kpi is-loading">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="summary-card-title text-body-secondary">Tindak Lanjut</span>
                    <span class="kpi-icon text-warning" aria-hidden="true">↗</span>
                </div>
                <div class="stat-number fw-bold" id="kpi-electricity-pending">—</div>
            </article>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <section class="enterprise-card electricity-chart-card is-loading">
                <header class="enterprise-card-header"><div>
                    <h2 class="enterprise-card-title">Cakupan Data Pembayaran Listrik — Makro dan Inbuilding</h2>
                    <div id="electricity-comparison-caption" class="small text-body-secondary mt-1"></div>
                </div></header>
                <div class="enterprise-card-body"><div id="chart-electricity-comparison-sites" style="height:430px"></div></div>
            </section>
        </div>
        <div class="col-12">
            <section class="enterprise-card electricity-chart-card is-loading">
                <header class="enterprise-card-header"><div>
                    <h2 class="enterprise-card-title">Tren Nominal Pembayaran Listrik — Makro dan Inbuilding</h2>
                    <div class="small text-body-secondary mt-1">Flagging Listrik All dan Payment IBC berstatus terbayar. Inquiry dan Pending tidak dihitung sebagai pembayaran.</div>
                </div></header>
                <div class="enterprise-card-body"><div id="chart-electricity-comparison-amount" style="height:430px"></div></div>
            </section>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <section class="enterprise-card electricity-chart-card is-loading h-100">
                <header class="enterprise-card-header">
                    <div>
                        <h2 class="enterprise-card-title">Tren Biaya Listrik Bulanan</h2>
                        <div class="small text-body-secondary mt-1">Perbandingan tagihan Centralized dan Inbuilding</div>
                    </div>
                    <span class="badge text-bg-light border" id="billing-period-badge">—</span>
                </header>
                <div class="enterprise-card-body"><div id="chart-electricity-billing" class="electricity-chart"></div></div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="enterprise-card electricity-chart-card is-loading h-100">
                <header class="enterprise-card-header">
                    <div>
                        <h2 class="enterprise-card-title">Status Pembayaran</h2>
                        <div class="small text-body-secondary mt-1">Proporsi nominal terbayar dan tertunda</div>
                    </div>
                </header>
                <div class="enterprise-card-body"><div id="chart-electricity-payment" class="electricity-chart electricity-chart-sm"></div></div>
            </section>
        </div>
    </div>

    </section>
    <section id="electricity-panel-sites" role="tabpanel" aria-labelledby="electricity-tab-sites" data-electricity-panel="sites" hidden>
    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <section class="enterprise-card electricity-chart-card is-loading h-100">
                <header class="enterprise-card-header">
                    <div>
                        <h2 class="enterprise-card-title">Komposisi Site</h2>
                        <div class="small text-body-secondary mt-1">Centralized dibandingkan Inbuilding</div>
                    </div>
                </header>
                <div class="enterprise-card-body"><div id="chart-electricity-sites" class="electricity-chart electricity-chart-sm"></div></div>
            </section>
        </div>
        <div class="col-xl-7">
            <section class="enterprise-card electricity-chart-card is-loading h-100">
                <header class="enterprise-card-header">
                    <div>
                        <h2 class="enterprise-card-title">Sebaran Site Centralized per NOP</h2>
                        <div class="small text-body-secondary mt-1">Jumlah site dan kapasitas daya pada master PLN</div>
                    </div>
                </header>
                <div class="enterprise-card-body"><div id="chart-electricity-nop" class="electricity-chart electricity-chart-sm"></div></div>
            </section>
        </div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <section class="enterprise-card h-100">
                <header class="enterprise-card-header">
                    <div>
                        <h2 class="enterprise-card-title">Ringkasan Operasional</h2>
                    </div>
                </header>
                <div class="enterprise-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover electricity-summary-table mb-0">
                            <thead class="table-light">
                                <tr><th class="ps-4">Sumber</th><th>Site</th><th>Aktif</th><th class="text-end pe-4">Tagihan</th></tr>
                            </thead>
                            <tbody id="electricity-summary-body">
                                <tr><td colspan="4" class="text-center text-body-secondary py-5">Memuat data…</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
    </section>
    <section id="electricity-panel-anomalies" role="tabpanel" aria-labelledby="electricity-tab-anomalies" data-electricity-panel="anomalies" hidden>
    <div class="row g-4 mb-4">
        <div class="col-xl-5">
            <section class="enterprise-card electricity-chart-card is-loading h-100">
                <header class="enterprise-card-header">
                    <div>
                        <h2 class="enterprise-card-title">Anomali Tagihan per Bulan</h2>
                        <div class="small text-body-secondary mt-1">Deteksi perubahan tagihan signifikan dari kedua sumber</div>
                    </div>
                </header>
                <div class="enterprise-card-body"><div id="chart-electricity-anomaly" class="electricity-chart electricity-chart-sm"></div></div>
            </section>
        </div>
        <div class="col-xl-7">
    <section class="enterprise-card mb-4">
        <header class="enterprise-card-header">
            <div>
                <h2 class="enterprise-card-title">Anomali Prioritas</h2>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('electricity.centralized.anomali.index') }}" class="btn btn-sm btn-outline-primary">Anomali PLN</a>
                <a href="{{ route('electricity.inbuilding.anomali.index') }}" class="btn btn-sm btn-outline-primary">Anomali IBC</a>
            </div>
        </header>
        <div class="enterprise-card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover electricity-anomaly-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Sumber</th><th>Site ID</th><th>Periode</th>
                            <th class="text-end">Tagihan Sebelumnya</th><th class="text-end">Tagihan Saat Ini</th>
                            <th class="text-end">Selisih</th><th class="text-end pe-4">Kenaikan</th>
                        </tr>
                    </thead>
                    <tbody id="electricity-anomaly-body">
                        <tr><td colspan="7" class="text-center text-body-secondary py-5">Memuat data…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
        </div>
    </div>
    </section>
    @include('electricity.partials.payment-detail-modal')
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script src="{{ asset('js/electricity-comparison-charts.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const dataUrl = @json(route('electricity.dashboard.data'));
    const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    const controls = {
        scope: document.getElementById('electricity-scope'),
        year: document.getElementById('electricity-year'),
        month: document.getElementById('electricity-month'),
        nop: document.getElementById('electricity-nop'),
    };
    const charts = {};
    let currentData = null;
    let requestController = null;
    let filtersInitialized = false;

    const numberFormatter = new Intl.NumberFormat('id-ID');
    const percentFormatter = new Intl.NumberFormat('id-ID', { minimumFractionDigits: 1, maximumFractionDigits: 1 });

    function compactCurrency(value) {
        const amount = Number(value || 0);
        if (Math.abs(amount) >= 1e12) return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(amount / 1e12)} T`;
        if (Math.abs(amount) >= 1e9) return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(amount / 1e9)} M`;
        if (Math.abs(amount) >= 1e6) return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(amount / 1e6)} Jt`;
        return `Rp ${numberFormatter.format(Math.round(amount))}`;
    }

    function fullCurrency(value) {
        return `Rp ${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(Number(value || 0))}`;
    }

    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = String(value ?? '');
        return element.innerHTML;
    }

    function compactPower(value) {
        const va = Number(value || 0);
        if (va >= 1e6) return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(va / 1e6)} MVA`;
        return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(va / 1e3)} kVA`;
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }

    function setLoading(loading) {
        document.querySelectorAll('.electricity-kpi, .electricity-chart-card').forEach((card) => {
            card.classList.toggle('is-loading', loading);
        });
    }

    function showError(message = '') {
        const element = document.getElementById('electricity-error');
        element.textContent = message;
        element.classList.toggle('d-none', !message);
    }

    function isDark() {
        return document.documentElement.getAttribute('data-bs-theme') === 'dark';
    }

    function baseChartOptions() {
        const dark = isDark();
        return {
            chart: { backgroundColor: 'transparent', animation: { duration: 350 }, style: { fontFamily: 'inherit' } },
            title: { text: null },
            credits: { enabled: false },
            exporting: { enabled: false },
            legend: { itemStyle: { color: dark ? '#cbd5e1' : '#475569', fontWeight: '600' }, itemHoverStyle: { color: dark ? '#fff' : '#0f172a' } },
            xAxis: { lineColor: dark ? '#334155' : '#e2e8f0', tickColor: dark ? '#334155' : '#e2e8f0', labels: { style: { color: dark ? '#94a3b8' : '#64748b' } } },
            yAxis: { gridLineColor: dark ? '#475569' : '#cbd5e1', gridLineWidth: 1, gridLineDashStyle: 'ShortDash', labels: { style: { color: dark ? '#94a3b8' : '#64748b' } }, title: { style: { color: dark ? '#94a3b8' : '#64748b' } } },
            tooltip: { backgroundColor: dark ? '#0f172a' : '#fff', borderColor: dark ? '#334155' : '#e2e8f0', style: { color: dark ? '#f8fafc' : '#0f172a' } },
        };
    }

    function upsertChart(key, container, options) {
        if (charts[key]) charts[key].destroy();
        charts[key] = Highcharts.chart(container, Highcharts.merge(baseChartOptions(), options));
    }

    function renderKpis(data) {
        const kpi = data.kpi;
        setText('kpi-electricity-sites', numberFormatter.format(kpi.total_sites));
        setText('kpi-electricity-capacity', compactPower(kpi.total_capacity_va));
        setText('kpi-electricity-billed', compactCurrency(kpi.total_billed));
        setText('kpi-electricity-rate', `${percentFormatter.format(kpi.payment_rate)}%`);
        document.getElementById('kpi-electricity-rate-bar').style.width = `${Math.min(100, Math.max(0, kpi.payment_rate))}%`;
        setText('kpi-electricity-anomalies', numberFormatter.format(kpi.anomalies));
        setText('kpi-electricity-pending', numberFormatter.format(kpi.pending_records));
        setText('billing-period-badge', data.meta.period_label);
        setText('electricity-filter-caption', `${data.meta.scope_label} · ${data.meta.period_label}`);
    }

    function renderBillingChart(data) {
        const renderer = window.SimasterElectricityComparison;
        const theme = { text: isDark() ? '#e2e8f0' : '#334155', gridLine: isDark() ? '#475569' : '#cbd5e1' };
        const comparison = data.electricity_comparison || { labels: [], periods: [], series: [], notes: [] };
        setText('electricity-comparison-caption', renderer.caption(comparison));
        const openPayment = (key, period, metric) => {
            if (!period) return;
            window.SimasterElectricityDetail.open(key, period, metric, data.filters.nop || '');
        };
        upsertChart('comparisonSites', 'chart-electricity-comparison-sites', renderer.options(comparison, theme, 'sites', openPayment));
        upsertChart('comparisonAmount', 'chart-electricity-comparison-amount', renderer.options(comparison, theme, 'amount', openPayment));
        const trend = data.billing_trend;
        upsertChart('billing', 'chart-electricity-billing', {
            chart: { type: 'column' },
            xAxis: { categories: trend.labels },
            yAxis: { min: 0, title: { text: 'Nominal tagihan' }, labels: { formatter() { return compactCurrency(this.value); } } },
            tooltip: { shared: true, formatter() {
                const rows = this.points.map((point) => `<span style="color:${point.color}">●</span> ${point.series.name}: <b>${fullCurrency(point.y)}</b>`).join('<br>');
                return `<b>${this.x}</b><br>${rows}`;
            } },
            plotOptions: { column: { borderWidth: 0, borderRadius: 4, groupPadding: .12, cursor: 'pointer', point: { events: { click() {
                window.SimasterElectricityDetail.open(this.series.options.custom.source, trend.periods[this.index], 'billing', data.filters.nop || '');
            } } } } },
            series: [
                { name: 'Centralized', data: trend.centralized, color: '#2563eb', custom: { source: 'macro' } },
                { name: 'Inbuilding', data: trend.inbuilding, color: '#06b6d4', custom: { source: 'inbuilding' } },
                { name: 'Belum Terbayar', data: trend.pending, color: '#f59e0b', type: 'spline', marker: { radius: 3 }, lineWidth: 2 },
            ],
        });
    }

    function renderPaymentChart(data) {
        const points = data.payment_status.map((item, index) => ({
            name: item.name,
            y: Number(item.amount),
            records: Number(item.records),
            color: index === 0 ? '#16a34a' : '#f59e0b',
        }));
        upsertChart('payment', 'chart-electricity-payment', {
            chart: { type: 'pie' },
            tooltip: { pointFormatter() { return `<b>${fullCurrency(this.y)}</b><br>${numberFormatter.format(this.records)} transaksi`; } },
            plotOptions: { pie: { innerSize: '66%', borderWidth: 0, dataLabels: { enabled: true, formatter() { return this.y > 0 ? `${this.point.name}<br><b>${percentFormatter.format(this.percentage)}%</b>` : null; }, style: { textOutline: 'none' } } } },
            series: [{ name: 'Pembayaran', data: points }],
        });
    }

    function renderAnomalyChart(data) {
        upsertChart('anomaly', 'chart-electricity-anomaly', {
            chart: { type: 'column' },
            xAxis: { categories: data.anomaly_trend.labels },
            yAxis: { min: 0, allowDecimals: false, title: { text: 'Jumlah anomali' } },
            tooltip: { shared: true },
            plotOptions: { column: { borderWidth: 0, borderRadius: 4 } },
            series: [
                { name: 'Centralized', data: data.anomaly_trend.centralized, color: '#ef4444' },
                { name: 'Inbuilding', data: data.anomaly_trend.inbuilding, color: '#f97316' },
            ],
        });
    }

    function renderSiteChart(data) {
        const points = data.site_distribution.map((item, index) => ({
            name: item.name,
            y: Number(item.sites),
            color: index === 0 ? '#2563eb' : '#06b6d4',
        }));
        upsertChart('sites', 'chart-electricity-sites', {
            chart: { type: 'pie' },
            tooltip: { pointFormatter() { return `<b>${numberFormatter.format(this.y)} site</b><br>${percentFormatter.format(this.percentage)}%`; } },
            plotOptions: { pie: { innerSize: '58%', borderWidth: 0, dataLabels: { enabled: true, format: '{point.name}<br><b>{point.y:,.0f}</b>', style: { textOutline: 'none' } } } },
            series: [{ name: 'Site', data: points }],
        });
    }

    function renderNopChart(data) {
        const rows = data.nop_distribution || [];
        if (!rows.length) {
            if (charts.nop) {
                charts.nop.destroy();
                charts.nop = null;
            }
            document.getElementById('chart-electricity-nop').innerHTML = '<div class="electricity-empty">Sebaran NOP hanya tersedia untuk data Centralized.</div>';
            return;
        }
        upsertChart('nop', 'chart-electricity-nop', {
            chart: { type: 'bar' },
            colors: window.SimasterChartPalette.distinctColors(rows.length),
            xAxis: { categories: rows.map((item) => item.name) },
            yAxis: { min: 0, allowDecimals: false, title: { text: 'Jumlah site' } },
            tooltip: { formatter() {
                const row = rows[this.point.index];
                return `<b>${escapeHtml(row.name)}</b><br>${numberFormatter.format(row.sites)} site<br>${compactPower(row.capacity_va)}`;
            } },
            legend: { enabled: false },
            plotOptions: { bar: { borderWidth: 0, borderRadius: 4, colorByPoint: true } },
            series: [{ name: 'Site', data: rows.map((item) => item.sites) }],
        });
    }

    function appendCell(row, value, className = '') {
        const cell = document.createElement('td');
        cell.textContent = value;
        if (className) cell.className = className;
        row.appendChild(cell);
        return cell;
    }

    function renderSummary(data) {
        const body = document.getElementById('electricity-summary-body');
        body.replaceChildren();
        if (!data.site_distribution.length) {
            const row = document.createElement('tr');
            appendCell(row, 'Tidak ada data untuk filter ini.', 'text-center text-body-secondary py-5');
            row.firstChild.colSpan = 4;
            body.appendChild(row);
            return;
        }
        data.site_distribution.forEach((item, index) => {
            const row = document.createElement('tr');
            const sourceCell = appendCell(row, item.name, 'ps-4 fw-semibold');
            const dot = document.createElement('span');
            dot.className = 'electricity-source-dot';
            dot.style.backgroundColor = index === 0 && item.name === 'Centralized' ? '#2563eb' : '#06b6d4';
            sourceCell.prepend(dot);
            appendCell(row, numberFormatter.format(item.sites));
            appendCell(row, numberFormatter.format(item.active_sites));
            appendCell(row, compactCurrency(item.billed), 'text-end pe-4 fw-semibold');
            body.appendChild(row);
        });
    }

    function renderAnomalyTable(data) {
        const body = document.getElementById('electricity-anomaly-body');
        body.replaceChildren();
        if (!data.latest_anomalies.length) {
            const row = document.createElement('tr');
            appendCell(row, 'Tidak ada anomali pada periode dan cakupan yang dipilih.', 'text-center text-body-secondary py-5');
            row.firstChild.colSpan = 7;
            body.appendChild(row);
            return;
        }
        data.latest_anomalies.forEach((item) => {
            const row = document.createElement('tr');
            row.tabIndex = 0;
            row.setAttribute('role', 'link');
            const open = () => { window.location.href = item.url; };
            row.addEventListener('click', open);
            row.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); open(); } });
            appendCell(row, item.source, 'ps-4 fw-semibold');
            appendCell(row, item.site_id);
            appendCell(row, item.period);
            appendCell(row, fullCurrency(item.previous_bill), 'text-end');
            appendCell(row, fullCurrency(item.current_bill), 'text-end');
            appendCell(row, fullCurrency(item.difference), `text-end ${Number(item.difference) >= 0 ? 'text-danger' : 'text-success'}`);
            appendCell(row, `${percentFormatter.format(item.increase_percent)}%`, 'text-end pe-4 fw-semibold text-danger');
            body.appendChild(row);
        });
    }

    function renderAll(data) {
        renderKpis(data);
        renderBillingChart(data);
        renderPaymentChart(data);
        renderAnomalyChart(data);
        renderSiteChart(data);
        renderNopChart(data);
        renderSummary(data);
        renderAnomalyTable(data);
    }

    function replaceOptions(select, options, selectedValue) {
        select.replaceChildren();
        options.forEach(({ value, label }) => {
            const option = document.createElement('option');
            option.value = String(value);
            option.textContent = label;
            option.selected = String(value) === String(selectedValue);
            select.appendChild(option);
        });
    }

    function populateFilters(filters) {
        controls.scope.value = filters.scope;
        replaceOptions(controls.year, filters.years.map((year) => ({ value: year, label: year })), filters.tahun);
        const availableMonths = filters.periods
            .filter((period) => Number(period.tahun) === Number(filters.tahun))
            .map((period) => Number(period.bulan));
        if (filters.bulan) availableMonths.push(Number(filters.bulan));
        replaceOptions(controls.month, [
            { value: 'all', label: 'Semua Bulan' },
            ...[...new Set(availableMonths)].sort((a, b) => a - b).map((month) => ({ value: month, label: monthNames[month - 1] })),
        ], filters.bulan ?? 'all');
        replaceOptions(controls.nop, [
            { value: '', label: 'Semua NOP' },
            ...filters.nops.map((nop) => ({ value: nop, label: nop })),
        ], filters.nop ?? '');
        controls.year.disabled = false;
        controls.month.disabled = false;
        controls.nop.disabled = filters.scope === 'inbuilding';
        filtersInitialized = true;
    }

    function queryFromControls() {
        if (!filtersInitialized) return {};
        return {
            scope: controls.scope.value,
            tahun: controls.year.value,
            bulan: controls.month.value || 'all',
            nop: controls.nop.value,
        };
    }

    async function loadDashboard(parameters = queryFromControls()) {
        if (requestController) requestController.abort();
        const activeController = new AbortController();
        requestController = activeController;
        setLoading(true);
        showError();
        const url = new URL(dataUrl, window.location.origin);
        Object.entries(parameters).forEach(([key, value]) => {
            if (value !== '' && value !== null && value !== undefined) url.searchParams.set(key, value);
        });
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, signal: activeController.signal });
            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'Data dashboard tidak dapat dimuat.');
            }
            currentData = await response.json();
            populateFilters(currentData.filters);
            renderAll(currentData);
        } catch (error) {
            if (error.name !== 'AbortError') showError(error.message || 'Terjadi kesalahan saat memuat dashboard.');
        } finally {
            if (requestController === activeController) setLoading(false);
        }
    }

    Object.values(controls).forEach((control) => control.addEventListener('change', () => {
        if (control === controls.scope && controls.scope.value === 'inbuilding') controls.nop.value = '';
        if (control === controls.nop && controls.nop.value) controls.scope.value = 'centralized';
        loadDashboard();
    }));

    document.getElementById('electricity-reset').addEventListener('click', () => {
        controls.scope.value = 'all';
        controls.nop.value = '';
        filtersInitialized = false;
        loadDashboard({ scope: 'all', bulan: 'all' });
    });

    new MutationObserver(() => {
        if (currentData) renderAll(currentData);
    }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });

    const moduleTabs = Array.from(document.querySelectorAll('[data-electricity-tab]'));
    function activateElectricityTab(name) {
        if (!moduleTabs.some(tab => tab.dataset.electricityTab === name)) return;
        moduleTabs.forEach(tab => {
            const active = tab.dataset.electricityTab === name;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        document.querySelectorAll('[data-electricity-panel]').forEach(panel => {
            panel.hidden = panel.dataset.electricityPanel !== name;
        });
        requestAnimationFrame(() => window.Highcharts?.charts.filter(Boolean).forEach(chart => {
            if (chart.renderTo.closest(`[data-electricity-panel="${name}"]`)) chart.reflow();
        }));
    }
    moduleTabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateElectricityTab(tab.dataset.electricityTab));
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? index + 1 : event.key === 'ArrowLeft' ? index - 1 : event.key === 'Home' ? 0 : event.key === 'End' ? moduleTabs.length - 1 : null;
            if (next === null) return;
            event.preventDefault();
            const target = moduleTabs[(next + moduleTabs.length) % moduleTabs.length];
            activateElectricityTab(target.dataset.electricityTab);
            target.focus();
        });
    });

    window.addEventListener('simaster:data-changed', event => { event.detail.handled = true; loadDashboard(); });
    loadDashboard({ scope: 'all', bulan: 'all' });
});
</script>
@endpush
