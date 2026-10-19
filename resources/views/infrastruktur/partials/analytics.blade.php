<section class="infra-analytics mb-4"
         data-scope="{{ $scope }}"
         data-ownership-scope="{{ $scope === 'sewa' ? (request('ownership_scope') ?: (request('filter_field') === 'ownership' ? request('filter_value') : '')) : '' }}"
         @if ($scope === 'all') id="infrastructure-data" @endif>
    @php($isOwnershipSubmodule = $scope === 'sewa' && in_array(strtolower(trim((string) (request('ownership_scope') ?: (request('filter_field') === 'ownership' ? request('filter_value') : '')))), ['telkomsel', 'tp'], true))
    @if ($scope !== 'all')
        <h2 class="h5 mb-3">Analitik {{ $scope === 'sewa' ? 'Sewa Lahan' : 'Combat' }}</h2>
    @endif
    @if ($scope !== 'all')
    <div class="row g-3 mb-3">
        @foreach ([
            ['id' => 'total', 'label' => 'Total Site', 'class' => 'primary', 'icon' => 'tower'],
            ['id' => 'active', 'label' => 'Status On Air', 'class' => 'success', 'icon' => 'check'],
            ['id' => 'off_air', 'label' => 'Status Off Air', 'class' => 'warning', 'icon' => 'offline'],
            ['id' => 'without_pks', 'label' => 'Site tanpa Nomor PKS', 'class' => 'danger', 'icon' => 'alert'],
        ] as $card)
            <div class="col-xl-3 col-md-6 col-12">
                <div class="card h-100 border-{{ $card['class'] }} infra-card-link infra-summary-card"
                     data-filter-field="{{ $card['id'] === 'total' ? 'all_records' : 'summary_status' }}"
                     data-filter-value="{{ in_array($card['id'], ['active', 'contract', 'without_pks', 'off_air'], true) ? $card['id'] : '' }}"
                     role="link" tabindex="0">
                    <div class="card-body py-2 infra-summary-card-body">
                        <div>
                            <div class="infra-summary-label text-body-secondary">{{ $card['label'] }}</div>
                            <div class="fs-4 fw-bold infra-card-{{ $card['id'] }}">—</div>
                        </div>
                        <span class="infra-summary-icon" aria-hidden="true">
                            @switch($card['icon'])
                                @case('tower')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 7 21M12 3l5 18M7 21h10M8.1 17h7.8M9.3 12h5.4"/><path d="M5.2 5.8a9.5 9.5 0 0 1 0 12.4M18.8 5.8a9.5 9.5 0 0 0 0 12.4"/></svg>
                                    @break
                                @case('check')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="m8.5 12.1 2.2 2.2 4.8-5"/></svg>
                                    @break
                                @case('contract')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h8l4 4v14H6zM14 3v5h5M9 12h6M9 16h3"/><circle cx="16.5" cy="16.5" r="3.5"/><path d="M16.5 14.8v2l1.2.8"/></svg>
                                    @break
                                @case('alert')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h8l4 4v14H6zM14 3v5h5"/><path d="M12 12v4M12 19h.01"/></svg>
                                    @break
                                @case('offline')
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 7 21M12 3l5 18M7 21h10M8.1 17h7.8M9.3 12h5.4"/><path d="m4 4 16 16"/></svg>
                            @endswitch
                        </span>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
    @endif
    @php($charts = [
        ['id' => 'status', 'title' => 'Komposisi Status Site', 'type' => 'pie', 'key' => 'status'],
        ['id' => 'owner', 'title' => 'Site Owner', 'type' => 'pie', 'key' => 'owners'],
        ['id' => 'renewal', 'title' => $scope === 'combat' ? 'Distribusi Tahun Justi Combat' : 'Distribusi Tahun Renewal Sewa Lahan', 'type' => 'column', 'key' => 'renewal_years'],
        ['id' => 'pks-status', 'title' => 'PKS Status', 'type' => 'pie', 'key' => 'pks_status'],
        ['id' => 'nop', 'title' => 'NOP Site', 'type' => 'pie', 'key' => 'nop'],
        ['id' => 'vendor', 'title' => 'Site Owner', 'type' => 'column', 'key' => 'vendor'],
        ['id' => 'health', 'title' => 'Contract Health / Aging Masa Sewa', 'type' => 'pie', 'key' => 'health'],
        ['id' => 'pipeline', 'title' => 'Renewal Pipeline', 'type' => 'bar', 'key' => 'pipeline', 'height' => 320, 'wide_on_overview' => true],
        ['id' => 'aging', 'title' => 'Rata-rata Process Aging (hari)', 'type' => 'column', 'key' => 'aging', 'height' => 360],
        ['id' => 'geography', 'title' => 'Konsentrasi Area / Kota', 'type' => 'bar', 'key' => 'geography', 'height' => 360],
    ])
    @if ($scope === 'all')
        @php($charts = array_values(array_filter($charts, fn ($chart) => in_array($chart['key'], ['pks_status', 'nop', 'vendor', 'health', 'pipeline', 'aging', 'geography'], true))))
    @endif
    @if ($isOwnershipSubmodule)
        {{-- Portfolio TP/Telkomsel sudah memiliki cakupan owner tunggal. --}}
        @php($charts = array_values(array_filter($charts, fn ($chart) => !in_array($chart['key'], ['owners', 'vendor'], true))))
    @endif
    @php($chartTabs = $scope === 'all' ? [
        'risk' => ['pks_status', 'health'],
        'renewal' => ['pipeline'],
        'distribution' => ['nop', 'vendor'],
        'process' => ['aging', 'geography'],
    ] : [
        'performance' => ['status', 'owners'],
        'risk' => ['pks_status', 'health'],
        'renewal' => ['renewal_years', 'pipeline'],
        'distribution' => ['nop', 'vendor'],
        'process' => ['aging', 'geography'],
    ])
    @if ($scope !== 'all')
        <div class="alert alert-info infra-empty-state d-none" role="status">Belum ada transaksi {{ $scope === 'sewa' ? 'Sewa Lahan' : 'Combat' }} untuk cakupan ini pada dataset aktif.</div>
        <div class="enterprise-tabs-nav infra-module-tabs mb-3" role="tablist" aria-label="Bagian analitik {{ $scope === 'sewa' ? 'Sewa Lahan' : 'Combat' }}">
            @foreach (['performance' => 'Site Performance', 'risk' => 'PKS & Masa Sewa', 'renewal' => 'Perpanjangan', 'distribution' => 'Distribusi Site', 'process' => 'Proses & Wilayah'] as $tabId => $tabLabel)
                <button type="button" class="enterprise-tab-btn {{ $loop->first ? 'active' : '' }}" id="infra-{{ $scope }}-tab-{{ $tabId }}" data-infra-section="{{ $tabId }}" role="tab" aria-controls="infra-{{ $scope }}-panel-{{ $tabId }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? '0' : '-1' }}">{{ $tabLabel }}</button>
            @endforeach
        </div>
    @endif
    @foreach ($chartTabs as $chartTab => $chartKeys)
    @if ($scope === 'all')
        <div id="infra-panel-{{ $chartTab }}" class="infra-tab-panel d-none" data-infra-panel="{{ $chartTab }}" role="tabpanel" aria-labelledby="infra-tab-{{ $chartTab }}" hidden>
    @else
        <div id="infra-{{ $scope }}-panel-{{ $chartTab }}" class="infra-module-panel {{ $loop->first ? '' : 'd-none' }}" data-infra-section-panel="{{ $chartTab }}" role="tabpanel" aria-labelledby="infra-{{ $scope }}-tab-{{ $chartTab }}" @if (! $loop->first) hidden @endif>
    @endif
    @if ($chartTab === 'risk')
    <div class="infra-alert-panel mb-3" role="status" aria-live="polite">
        <div class="small fw-semibold text-uppercase text-body-secondary mb-2">Peringatan {{ $scope === 'sewa' ? 'Sewa Lahan' : ($scope === 'combat' ? 'Combat' : 'Infrastruktur') }}</div>
        <div class="row g-2 infra-alert-grid">
            <div class="col-md-3 col-6"><button type="button" class="alert alert-danger py-2 mb-0 w-100 text-start infra-alert-toggle" data-alert-key="expired" aria-expanded="false"><div class="small">Masa Sewa Berakhir</div><strong class="infra-alert-expired">0</strong> site <span class="float-end">⌄</span></button></div>
            <div class="col-md-3 col-6"><button type="button" class="alert alert-warning py-2 mb-0 w-100 text-start infra-alert-toggle" data-alert-key="within_90" aria-expanded="false"><div class="small">Berakhir ≤90 Hari</div><strong class="infra-alert-90">0</strong> site <span class="float-end">⌄</span></button></div>
            <div class="col-md-3 col-6"><button type="button" class="alert alert-warning py-2 mb-0 w-100 text-start infra-alert-toggle" data-alert-key="within_180" aria-expanded="false"><div class="small">Berakhir ≤180 Hari</div><strong class="infra-alert-180">0</strong> site <span class="float-end">⌄</span></button></div>
            <div class="col-md-3 col-6"><button type="button" class="alert alert-secondary py-2 mb-0 w-100 text-start infra-alert-toggle" data-alert-key="unknown" aria-expanded="false"><div class="small">Tanpa Tanggal Akhir</div><strong class="infra-alert-unknown">0</strong> site <span class="float-end">⌄</span></button></div>
        </div>
        <div class="infra-alert-details card mt-2 d-none" aria-live="polite">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                    <strong class="infra-alert-details-title">Detail notifikasi</strong>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-link text-decoration-none infra-alert-close">Tutup</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Site ID</th><th>Nama Site</th><th>Sumber</th><th>Tanggal Akhir</th><th>Sisa Hari</th></tr></thead>
                        <tbody class="infra-alert-details-body"></tbody>
                    </table>
                </div>
                <div class="small text-body-secondary mt-2 infra-alert-details-empty d-none">Tidak ada site pada kategori ini.</div>
            </div>
        </div>
    </div>
    @endif
    @if ($scope === 'all' && $chartTab === 'renewal')
        <div class="row g-3 mb-3">
            <div class="col-12 d-flex"><div class="card h-100 w-100 infra-overview-chart-card"><div class="card-header fw-semibold">Distribusi Tahun Renewal Sewa Lahan</div><div class="card-body"><div id="infra-renewal-tab-chart" class="infra-overview-chart"></div></div></div></div>
        </div>
    @endif
    <div class="row g-3 infra-detail-chart-grid">
        @foreach ($charts as $chart)
            @continue(!in_array($chart['key'], $chartKeys, true))
            <div class="{{ !empty($chart['wide_on_overview']) && $scope === 'all' ? 'col-12' : 'col-xl-6' }} d-flex">
                <div class="card h-100 w-100 infra-detail-chart-card">
                    <div class="card-header fw-semibold">{{ $chart['title'] }}</div>
                    <div class="card-body"><div id="infra-{{ $scope }}-{{ $chart['id'] }}" class="infra-detail-chart" style="height:{{ $chart['height'] ?? 320 }}px"></div></div>
                </div>
            </div>
        @endforeach
    </div>
    @if ($chartTab === 'risk')
    <button type="button" class="card mt-3 w-100 text-start infra-priority-trigger" aria-label="Tampilkan site Prioritas Tindakan">
        <span class="card-body d-flex align-items-center justify-content-between gap-3 py-3">
            <span><strong>Prioritas Tindakan</strong></span>
            <span class="badge text-bg-warning rounded-pill fs-6 infra-priority-count">—</span>
        </span>
    </button>
    @endif
        </div>
    @endforeach
</section>

@once
<div class="modal fade" id="infraDrilldownModal" tabindex="-1" aria-labelledby="infraDrilldownModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable infra-drilldown-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="infraDrilldownModalLabel">Detail Infrastruktur</h5>
                    <div class="small text-body-secondary" id="infra-drilldown-summary">Memuat data...</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-0">
                <div class="alert alert-danger rounded-0 mb-0 d-none" id="infra-drilldown-error"></div>
                <div id="infra-drilldown-list">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0" id="infra-drilldown-table">
                        <thead class="table-light">
                            <tr>
                                <th>Site ID</th><th>Nama Site</th><th>Sumber</th><th>Owner</th><th>Status Site</th>
                                <th>Status Dokumen</th><th>Status Perpanjangan</th><th>Masa Sewa</th>
                                <th>Tanggal Akhir</th><th>Sisa Hari</th><th>Aging Proses</th><th></th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="12" class="text-center text-body-secondary py-4">Memuat data...</td></tr></tbody>
                    </table>
                </div>
                </div>
                <div id="infra-drilldown-row-detail" class="d-none p-3"></div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-outline-primary btn-sm d-none" id="infra-drilldown-back">Kembali ke tabel</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@push('styles')
<style>
    .infra-module-tabs { display: flex; flex-wrap: wrap; max-width: 100%; }
    .infra-module-tabs .enterprise-tab-btn { white-space: nowrap; }
    @media (max-width: 575px) {
        .infra-module-tabs { width: 100%; }
        .infra-module-tabs .enterprise-tab-btn { flex: 1 1 auto; padding: .5rem .65rem; }
    }
    .infra-alert-toggle { cursor: pointer; border: 1px solid transparent; transition: transform .15s ease, box-shadow .15s ease; }
    .infra-alert-toggle:hover, .infra-alert-toggle[aria-expanded="true"] { transform: translateY(-1px); box-shadow: 0 .2rem .5rem rgba(0,0,0,.08); }
    .infra-alert-details table { font-size: .82rem; }
    .infra-drilldown-dialog { width: 75vw; max-width: 75vw; }
    #infra-drilldown-table { font-size: .82rem; }
    #infra-drilldown-table th { white-space: nowrap; }
    #infra-drilldown-table tbody tr,
    .infra-alert-details-body tr { cursor: pointer; }
    #infra-drilldown-table tbody tr:hover,
    .infra-alert-details-body tr:hover { background: rgba(13,110,253,.08); }
    .infra-priority-trigger { border: 1px solid var(--bs-warning-border-subtle); background: var(--bs-body-bg); }
    .infra-priority-trigger:hover, .infra-priority-trigger:focus-visible { background: var(--bs-warning-bg-subtle); }
    #infra-drilldown-table details summary { cursor: pointer; white-space: nowrap; }
    .infra-drilldown-extra { min-width: 280px; max-width: 460px; white-space: normal; }
    @media (max-width: 992px) {
        .infra-drilldown-dialog { width: 95vw; max-width: 95vw; }
    }
</style>
@endpush
@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
window.infrastructureChartThemeOptions = function (requestedTheme) {
    const theme = requestedTheme || document.documentElement.getAttribute('data-bs-theme') || 'light';
    const dark = theme === 'dark';
    const background = dark ? '#111827' : '#ffffff';
    const text = dark ? '#f1f5f9' : '#0f172a';
    const muted = dark ? '#cbd5e1' : '#64748b';
    const grid = dark ? '#334155' : '#e2e8f0';

    return {
        colors: window.SimasterChartPalette.series,
        credits: { enabled: false },
        exporting: { enabled: false },
        chart: {
            style: { fontSize: '14px' },
            backgroundColor: background,
            plotBackgroundColor: background,
            style: { color: text }
        },
        title: { text: null, style: { color: text } },
        subtitle: { style: { color: muted } },
        legend: {
            layout: 'horizontal',
            align: 'center',
            verticalAlign: 'bottom',
            itemDistance: 12,
            maxHeight: 56,
            itemStyle: { color: text, fontSize: '0.85rem', fontWeight: '500', textOverflow: 'ellipsis' },
            itemHoverStyle: { color: dark ? '#ffffff' : '#020617' },
            itemHiddenStyle: { color: dark ? '#64748b' : '#94a3b8' }
        },
        xAxis: {
            lineColor: grid,
            tickColor: grid,
            labels: { style: { color: muted } },
            title: { style: { color: text } }
        },
        yAxis: {
            gridLineColor: grid,
            lineColor: grid,
            tickColor: grid,
            labels: { style: { color: muted } },
            title: { style: { color: text } }
        },
        tooltip: {
            backgroundColor: dark ? '#1f2937' : '#ffffff',
            borderColor: grid,
            style: { color: text },
            formatter: function () {
                const point = this.point || this;
                const tooltipOptions = point.series.chart.options.tooltip || {};
                const rawValue = Number(point.y) || 0;
                const formattedValue = `${tooltipOptions.valuePrefix || ''}${window.infrastructureShortChartValue(rawValue)}${tooltipOptions.valueSuffix || ''}`;
                const percentage = window.SimasterChartMetrics.highcharts(point);
                const label = point.name || this.key || this.x || point.series.name;

                return `<b>${label}</b><br>${point.series.name}: <b>${formattedValue}</b><br>` +
                    `Persentase total: <b>${window.SimasterChartMetrics.format(percentage)}</b>`;
            }
        },
        plotOptions: {
            // Semua outline bawaan dimatikan khusus grafik Infrastruktur.
            // Ini menghilangkan garis hitam pada irisan pie maupun batang.
            series: { borderWidth: 0, dataLabels: { style: { color: text, textOutline: 'none' } } },
            column: { borderWidth: 0 },
            bar: { borderWidth: 0 },
            pie: {
                borderWidth: 0,
                showInLegend: true,
                dataLabels: {
                    enabled: false,
                    color: text,
                    connectorColor: muted,
                    style: { color: text, textOutline: 'none' }
                }
            }
        }
    };
};

window.infrastructureShortChartValue = function (value) {
    const number = Number(value);
    if (!Number.isFinite(number)) return '—';

    return new Intl.NumberFormat('id-ID', {
        notation: Math.abs(number) >= 1000 ? 'compact' : 'standard',
        maximumFractionDigits: Number.isInteger(number) ? 0 : 1,
    }).format(number);
};

// Keep the legend for category names and place only readable counts on slices.
// Tiny slices retain their count in the legend instead of crowding the chart.
window.infrastructurePieLabelOptions = function (pointCount) {
    const useSideLegend = Number(pointCount) <= 6;

    return {
        legend: {
            layout: useSideLegend ? 'vertical' : 'horizontal',
            align: useSideLegend ? 'right' : 'center',
            verticalAlign: useSideLegend ? 'middle' : 'bottom',
            maxHeight: useSideLegend ? 230 : 62,
            itemDistance: useSideLegend ? 0 : 12,
            itemMarginBottom: useSideLegend ? 6 : 0,
            symbolWidth: useSideLegend ? 10 : undefined,
            labelFormatter: function () {
                const points = this.series?.data || [];
                const total = points.reduce((sum, point) => sum + (Number(point.y) || 0), 0);
                const percentage = total > 0 ? Math.round(((Number(this.y) || 0) / total) * 100) : 0;

                return `${this.name}: ${window.infrastructureShortChartValue(this.y)} (${percentage}%)`;
            }
        },
        plotOptions: {
            pie: {
                showInLegend: true,
                size: useSideLegend ? '76%' : '68%',
                dataLabels: {
                    enabled: true,
                    distance: -24,
                    allowOverlap: false,
                    padding: 2,
                    borderRadius: 3,
                    backgroundColor: 'rgba(15, 23, 42, 0.78)',
                    color: '#ffffff',
                    formatter: function () {
                        return this.y > 0 && this.percentage >= 8
                            ? new Intl.NumberFormat('id-ID').format(this.y)
                            : null;
                    },
                    style: { color: '#ffffff', fontSize: '0.85rem', fontWeight: '700', textOutline: 'none' }
                }
            }
        },
        responsive: [{
            condition: { maxWidth: 575 },
            chartOptions: {
                legend: {
                    layout: 'horizontal',
                    align: 'center',
                    verticalAlign: 'bottom',
                    maxHeight: 62,
                    itemDistance: 10,
                    itemMarginBottom: 0,
                },
                plotOptions: { pie: { size: '70%' } }
            }
        }]
    };
};

// Counts on bars/columns stay small and Highcharts hides a label when there
// is not enough room, so labels inform without turning dense charts noisy.
window.infrastructureValueLabelOptions = function (type, pointCount) {
    const isBar = type === 'bar';

    return {
        plotOptions: {
            series: {
                dataLabels: {
                    enabled: Number(pointCount) <= 12,
                    allowOverlap: false,
                    crop: true,
                    overflow: 'justify',
                    inside: false,
                    align: isBar ? 'left' : 'center',
                    x: isBar ? 4 : 0,
                    y: isBar ? 0 : -3,
                    padding: 1,
                    formatter: function () {
                        return this.y === null || this.y === undefined
                            ? null
                            : window.infrastructureShortChartValue(this.y);
                    },
                    style: { fontSize: '0.8rem', fontWeight: '600', textOutline: 'none' }
                }
            }
        }
    };
};

// Count charts show exact site totals at each bar end. Financial and aging
// charts continue to use their own value labels and units.
window.infrastructureSiteCountLabelOptions = function (type) {
    const isBar = type === 'bar';

    return {
        yAxis: { maxPadding: 0.14 },
        plotOptions: {
            series: {
                dataLabels: {
                    enabled: true,
                    allowOverlap: false,
                    crop: false,
                    overflow: 'allow',
                    inside: false,
                    align: isBar ? 'left' : 'center',
                    x: isBar ? 5 : 0,
                    y: isBar ? 0 : -4,
                    padding: 1,
                    formatter: function () {
                        return this.y === null || this.y === undefined
                            ? null
                            : new Intl.NumberFormat('id-ID').format(this.y);
                    },
                    style: { fontSize: '0.85rem', fontWeight: '700', textOutline: 'none' }
                }
            }
        }
    };
};

window.addEventListener('simaster:theme-changed', function (event) {
    if (!window.Highcharts) return;
    const options = window.infrastructureChartThemeOptions(event.detail?.theme);
    Highcharts.charts.filter(Boolean).forEach(function (chart) {
        if (chart.options.chart?.className?.includes('infra-analytics-chart')) {
            const type = chart.options.chart.type;
            const pointCount = type === 'pie'
                ? (chart.series[0]?.data?.length || 0)
                : chart.series.reduce((total, series) => total + series.data.length, 0);
            const labels = type === 'pie'
                ? window.infrastructurePieLabelOptions(pointCount)
                : chart.options.chart.className.includes('infra-site-count-chart')
                    ? window.infrastructureSiteCountLabelOptions(type)
                    : window.infrastructureValueLabelOptions(type, pointCount);
            chart.update(Highcharts.merge(options, labels), true, false);
        }
    });
});

const infrastructureDrilldownEscape = function (value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'object') {
        try { value = JSON.stringify(value); } catch (e) { value = String(value); }
    }
    return $('<div>').text(String(value)).html();
};

/**
 * Render diagram batang performa untuk satu site. Filter berada di dalam
 * pop-up detail: tahun memilih sumber periode dan bulan dapat dipersempit
 * menjadi tiga batang Revenue/Cost/PnL.
 */
window.renderInfrastructureSitePerformance = function (container, performance) {
    const root = typeof container === 'string' ? document.querySelector(container) : container;
    const periods = Array.isArray(performance) ? performance : [];
    if (!root) return;

    if (!periods.length) {
        root.innerHTML = '<div class="border rounded p-3 text-body-secondary small">Data performance bulanan belum tersedia untuk site ini.</div>';
        return;
    }

    const years = [...new Set(periods.map(period => Number(period.year)).filter(Number.isFinite))]
        .sort((left, right) => right - left);
    const chartId = `infra-site-performance-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

    root.innerHTML = `
        <div class="card border-primary-subtle">
            <div class="card-header bg-transparent d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong>Performance Site</strong>
                <div class="d-flex gap-2">
                    <label class="visually-hidden" for="${chartId}-year">Tahun</label>
                    <select class="form-select form-select-sm" id="${chartId}-year" data-infra-performance-year></select>
                    <label class="visually-hidden" for="${chartId}-month">Bulan</label>
                    <select class="form-select form-select-sm" id="${chartId}-month" data-infra-performance-month>
                        <option value="all">Semua bulan</option>
                    </select>
                </div>
            </div>
            <div class="card-body"><div id="${chartId}" style="height:280px"></div></div>
        </div>`;

    const yearSelect = root.querySelector('[data-infra-performance-year]');
    const monthSelect = root.querySelector('[data-infra-performance-month]');
    yearSelect.innerHTML = years.map(year => `<option value="${year}">${year}</option>`).join('');
    const syncMonthOptions = function () {
        const selectedYear = Number(yearSelect.value);
        const monthOptions = periods
            .filter(period => Number(period.year) === selectedYear)
            .map(period => `<option value="${infrastructureDrilldownEscape(`${period.year}:${period.month}`)}">${infrastructureDrilldownEscape(period.label)}</option>`)
            .join('');
        monthSelect.innerHTML = `<option value="all">Semua bulan</option>${monthOptions}`;
    };

    const draw = function () {
        const selectedYear = Number(yearSelect.value);
        const selectedMonth = monthSelect.value;
        const selected = periods
            .filter(period => Number(period.year) === selectedYear)
            .filter(period => selectedMonth === 'all' || `${period.year}:${period.month}` === selectedMonth);
        const options = selectedMonth === 'all'
            ? {
                xAxis: { categories: selected.map(period => period.label) },
                series: ['revenue', 'cost', 'pnl'].map(metric => ({
                    name: metric === 'pnl' ? 'PnL' : metric[0].toUpperCase() + metric.slice(1),
                    data: selected.map(period => Number(period[metric] || 0)),
                })),
            }
            : {
                xAxis: { categories: ['Revenue', 'Cost', 'PnL'] },
                series: [{
                    name: selected[0]?.label || 'Performance',
                    data: [
                        Number(selected[0]?.revenue || 0),
                        Number(selected[0]?.cost || 0),
                        Number(selected[0]?.pnl || 0),
                    ],
                }],
            };

        // A single selected month has three bars and can safely expose its
        // values.  The all-month view deliberately suppresses labels once
        // they would crowd the grouped financial bars.
        const pointCount = selected.length * options.series.length;
        Highcharts.chart(chartId, Highcharts.merge(window.infrastructureChartThemeOptions(), {
            chart: { type: 'column', className: 'infra-analytics-chart' },
            xAxis: options.xAxis,
            yAxis: { title: { text: 'Nilai (Rp)' } },
        tooltip: {
            valuePrefix: 'Rp ',
            valueDecimals: 0,
            formatter: function () {
                return `${infrastructureDrilldownEscape(this.key)}<br>${infrastructureDrilldownEscape(this.series.name)}: <b>Rp ${new Intl.NumberFormat('id-ID').format(this.y)}</b>`;
            }
        },
            plotOptions: {
                column: {
                    borderRadius: 3,
                    borderWidth: 0,
                    colorByPoint: selectedMonth !== 'all'
                }
            },
            series: options.series,
        }, window.infrastructureValueLabelOptions('column', pointCount)));
    };

    yearSelect.addEventListener('change', function () {
        syncMonthOptions();
        draw();
    });
    monthSelect.addEventListener('change', draw);
    syncMonthOptions();
    draw();
};

window.__infraDrilldownState = { rows: [], scope: 'all', title: '' };
window.showInfrastructureRowDetail = function (row, scope, title) {
    const detail = $('#infra-drilldown-row-detail');
    const list = $('#infra-drilldown-list');
    const back = $('#infra-drilldown-back');
    if (!detail.length) return;

    const source = row.source || (scope === 'combat' ? 'Combat' : (scope === 'sewa' ? 'Sewa Lahan' : 'Infrastruktur'));
    const sourceBase = source === 'Combat'
        ? @json(route('infrastruktur.combat.index'))
        : @json(route('infrastruktur.sewa-lahan.index'));
    const sourceUrl = `${sourceBase}?filter_field=site_code&filter_value=${encodeURIComponent(row.site_code || '')}&unique_sites=1#infrastructure-data`;
    const entries = [
        ['Site ID', row.site_code], ['Nama Site', row.site_name], ['Sumber', source],
        ['Owner', row.owner], ['Tahun', row.tahun], ['NOP', row.nop], ['Vendor / TP', row.vendor],
        ['Status Site', row.status_site],
        ['Status Dokumen', row.status_dokumen], ['Status Perpanjangan', row.status_perpanjangan],
        ['Status Masa Sewa', row.status_masa_sewa], ['Tanggal Akhir', row.end_date],
        ['Sisa Hari', row.days_remaining === null || row.days_remaining === undefined ? '—' : row.days_remaining],
        ['Mulai Tahap Proses', row.process_started_at], ['Aging Proses (hari)', row.process_aging_days],
        ['No PKS Baru', row.no_pks_baru], ['Total Harga Baru', row.total_harga_baru]
    ].concat(Object.entries(row.source_details || {}).filter(([key]) => !key.startsWith('_')).map(([key, value]) => [key.replaceAll('_', ' '), value]));
    const cells = entries
        .filter(([, value]) => value !== null && value !== undefined && value !== '')
        .map(([key, value]) => `<tr><th class="w-25">${infrastructureDrilldownEscape(key)}</th><td>${infrastructureDrilldownEscape(value)}</td></tr>`)
        .join('');
    detail.html(`<div class="d-flex justify-content-between align-items-center mb-3"><h6 class="mb-0">Informasi lengkap ${infrastructureDrilldownEscape(row.site_code || '')}</h6><a class="btn btn-sm btn-outline-secondary" href="${sourceUrl}">Buka sumber data</a></div><div class="table-responsive"><table class="table table-bordered table-sm mb-0">${cells}</table></div><div class="mt-3" id="infra-detail-site-performance"></div>`);
    window.renderInfrastructureSitePerformance(document.getElementById('infra-detail-site-performance'), row.performance);
    list.addClass('d-none');
    detail.removeClass('d-none');
    back.removeClass('d-none');
    $('#infraDrilldownModalLabel').text(`Detail ${source} — ${row.site_code || title || ''}`);
};

$(document).on('click', '#infra-drilldown-back', function () {
    $('#infra-drilldown-row-detail').addClass('d-none').empty();
    $('#infra-drilldown-list').removeClass('d-none');
    $(this).addClass('d-none');
    const state = window.__infraDrilldownState || {};
    $('#infraDrilldownModalLabel').text(`Detail ${state.scope === 'combat' ? 'Combat' : (state.scope === 'sewa' ? 'Sewa Lahan' : 'Infrastruktur')} — ${state.title || 'Semua Site'}`);
});

window.openInfrastructureDrilldown = function (scope, filterField, filterValue, title, ownershipScope = '') {
    // Halaman Sewa Lahan dan Combat sudah memiliki tabel lengkap di bawah
    // analitik. Semua pilihan kartu/diagram harus memakai tabel itu, bukan
    // membuat daftar kedua di modal.
    if (scope === 'sewa' || scope === 'combat') {
        window.applyInfrastructureFilter(scope, filterField, filterValue, ownershipScope);
        return;
    }

    const modalElement = document.getElementById('infraDrilldownModal');
    if (!modalElement || typeof bootstrap === 'undefined') {
        window.applyInfrastructureFilter(scope, filterField, filterValue);
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const body = $('#infra-drilldown-table tbody');
    $('#infra-drilldown-list').removeClass('d-none');
    $('#infra-drilldown-row-detail').addClass('d-none').empty();
    $('#infra-drilldown-back').addClass('d-none');
    const summary = $('#infra-drilldown-summary');
    const error = $('#infra-drilldown-error');
    const selectedTitle = title || (filterValue ? `${filterValue}` : 'Semua Site');
    $('#infraDrilldownModalLabel').text(`Detail ${scope === 'combat' ? 'Combat' : (scope === 'sewa' ? 'Sewa Lahan' : 'Infrastruktur')} — ${selectedTitle}`);
    summary.text('Memuat data...');
    error.addClass('d-none').text('');
    body.html('<tr><td colspan="12" class="text-center text-body-secondary py-4"><span class="spinner-border spinner-border-sm me-2"></span>Memuat data...</td></tr>');
    modal.show();

    const params = new URLSearchParams({ scope: scope || 'all' });
    const selectedYear = new URLSearchParams(window.location.search).get('tahun');
    if (selectedYear && selectedYear !== 'all') params.set('tahun', selectedYear);
    // Retain value-less requests for callers that explicitly need raw records.
    if (filterField && (filterField === 'all_records' || (filterValue !== undefined && filterValue !== null && filterValue !== ''))) {
        params.set('filter_field', filterField);
        if (filterValue !== undefined && filterValue !== null && filterValue !== '') {
            params.set('filter_value', filterValue);
        }
    }
    if (ownershipScope === 'Telkomsel' || ownershipScope === 'TP') {
        params.set('ownership_scope', ownershipScope);
    }

    fetch(@json(route('infrastruktur.dashboard.details')) + '?' + params.toString(), {
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
        .then(response => response.ok ? response.json() : response.json().then(payload => Promise.reject(payload)))
        .then(payload => {
            const rows = payload.data || [];
            window.__infraDrilldownState = { rows, scope: scope || 'all', title: selectedTitle };
            const sourceSummary = Object.entries(payload.source_counts || {})
                .map(([source, count]) => `${source}: ${new Intl.NumberFormat('id-ID').format(count)}`)
                .join(' · ');
            const isRowLevel = filterField === 'all_records' || filterField === 'status'
                || (filterField === 'summary_status' && ['active', 'off_air', 'without_pks'].includes(filterValue));
            const countLabel = isRowLevel ? 'baris data' : 'site unik';
            summary.text(`${new Intl.NumberFormat('id-ID').format(payload.count || rows.length)} ${countLabel}${sourceSummary ? ` (${sourceSummary})` : ''}`);

            if (!rows.length) {
                body.html('<tr><td colspan="12" class="text-center text-body-secondary py-4">Tidak ada data untuk pilihan ini.</td></tr>');
                return;
            }

            const extraDetails = function (row) {
                const entries = [
                    ['Tahun', row.tahun],
                    ['NOP', row.nop],
                    ['Vendor / TP', row.vendor],
                    ['No PKS Baru', row.no_pks_baru],
                    ['Total Harga Baru', row.total_harga_baru],
                    ['Mulai Tahap Proses', row.process_started_at],
                    ['Aging Proses (hari)', row.process_aging_days]
                ].concat(Object.entries(row.source_details || {}))
                    .filter(([key, value]) => !key.startsWith('_') && value !== null && value !== '' && !/^(rev|cost|pnl|revenue|margin|profit|tracy|payload|jan|feb|mar|apr|mei|may|jun|jul|aug|agu|sep|okt|oct|nov|des|dec)|_202[0-9]|-(25|26)/i.test(key));
                if (!entries.length) return '';
                const cells = entries.map(([key, value]) => `<div><span class="text-body-secondary">${infrastructureDrilldownEscape(key.replaceAll('_', ' '))}:</span> ${infrastructureDrilldownEscape(value)}</div>`).join('');
                return `<details class="infra-drilldown-extra mt-1"><summary class="text-primary">Detail kolom dataset</summary><div class="border rounded p-2 mt-1 bg-body-tertiary">${cells}</div></details>`;
            };
            body.html(rows.map((row, rowIndex) => {
                const days = row.days_remaining === null || row.days_remaining === undefined
                    ? '—'
                    : (row.days_remaining < 0 ? `${Math.abs(row.days_remaining)} hari lewat` : `${row.days_remaining} hari`);
                return `<tr class="infra-drilldown-row" data-row-index="${rowIndex}">
                    <td class="fw-semibold">${infrastructureDrilldownEscape(row.site_code)}</td>
                    <td>${infrastructureDrilldownEscape(row.site_name)}</td>
                    <td>${infrastructureDrilldownEscape(row.source)}</td>
                    <td>${infrastructureDrilldownEscape(row.owner)}</td>
                    <td>${infrastructureDrilldownEscape(row.status_site)}</td>
                    <td>${infrastructureDrilldownEscape(row.status_dokumen)}</td>
                    <td>${infrastructureDrilldownEscape(row.status_perpanjangan)}</td>
                    <td>${infrastructureDrilldownEscape(row.status_masa_sewa)}</td>
                    <td>${infrastructureDrilldownEscape(row.end_date)}</td>
                    <td>${infrastructureDrilldownEscape(days)}</td>
                    <td>${infrastructureDrilldownEscape(row.process_aging_days === null || row.process_aging_days === undefined ? '—' : `${row.process_aging_days} hari`)}</td>
                    <td>${extraDetails(row)}</td>
                </tr>`;
            }).join(''));
            body.off('click.infraRow').on('click.infraRow', 'tr.infra-drilldown-row', function () {
                const row = window.__infraDrilldownState.rows[Number($(this).data('row-index'))];
                if (row) window.showInfrastructureRowDetail(row, scope || 'all', selectedTitle);
            });
            if (filterField === 'site_code' && rows.length === 1) {
                window.showInfrastructureRowDetail(rows[0], scope || 'all', selectedTitle);
            }
        })
        .catch(payload => {
            const message = payload?.message || 'Detail data belum dapat dimuat.';
            error.removeClass('d-none').text(message);
            summary.text('Gagal memuat data');
            body.html('<tr><td colspan="12" class="text-center text-body-secondary py-4">Tidak ada data yang dapat ditampilkan.</td></tr>');
        });
};

window.scrollToInfrastructureTable = function () {
    const target = document.getElementById('infrastructure-data')
        || document.getElementById('sewa-table-container')
        || document.getElementById('combat-table-container')
        || document.querySelector('#sewa-table, #combat-table')?.closest('.card');

    if (!target) return;

    const header = document.querySelector('.app-header');
    const headerHeight = header ? header.offsetHeight : 70;
    const elementTop = target.getBoundingClientRect().top + window.pageYOffset;
    const offsetPosition = Math.max(0, elementTop - headerHeight - 16);

    window.scrollTo({
        top: offsetPosition,
        behavior: 'smooth'
    });
};

function dashboardTarget(scope, filterField, filterValue, ownershipScope = '') {
    const infrastructureUrl = @json(route('infrastruktur.index'));
    const sewaUrl = @json(route('infrastruktur.sewa-lahan.index'));
    const combatUrl = @json(route('infrastruktur.combat.index'));
    let target = scope === 'combat' ? combatUrl : sewaUrl;
    const selectedYear = typeof window === 'undefined' ? '' : (new URLSearchParams(window.location.search).get('tahun') || '');

    // Total Site opens every source row and stays visible after a reload.
    if (filterField === 'all_records') {
        const params = new URLSearchParams({ filter_field: 'all_records' });
        if (selectedYear && selectedYear !== 'all') params.set('tahun', selectedYear);
        if (scope === 'sewa' && (ownershipScope === 'Telkomsel' || ownershipScope === 'TP')) {
            params.set('ownership_scope', ownershipScope);
        }
        return target + (params.toString() ? '?' + params.toString() : '');
    }

    if (filterField === 'tahun') {
        const params = new URLSearchParams({ unique_sites: '1' });
        if (/^(19|20|21)\d{2}$/.test(String(filterValue))) {
            params.set('tahun', filterValue);
        } else {
            params.set('filter_field', 'tahun');
            params.set('filter_value', filterValue);
        }
        if (scope === 'sewa' && (ownershipScope === 'Telkomsel' || ownershipScope === 'TP')) {
            params.set('ownership_scope', ownershipScope);
        }
        return (scope === 'combat' ? combatUrl : sewaUrl) + '?' + params;
    }
    if (scope === 'all' && filterField === 'source') {
        target = filterValue === 'Combat' ? combatUrl : sewaUrl;
        filterField = '';
        filterValue = '';
    } else if (filterField === 'source') {
        filterField = '';
        filterValue = '';
    } else if (scope === 'all' && filterField === 'ownership') {
        target = sewaUrl;
    }

    const params = new URLSearchParams();
    if (selectedYear && selectedYear !== 'all') params.set('tahun', selectedYear);
    if (filterField && filterValue) {
        params.set('filter_field', filterField);
        params.set('filter_value', filterValue);
    }
    if (scope === 'sewa' && filterField !== 'ownership' && (ownershipScope === 'Telkomsel' || ownershipScope === 'TP')) {
        params.set('ownership_scope', ownershipScope);
    }
    // Status cards and charts count all source rows; other charts count
    // canonical Site IDs and retain their existing table filter.
    const isRowLevel = filterField === 'status'
        || (filterField === 'summary_status' && ['active', 'off_air', 'without_pks'].includes(filterValue));
    if (!isRowLevel) params.set('unique_sites', '1');
    return target + (params.toString() ? '?' + params.toString() : '');
}

window.applyInfrastructureFilter = function (scope, filterField, filterValue, ownershipScope = '') {
    const target = dashboardTarget(scope, filterField, filterValue, ownershipScope);
    const hasTable = document.getElementById('infrastructure-data') !== null;
    const isSameScope = (scope === 'sewa' && window.location.pathname.includes('sewa-lahan')) ||
                        (scope === 'combat' && window.location.pathname.includes('combat'));

    if (hasTable && isSameScope) {
        if (filterField === 'tahun') {
            window.location.href = target + '#infrastructure-data';
            return;
        }
        const url = new URL(window.location.href);
        const targetUrl = new URL(target, window.location.origin);
        url.search = targetUrl.search;
        window.history.pushState({}, '', url);

        $(document).trigger('infrastructure-filter', [{
            filterField: filterField || '',
            filterValue: filterValue || ''
        }]);
        // The module table is a chart drill-down and is revealed by the
        // event above. Scroll only after it is visible in the layout.
        window.scrollToInfrastructureTable();
        return;
    }

    // Jika berpindah dari halaman lain (misal dari dashboard utama)
    window.location.href = target + '#infrastructure-data';
};

$(function () {
    $('.infra-analytics').each(function () {
        const root = $(this), scope = root.data('scope');
        const moduleTabs = Array.from(root[0].querySelectorAll('[data-infra-section]'));
        function activateSection(name) {
            moduleTabs.forEach(tab => {
                const active = tab.dataset.infraSection === name;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
            });
            root[0].querySelectorAll('[data-infra-section-panel]').forEach(panel => {
                const active = panel.dataset.infraSectionPanel === name;
                panel.hidden = !active;
                panel.classList.toggle('d-none', !active);
            });
            requestAnimationFrame(() => {
                window.Highcharts?.charts.filter(Boolean).forEach(chart => {
                    if (root[0].contains(chart.renderTo) && chart.renderTo.closest('[data-infra-section-panel]')?.dataset.infraSectionPanel === name) chart.reflow();
                });
            });
        }
        moduleTabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activateSection(tab.dataset.infraSection));
            tab.addEventListener('keydown', event => {
                const next = event.key === 'ArrowRight' ? index + 1 : event.key === 'ArrowLeft' ? index - 1 : event.key === 'Home' ? 0 : event.key === 'End' ? moduleTabs.length - 1 : null;
                if (next === null) return;
                event.preventDefault();
                const target = moduleTabs[(next + moduleTabs.length) % moduleTabs.length];
                activateSection(target.dataset.infraSection);
                target.focus();
            });
        });
        const ownershipScope = root.data('ownership-scope') || '';
        const dataParams = { scope };
        const selectedYear = new URLSearchParams(window.location.search).get('tahun');
        if (selectedYear && selectedYear !== 'all') dataParams.tahun = selectedYear;
        if (ownershipScope === 'Telkomsel' || ownershipScope === 'TP') {
            dataParams.ownership_scope = ownershipScope;
        }
        $.getJSON(@json(route('infrastruktur.dashboard.data')), dataParams).done(function (data) {
            Object.entries(data.cards).forEach(([key, value]) => {
                const el = root.find('.infra-card-' + key);
                if (el.length) el.text(key === 'risk_value' ? 'Rp ' + new Intl.NumberFormat('id-ID').format(value) : new Intl.NumberFormat('id-ID').format(value));
            });
            if (scope !== 'all') {
                const empty = !data.cards.total;
                root.find('.infra-empty-state').toggleClass('d-none', !empty);
                root.find('.infra-module-tabs').toggleClass('d-none', empty);
                if (empty) {
                    root.find('.infra-module-panel').addClass('d-none').prop('hidden', true);
                    return;
                }
            }
            const alerts = data.alerts || { expired: [], within_90: [], within_180: [], unknown: [] };
            root[0].__infraAlerts = alerts;
            root.find('.infra-alert-expired').text(new Intl.NumberFormat('id-ID').format((alerts.expired || []).length));
            root.find('.infra-alert-90').text(new Intl.NumberFormat('id-ID').format((alerts.within_90 || []).length));
            root.find('.infra-alert-180').text(new Intl.NumberFormat('id-ID').format((alerts.within_180 || []).length));
            root.find('.infra-alert-unknown').text(new Intl.NumberFormat('id-ID').format((alerts.unknown || []).length));
            const base = window.infrastructureChartThemeOptions();
            const charts = [
                ['status', 'status', 'pie'], ['owner', 'owners', 'pie'],
                ['renewal', 'renewal_years', 'column'], ['pks-status', 'pks_status', 'pie'],
                ['nop', 'nop', 'pie'], ['vendor', 'vendor', 'column'],
                ['health', 'health', 'pie'], ['pipeline', 'pipeline', 'bar'],
                ['aging', 'aging', 'column'], ['geography', 'geography', 'bar']
            ];
            charts.forEach(([id, key, type]) => {
                const chartId = `infra-${scope}-${id}`;
                const container = document.getElementById(chartId);

                // Halaman Dashboard Utama hanya menampilkan analitik lanjutan.
                // Jangan membuat chart untuk kontainer yang sengaja tidak
                // dirender; Highcharts akan melempar error #13 dan menghentikan
                // proses render chart berikutnya.
                if (!container) return;

                const source = data[key] || [];
                const isAging = key === 'aging';
                const series = [{ name: isAging ? 'Rata-rata hari' : 'Site', data: source.map(point => ({
                        name: point.name, y: point.y,
                        custom: {
                            filterField: point.filter_field,
                            filterValue: point.filter_value,
                            datedCount: point.dated_count,
                            totalCount: point.count
                        }
                    })) }];
                const labelOptions = type === 'pie'
                    ? window.infrastructurePieLabelOptions(source.length)
                    : isAging
                        ? window.infrastructureValueLabelOptions(type, source.length)
                        : window.infrastructureSiteCountLabelOptions(type);
                Highcharts.chart(container, Highcharts.merge(base, {
                    chart: { type, className: `infra-analytics-chart${isAging ? '' : ' infra-site-count-chart'}` },
                    ...(type === 'pie' ? {} : { colors: window.SimasterChartPalette.distinctColors(source.length) }),
                    xAxis: { type: 'category' },
                    yAxis: { title: { text: isAging ? 'Hari' : 'Site unik' } },
                    plotOptions: {
                        pie: {
                            borderWidth: 0,
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        if (!this.options.custom) return;
                                        window.openInfrastructureDrilldown(scope, this.options.custom.filterField, this.options.custom.filterValue, this.options.custom.filterValue || this.options.custom.filterField, ownershipScope);
                                    }
                                }
                            }
                        },
                        series: {
                            cursor: 'pointer',
                point: {
                    events: {
                                    click: function () {
                                        if (!this.options.custom) return;
                                        window.openInfrastructureDrilldown(scope, this.options.custom.filterField, this.options.custom.filterValue, this.options.custom.filterValue || this.options.custom.filterField, ownershipScope);
                                    }
                                }
                            }
                        },
                        column: { borderWidth: 0, colorByPoint: true },
                        bar: { borderWidth: 0, colorByPoint: true }
                    },
                    ...(isAging ? {
                            tooltip: {
                                formatter: function () {
                                    const dated = this.point.options.custom?.datedCount || 0;
                                    const total = this.point.options.custom?.totalCount || 0;
                                    const value = this.y === null || this.y === undefined ? 'Tanggal proses belum tersedia' : `<b>${this.y} hari</b>`;
                                    return `${infrastructureDrilldownEscape(this.key)}<br>${value}<br><span style="font-size:15px;font-weight:400">${dated} dari ${total} site memiliki tanggal tahap</span>`;
                                }
                            }
                        } : {}),
                    series
                }, labelOptions));
            });

            root.find('.infra-priority-count').text(new Intl.NumberFormat('id-ID').format(data.priority_count || 0));

            // Setelah semua chart selesai digambar di DOM, jika ada filter aktif atau hash, pastikan posisi scroll tepat di tabel
            if (window.location.hash === '#infrastructure-data' || new URLSearchParams(window.location.search).has('filter_field')) {
                setTimeout(function () {
                    window.scrollToInfrastructureTable();
                }, 200);
            }
        });
    });

    $(document).on('click keydown', '.infra-card-link', function (event) {
        if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        const root = $(this).closest('.infra-analytics');
        window.openInfrastructureDrilldown(
            root.data('scope'),
            $(this).data('filter-field'),
            $(this).data('filter-value'),
            $(this).find('.infra-summary-label').first().text(),
            root.data('ownership-scope') || ''
        );
    });

    $(document).on('click', '.infra-priority-trigger', function () {
        const root = $(this).closest('.infra-analytics');
        window.openInfrastructureDrilldown(
            root.data('scope'),
            'priority',
            '1',
            'Prioritas Tindakan',
            root.data('ownership-scope') || ''
        );
    });

    const alertTitles = {
        expired: 'Site dengan masa sewa sudah berakhir',
        within_90: 'Site yang berakhir dalam ≤90 hari',
        within_180: 'Site yang berakhir dalam ≤180 hari',
        unknown: 'Site tanpa tanggal akhir masa sewa'
    };
    const escapeAlertHtml = value => $('<div>').text(value ?? '').html();
    const renderAlertDetails = function (root, key) {
        const panel = root.find('.infra-alert-details');
        const body = panel.find('.infra-alert-details-body');
        const items = root[0].__infraAlerts?.[key] || [];
        root.find('.infra-alert-toggle').attr('aria-expanded', 'false');
        root.find(`.infra-alert-toggle[data-alert-key="${key}"]`).attr('aria-expanded', 'true');
        panel.removeClass('d-none');
        panel.find('.infra-alert-details-title').text(`${alertTitles[key] || 'Detail notifikasi'} (${items.length} site)`);
        panel.find('.infra-alert-details-empty').toggleClass('d-none', items.length > 0);
        body.html(items.map(item => {
            const days = item.days_remaining === null || item.days_remaining === undefined
                ? '—'
                : (item.days_remaining < 0 ? `${Math.abs(item.days_remaining)} hari lewat` : `${item.days_remaining} hari`);
            return `<tr class="infra-alert-row" data-site="${escapeAlertHtml(item.site_code || '')}" data-source="${escapeAlertHtml(item.source || '')}">
                <td class="fw-semibold">${escapeAlertHtml(item.site_code || '—')}</td>
                <td>${escapeAlertHtml(item.site_name || '—')}</td>
                <td>${escapeAlertHtml(item.source || '—')}</td>
                <td>${escapeAlertHtml(item.end_date || '—')}</td>
                <td>${escapeAlertHtml(days)}</td>
            </tr>`;
        }).join(''));
        body.off('click.infraAlertRow').on('click.infraAlertRow', 'tr.infra-alert-row', function () {
            const rowSource = $(this).data('source') === 'Combat' ? 'combat' : 'sewa';
            window.openInfrastructureDrilldown(rowSource, 'site_code', $(this).data('site'), $(this).data('site'), root.data('ownership-scope') || '');
        });
        if (items.length) {
            setTimeout(() => panel[0]?.scrollIntoView({ behavior: 'smooth', block: 'center' }), 0);
        }
    };
    $(document).on('click', '.infra-alert-toggle', function () {
        const root = $(this).closest('.infra-analytics');
        const key = $(this).data('alert-key');
        const scope = root.data('scope');

        if (scope === 'sewa' || scope === 'combat') {
            const tableFilters = {
                expired: ['status_masa_sewa', @json(\App\Support\LeaseStatus::EXPIRED)],
                within_90: ['status_masa_sewa', @json(\App\Support\LeaseStatus::WITHIN_90_DAYS)],
                within_180: ['lease_window', '180'],
                unknown: ['status_masa_sewa', @json(\App\Support\LeaseStatus::NO_END_DATE)]
            };
            const selected = tableFilters[key] || ['', ''];
            window.applyInfrastructureFilter(scope, selected[0], selected[1], root.data('ownership-scope') || '');
            return;
        }

        const panel = root.find('.infra-alert-details');
        if (!panel.hasClass('d-none') && panel.data('alert-key') === key) {
            panel.addClass('d-none').removeData('alert-key');
            root.find('.infra-alert-toggle').attr('aria-expanded', 'false');
            return;
        }
        panel.data('alert-key', key);
        renderAlertDetails(root, key);
    });
    $(document).on('click', '.infra-alert-close', function () {
        const root = $(this).closest('.infra-analytics');
        root.find('.infra-alert-details').addClass('d-none').removeData('alert-key');
        root.find('.infra-alert-toggle').attr('aria-expanded', 'false');
    });
    if (window.location.hash === '#infrastructure-data' || new URLSearchParams(window.location.search).has('filter_field')) {
        setTimeout(function () {
            window.scrollToInfrastructureTable();
        }, 150);
    }
});
</script>
@endpush
@endonce
