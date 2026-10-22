@extends('layouts.app')

@section('title', 'Infrastruktur — SIMAWAR')

@push('styles')
<style>
    .infra-tabs-nav { display: flex; flex-wrap: wrap; max-width: 100%; }
    .infra-tabs-nav .enterprise-tab-btn { white-space: nowrap; }
    .infra-tab-panel .infra-alert-details .table-responsive { max-height: min(34vh, 300px); overflow-y: auto; }
    @media (max-width: 575px) {
        .infra-tabs-nav { width: 100%; }
        .infra-tabs-nav .enterprise-tab-btn { flex: 1 1 auto; padding: .5rem .75rem; }
        .infra-tab-panel .infra-summary-card-body { padding: .75rem; }
        .infra-tab-panel .infra-summary-icon { display: none; }
        .infra-tab-panel .infra-summary-card .fs-3 { font-size: 1.4rem !important; }
    }
</style>
@endpush

@section('content')
<div class="page-hero mb-3">
    <h1 class="h4 mb-0">Infrastruktur Performance</h1>
</div>
@include('infrastruktur.partials.scope-filter', ['scope' => 'all'])

<div class="enterprise-tabs-nav infra-tabs-nav mb-3" id="infra-tabs" role="tablist" aria-label="Bagian Infrastruktur Management">
    <button type="button" class="enterprise-tab-btn active" id="infra-tab-overview" role="tab" aria-selected="true" aria-controls="infra-panel-overview" data-infra-tab="overview">Ringkasan</button>
    <button type="button" class="enterprise-tab-btn" id="infra-tab-risk" role="tab" aria-selected="false" aria-controls="infra-panel-risk" data-infra-tab="risk" tabindex="-1">PKS &amp; Masa Sewa</button>
    <button type="button" class="enterprise-tab-btn" id="infra-tab-renewal" role="tab" aria-selected="false" aria-controls="infra-panel-renewal" data-infra-tab="renewal" tabindex="-1">Perpanjangan</button>
    <button type="button" class="enterprise-tab-btn" id="infra-tab-distribution" role="tab" aria-selected="false" aria-controls="infra-panel-distribution" data-infra-tab="distribution" tabindex="-1">Distribusi Site</button>
    <button type="button" class="enterprise-tab-btn" id="infra-tab-process" role="tab" aria-selected="false" aria-controls="infra-panel-process" data-infra-tab="process" tabindex="-1">Proses &amp; Wilayah</button>
</div>

<div id="infra-panel-overview" class="infra-tab-panel" data-infra-panel="overview" role="tabpanel" aria-labelledby="infra-tab-overview">
<div class="row g-3 mb-4">
    @foreach ([
        ['id' => 'total', 'label' => 'Total Site', 'class' => 'primary', 'icon' => 'tower'],
        ['id' => 'active', 'label' => 'Status On Air', 'class' => 'success', 'icon' => 'check'],
        ['id' => 'off_air', 'label' => 'Status Off Air', 'class' => 'warning', 'icon' => 'offline'],
        ['id' => 'without_pks', 'label' => 'Site tanpa Nomor PKS', 'class' => 'danger', 'icon' => 'alert'],
    ] as $card)
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card h-100 border-{{ $card['class'] }} infra-summary-link infra-summary-card"
                 data-filter-field="{{ $card['id'] === 'total' ? 'all_records' : 'summary_status' }}" data-filter-value="{{ $card['id'] === 'total' ? '' : $card['id'] }}"
                 role="link" tabindex="0" title="Buka data {{ $card['label'] }}">
                <div class="card-body infra-summary-card-body">
                    <div>
                        <div class="infra-summary-label text-body-secondary">{{ $card['label'] }}</div>
                        <div class="fs-3 fw-bold" id="infra-{{ $card['id'] }}">—</div>
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

<div class="row g-3 infra-overview-chart-grid">
    <div class="col-lg-6 d-flex"><div class="card h-100 w-100 infra-overview-chart-card"><div class="card-header fw-semibold">Komposisi Status Site</div><div class="card-body"><div id="infra-status-chart" class="infra-overview-chart"></div></div></div></div>
    <div class="col-lg-6 d-flex"><div class="card h-100 w-100 infra-overview-chart-card"><div class="card-header fw-semibold">Site Owner (TP / Telkomsel)</div><div class="card-body"><div id="infra-owner-chart" class="infra-overview-chart"></div></div></div></div>
    <div class="col-12"><div class="card infra-overview-chart-card"><div class="card-header fw-semibold">Distribusi Tahun Renewal Sewa Lahan</div><div class="card-body"><div id="infra-renewal-chart" class="infra-overview-chart"></div></div></div></div>
</div>
</div>
@include('infrastruktur.partials.analytics', ['scope' => 'all'])
@endsection

@push('scripts')
<script>
$(function () {
    const tabs = Array.from(document.querySelectorAll('#infra-tabs [data-infra-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-infra-panel]'));

    function activateInfrastructureTab(name) {
        if (!tabs.some(tab => tab.dataset.infraTab === name)) return;

        tabs.forEach(tab => {
            const active = tab.dataset.infraTab === name;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
        });
        panels.forEach(panel => {
            const active = panel.dataset.infraPanel === name;
            panel.hidden = !active;
            panel.classList.toggle('d-none', !active);
        });

        requestAnimationFrame(() => {
            if (!window.Highcharts) return;
            Highcharts.charts.filter(Boolean).forEach(chart => {
                if (chart.renderTo.closest(`[data-infra-panel="${name}"]`)) chart.reflow();
            });
        });
    }

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activateInfrastructureTab(tab.dataset.infraTab));
        tab.addEventListener('keydown', event => {
            const next = event.key === 'ArrowRight' ? index + 1
                : event.key === 'ArrowLeft' ? index - 1
                : event.key === 'Home' ? 0
                : event.key === 'End' ? tabs.length - 1 : null;
            if (next === null) return;
            event.preventDefault();
            const target = tabs[(next + tabs.length) % tabs.length];
            activateInfrastructureTab(target.dataset.infraTab);
            target.focus();
        });
    });
    if (window.location.hash === '#infrastructure-data'
        || new URLSearchParams(window.location.search).has('filter_field')) {
        activateInfrastructureTab('risk');
    }

    $(document).on('click keydown', '.infra-summary-link', function (event) {
        if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') return;
        event.preventDefault();
        window.openInfrastructureDrilldown('all', $(this).data('filter-field'), $(this).data('filter-value'), $(this).find('.infra-summary-label').first().text());
    });

    const year = new URLSearchParams(window.location.search).get('tahun') || 'all';
    $.getJSON(@json(route('infrastruktur.dashboard.data')), { tahun: year }, function (data) {
        Object.entries(data.cards).forEach(([key, value]) => {
            const el = $('#infra-' + key);
            if (el.length) el.text(key === 'risk_value' ? 'Rp ' + new Intl.NumberFormat('id-ID').format(value) : new Intl.NumberFormat('id-ID').format(value));
        });
        const base = window.infrastructureChartThemeOptions();
        const themed = options => Highcharts.merge(base, options);
        const drilldown = (series) => series.map(point => ({ ...point, custom: { filterField: point.filter_field, filterValue: point.filter_value } }));
        const drilldownClick = scope => function () {
            const custom = this.options?.custom;
            if (!custom?.filterField) return;
            window.openInfrastructureDrilldown(scope, custom.filterField, custom.filterValue, custom.filterValue || custom.filterField);
        };
        const clickOptions = { cursor: 'pointer', point: { events: { click: drilldownClick('all') } } };
        const sewaRenewalClickOptions = { cursor: 'pointer', point: { events: { click: drilldownClick('sewa') } } };

        Highcharts.chart('infra-status-chart', themed(Highcharts.merge({
            chart: { type: 'pie', className: 'infra-analytics-chart infra-site-count-chart' },
            plotOptions: { pie: { borderWidth: 0 }, series: clickOptions },
            series: [{ name: 'Site', data: drilldown(data.status) }]
        }, window.infrastructurePieLabelOptions(data.status.length))));
        Highcharts.chart('infra-owner-chart', themed(Highcharts.merge({
            chart: { type: 'pie', className: 'infra-analytics-chart infra-site-count-chart' },
            plotOptions: { pie: { innerSize: '55%', borderWidth: 0 }, series: clickOptions },
            series: [{ name: 'Site', data: drilldown(data.owners) }]
        }, window.infrastructurePieLabelOptions(data.owners.length))));
        const renewalChartOptions = () => themed(Highcharts.merge({
            chart: { type: 'column', className: 'infra-analytics-chart infra-site-count-chart' },
            colors: window.SimasterChartPalette.distinctColors(data.renewal_years.length),
            xAxis: { type: 'category' },
            yAxis: { title: { text: 'Site unik' } },
            plotOptions: {
                series: sewaRenewalClickOptions,
                column: { borderWidth: 0, colorByPoint: true }
            },
            series: [{ name: 'Site', data: drilldown(data.renewal_years) }]
        }, window.infrastructureSiteCountLabelOptions('column')));
        Highcharts.chart('infra-renewal-chart', renewalChartOptions());
        Highcharts.chart('infra-renewal-tab-chart', renewalChartOptions());
    }).fail(function (xhr) {
        const message = xhr.responseJSON?.message || 'Data Infrastruktur belum dapat dimuat.';
        $('#infra-tabs').before($('<div>', { class: 'alert alert-danger mb-3', role: 'alert', text: message }));
    });
});
</script>
@endpush
