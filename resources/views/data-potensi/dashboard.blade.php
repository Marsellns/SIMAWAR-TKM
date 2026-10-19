@extends('layouts.app')

@section('title', 'Feasibility Site — SIMAWAR')

@push('styles')
<style>
    .feasibility-dashboard, #feasibility-detail-modal { --feasibility-border: var(--bs-border-color, #e2e8f0); color: var(--bs-body-color, #0f172a); }
    .feasibility-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
    .feasibility-header img { width: 60px; height: 60px; }
    .feasibility-header h1 { font-size: 1.65rem; font-weight: 700; margin: 0 0 .3rem; }
    .feasibility-header p, .feasibility-panel p { color: var(--bs-secondary-color, #64748b); margin: 0; font-size: .9rem; }
    .feasibility-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
    .feasibility-tone-blue { --tone: #0284c7; --tint: #f0f9ff; --wash: #e0f2fe; }
    .feasibility-tone-mint { --tone: #059669; --tint: #f0fdf8; --wash: #d1fae5; }
    .feasibility-tone-amber { --tone: #d97706; --tint: #fffbf5; --wash: #fef3c7; }
    .feasibility-tone-violet { --tone: #7c3aed; --tint: #faf7ff; --wash: #ede9fe; }
    .feasibility-kpi { position: relative; overflow: hidden; isolation: isolate; border-radius: 1.25rem; padding: 1.5rem; min-height: 148px; background: var(--tint); box-shadow: 0 5px 22px rgb(15 23 42 / .035); }
    button.feasibility-kpi { border: 0; text-align: left; color: inherit; font-family: inherit; cursor: pointer; transition: transform .2s, box-shadow .2s; }
    button.feasibility-kpi:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgb(15 23 42 / .1); }
    button.feasibility-kpi:focus-visible, button.feasibility-category-card:focus-visible { outline: 2px solid #3b82f6; outline-offset: 2px; }
    .feasibility-kpi::after { content: ''; position: absolute; right: -1.5rem; bottom: -2.5rem; width: 8rem; height: 8rem; border-radius: 50%; background: var(--wash); z-index: -1; opacity: .65; }
    .feasibility-kpi-label { display: block; color: #5e7491; font-size: .86rem; font-weight: 750; text-transform: uppercase; letter-spacing: .025em; padding-right: 2.5rem; }
    .feasibility-kpi-value { display: block; font-size: 2.15rem; line-height: 1.2; font-weight: 800; margin-top: 1.25rem; color: #0f172a; }
    .feasibility-kpi-icon { position: absolute; right: 1.25rem; top: 1.25rem; display: grid; place-items: center; width: 3rem; height: 3rem; border-radius: 1rem; background: var(--wash); color: var(--tone); }
    .feasibility-kpi-icon svg { width: 1.55rem; height: 1.55rem; }
    .feasibility-panel { height: 100%; border: 1px solid var(--feasibility-border); background: var(--bs-body-bg, #fff); border-radius: 1rem; overflow: hidden; }
    .feasibility-panel-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: .75rem; padding: 1.2rem 1.25rem; border-bottom: 1px solid var(--feasibility-border); }
    .feasibility-panel h2 { font-size: 1.05rem; font-weight: 600; margin: 0 0 .35rem; }
    .feasibility-panel-body { padding: 1rem 1.25rem 1.25rem; }
    .feasibility-chart { min-height: 340px; }
    .feasibility-chart-scroll { max-height: 460px; overflow: auto; overscroll-behavior: contain; }
    .feasibility-mla-chart { position: relative; height: 220px; }
    .feasibility-mla-chart > div:first-child { height: 220px; }
    .feasibility-donut-total { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; flex-direction: column; pointer-events: none; }
    .feasibility-donut-total strong { font-size: 1.8rem; font-weight: 700; }
    .feasibility-donut-total span { color: var(--bs-secondary-color); font-size: .8rem; }
    .feasibility-mla-values { display: grid; gap: .65rem; margin: .5rem 0 0; padding: 0; list-style: none; }
    .feasibility-mla-values li { display: flex; align-items: center; gap: .65rem; font-size: .9rem; }
    .feasibility-mla-values button { display: flex; align-items: center; gap: .65rem; border: 0; padding: .3rem 0; width: 100%; background: transparent; color: inherit; text-align: left; cursor: pointer; }
    .feasibility-mla-values button:hover { color: var(--bs-primary); }
    .feasibility-mla-values .value { margin-left: auto; font-weight: 600; font-variant-numeric: tabular-nums; }
    .feasibility-mla-values .percentage { width: 3.5rem; text-align: right; color: var(--bs-secondary-color); font-size: .8rem; }
    .feasibility-dot { width: .65rem; height: .65rem; border-radius: 50%; flex-shrink: 0; }
    .feasibility-note { font-size: .8rem !important; color: var(--bs-secondary-color); line-height: 1.55; }
    .feasibility-search { display: block; width: 100%; border: 1px solid var(--feasibility-border); border-radius: .7rem; padding: .65rem .85rem; color: inherit; background: var(--bs-body-bg); font-size: .9rem; }
    .feasibility-search:focus-visible { outline: 2px solid #3b82f6; outline-offset: 2px; }
    .feasibility-category-status { font-size: .8rem; color: var(--bs-secondary-color); margin: .6rem 0 .85rem; }
    .feasibility-category-list { max-height: 385px; overflow-y: auto; overscroll-behavior: contain; padding: .1rem .35rem .35rem .1rem; }
    .feasibility-category-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(175px, 100%), 1fr)); gap: .75rem; }
    .feasibility-category-card { background: var(--tint); border: 1px solid var(--wash); border-radius: .9rem; padding: 1rem; min-width: 0; }
    .simaster-report button.feasibility-category-card { text-align: left; color: inherit; width: 100%; cursor: pointer; font-family: inherit; border-radius: .9rem; }
    button.feasibility-category-card:hover, button.feasibility-category-card[aria-pressed="true"] { border-color: var(--tone); box-shadow: 0 0 0 1px var(--tone); }
    .feasibility-category-card[hidden], .feasibility-no-results[hidden] { display: none !important; }
    .feasibility-category-name { display: block; font-size: .85rem; font-weight: 700; overflow-wrap: anywhere; line-height: 1.45; }
    .feasibility-category-count { font-size: 1.5rem; font-weight: 800; margin: .4rem 0 .15rem; font-variant-numeric: tabular-nums; }
    .feasibility-category-count small { font-size: .75rem; color: var(--bs-secondary-color); font-weight: 500; }
    .feasibility-category-meta { font-size: .78rem; color: var(--bs-secondary-color); }
    .feasibility-category-progress { height: 5px; background: var(--wash); border-radius: 5px; margin-top: .7rem; overflow: hidden; }
    .feasibility-category-progress > span { display: block; height: 100%; width: var(--share); background: var(--tone); border-radius: inherit; }
    .feasibility-no-results { padding: 1rem 0; }
    #feasibility-detail-modal .modal-dialog { width: calc(100% - 2rem); max-width: 1440px; }
    #feasibility-detail-modal .modal-header { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; }
    .feasibility-detail-layout { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 1.25rem; }
    .feasibility-detail-layout.without-overview { grid-template-columns: minmax(0, 1fr); }
    .feasibility-detail-sidebar { min-width: 0; }
    .feasibility-detail-sidebar .feasibility-category-list { max-height: 55vh; }
    .feasibility-detail-sidebar .feasibility-category-grid { grid-template-columns: minmax(0, 1fr); }
    .feasibility-detail-sidebar .feasibility-category-count { display: block; font-size: 1.25rem; margin: .2rem 0; }
    .feasibility-detail-sidebar .feasibility-category-progress { display: block; }
    .feasibility-detail-table { min-width: 0; }
    .feasibility-detail-controls { display: flex; gap: .75rem; align-items: center; flex-wrap: wrap; margin-bottom: .75rem; }
    .feasibility-detail-controls input { flex: 1 1 240px; }
    .feasibility-active-filters { display: flex; flex-wrap: wrap; gap: .5rem; font-size: .8rem; margin-bottom: 1rem; }
    .feasibility-active-filters span { border: 1px solid var(--bs-border-color); border-radius: 1rem; padding: .25rem .65rem; background: var(--bs-tertiary-bg); overflow-wrap: anywhere; }
    #feasibility-detail-table tbody tr { cursor: pointer; }
    #feasibility-detail-table tbody tr:hover { background: var(--bs-tertiary-bg); }
    #feasibility-detail-table td, #feasibility-detail-table th { white-space: nowrap; font-size: .86rem; }
    #feasibility-detail-table .feasibility-row-detail th, #feasibility-detail-table .feasibility-row-detail td { white-space: normal; overflow-wrap: anywhere; }
    [data-classification][hidden], .feasibility-detail-sidebar[hidden] { display: none !important; }
    @media (max-width: 991px) { .feasibility-detail-layout { grid-template-columns: minmax(0, 1fr); } .feasibility-detail-sidebar .feasibility-category-list { max-height: 215px; } .feasibility-detail-sidebar .feasibility-category-grid { grid-template-columns: repeat(auto-fit, minmax(min(175px, 100%), 1fr)); } }
    @media (max-width: 575px) { #feasibility-detail-modal .modal-header { grid-template-columns: minmax(0, 1fr) auto; } #feasibility-detail-modal .modal-header > div { grid-column: 1; } #feasibility-download { grid-column: 1; justify-self: start; margin-left: 0 !important; } #feasibility-detail-modal .btn-close { grid-column: 2; grid-row: 1; align-self: start; } }
    @media (prefers-reduced-motion: reduce) { button.feasibility-kpi { transition: none; } button.feasibility-kpi:hover { transform: none; } }
    [data-bs-theme="dark"] .feasibility-kpi, [data-bs-theme="dark"] .feasibility-category-card { background: color-mix(in srgb, var(--tone) 8%, #111827); }
    [data-bs-theme="dark"] .feasibility-category-card { border-color: color-mix(in srgb, var(--tone) 24%, #1e293b); }
    [data-bs-theme="dark"] .feasibility-kpi-value { color: #f1f5f9; }
    [data-bs-theme="dark"] .feasibility-kpi-label { color: #a8bad0; }
    [data-bs-theme="dark"] .feasibility-kpi-icon, [data-bs-theme="dark"] .feasibility-kpi::after, [data-bs-theme="dark"] .feasibility-category-progress { background: color-mix(in srgb, var(--tone) 18%, #1e293b); }
    @media (max-width: 1199px) { .feasibility-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 575px) { .feasibility-kpis { gap: .65rem; } .feasibility-kpi { padding: 1rem; min-height: 140px; } .feasibility-kpi-label { font-size: .72rem; padding-right: 0; } .feasibility-kpi-icon { position: static; width: 2rem; height: 2rem; margin-bottom: .5rem; border-radius: .6rem; } .feasibility-kpi-icon svg { width: 1.2rem; height: 1.2rem; } .feasibility-kpi-value { font-size: 1.8rem; margin-top: .65rem; } .feasibility-panel-header, .feasibility-panel-body { padding: 1rem; } .feasibility-header h1 { font-size: 1.3rem; } .feasibility-header img { width: 45px; height: 45px; } }
</style>
@endpush

@section('content')
@php
    $total = $stats['Total Site'];
    $tones = ['blue', 'mint', 'amber', 'violet'];
    $icons = ['heroicon-o-building-office-2', 'heroicon-o-user-group', 'heroicon-o-squares-2x2', 'heroicon-o-signal'];
    $overviewKeys = ['sites', 'owners', 'classes', 'coverage'];
    $mlaColors = ['MLA' => '#10b981', 'Non MLA' => '#3b82f6', 'Belum diisi' => '#94a3b8'];
    $percentage = fn ($value) => $total > 0 ? $value / $total * 100 : 0;
    $formatPercentage = fn ($value) => number_format($percentage($value), $value > 0 && $percentage($value) < .1 ? 2 : 1, ',', '.');
@endphp
<div class="feasibility-dashboard">
    <header class="feasibility-header">
        <img src="{{ asset('assets/modules/feasibility.svg') }}" alt="">
        <div><h1>Feasibility Site</h1><p>Profil site, klasifikasi, dan status MLA dari Data Potensi.</p></div>
    </header>

    <div class="feasibility-kpis" aria-label="Statistik Feasibility Site">
        @foreach ($stats as $label => $value)
            <button type="button" class="feasibility-kpi feasibility-tone-{{ $tones[$loop->index] }}" data-feasibility-open="{{ $overviewKeys[$loop->index] }}" aria-label="Lihat data {{ $label }}" aria-haspopup="dialog">
                <div class="feasibility-kpi-icon"><x-filament::icon :icon="$icons[$loop->index]" /></div>
                <span class="feasibility-kpi-label">{{ $label }}</span>
                <strong class="feasibility-kpi-value">{{ number_format($value, 0, ',', '.') }}</strong>
            </button>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <section class="feasibility-panel">
                <header class="feasibility-panel-header">
                    <div><h2>NOP per Site Class</h2></div>
                    <button type="button" data-feasibility-open="classes" class="btn btn-sm btn-outline-secondary">Lihat data</button>
                </header>
                <div class="feasibility-panel-body"><div class="feasibility-chart-scroll" tabindex="0" role="region" aria-label="Grafik Site Class per NOP"><div id="feasibility-class-chart" class="feasibility-chart"></div></div></div>
            </section>
        </div>
        <div class="col-xl-4">
            <section class="feasibility-panel">
                <header class="feasibility-panel-header"><div><h2>Status MLA &amp; Non MLA</h2></div></header>
                <div class="feasibility-panel-body">
                    <div class="feasibility-mla-chart">
                        <div id="feasibility-mla-chart" aria-label="Diagram status MLA dan Non MLA"></div>
                        <div class="feasibility-donut-total"><strong>{{ number_format($total, 0, ',', '.') }}</strong><span>Total Site</span></div>
                    </div>
                    <ul class="feasibility-mla-values">
                        @foreach ($charts['mla'] as $point)
                            <li><button type="button" data-mla-status="{{ $point['name'] }}" aria-label="Lihat site status {{ $point['name'] }}"><span class="feasibility-dot" style="background:{{ $mlaColors[$point['name']] ?? '#a78bfa' }}"></span><span>{{ $point['name'] }}</span><span class="value">{{ number_format($point['y'], 0, ',', '.') }}</span><span class="percentage">{{ $formatPercentage($point['y']) }}%</span></button></li>
                        @endforeach
                    </ul>
                    @if (collect($charts['mla'])->contains(fn ($point) => $point['name'] === 'Belum diisi' && $point['y'] > 0))
                        <p class="feasibility-note mt-3">Status kosong ditampilkan terpisah sebagai “Belum diisi”.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <section class="feasibility-panel">
                <header class="feasibility-panel-header"><div><h2>Distribusi Site per NOP</h2></div><button type="button" data-feasibility-open="sites" class="btn btn-sm btn-outline-secondary">Lihat data</button></header>
                <div class="feasibility-panel-body"><div class="feasibility-chart-scroll" tabindex="0" role="region" aria-label="Grafik distribusi site per NOP"><div id="feasibility-nop-chart" class="feasibility-chart"></div></div></div>
            </section>
        </div>
        <div class="col-xl-6">
            <section class="feasibility-panel">
                <header class="feasibility-panel-header">
                    <div><h2>Asset berdasarkan Tipe Tower</h2><p>{{ number_format(array_sum(array_column($charts['tower'], 'y')), 0, ',', '.') }} baris aset · seluruh tipe tower.</p></div>
                    <button type="button" data-feasibility-open="tower" class="btn btn-sm btn-outline-secondary">Lihat data</button>
                </header>
                <div class="feasibility-panel-body"><div class="feasibility-chart-scroll" tabindex="0" role="region" aria-label="Grafik aset berdasarkan tipe tower"><div id="feasibility-tower-chart" class="feasibility-chart"></div></div></div>
            </section>
        </div>
    </div>
    <p class="feasibility-note mt-3 mb-0">Sumber profil site: Data Potensi → Site Owner. Jumlah klasifikasi tidak mencakup nilai kosong; kategori “Belum diisi” tetap disertakan dalam distribusi.</p>
</div>
@include('data-potensi.partials.feasibility-details')
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
$(function () {
    const data = @json($charts);
    const instances = [];
    const number = value => Number(value).toLocaleString('id-ID');
    const escape = value => $('<div>').text(String(value)).html();
    const statusColors = @json($mlaColors);
    const palette = window.SimasterChartPalette.series;

    document.querySelectorAll('[data-classification]').forEach(section => {
        const input = section.querySelector('input[type="search"]');
        const cards = [...section.querySelectorAll('[data-category-name]')];
        input.addEventListener('input', () => {
            const query = input.value.trim().toLocaleLowerCase('id-ID');
            let visible = 0;
            cards.forEach(card => {
                card.hidden = !card.dataset.categoryName.toLocaleLowerCase('id-ID').includes(query);
                if (!card.hidden) visible++;
            });
            section.querySelector('.feasibility-category-status').textContent = `Menampilkan ${number(visible)} dari ${number(cards.length)} kategori · jumlah site terbanyak`;
            section.querySelector('.feasibility-no-results').hidden = visible > 0;
        });
    });

    function render() {
        instances.splice(0).forEach(chart => chart.destroy());
        const dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const text = dark ? '#e2e8f0' : '#334155';
        const base = {
            chart: { backgroundColor: 'transparent', style: { fontFamily: 'inherit' }, animation: { duration: 300 } },
            title: { text: null }, credits: { enabled: false }, colors: palette,
            legend: { itemStyle: { color: text, fontWeight: '500' }, itemHoverStyle: { color: dark ? '#fff' : '#0f172a' } },
            xAxis: { labels: { style: { color: text, fontSize: '12px' } }, lineColor: dark ? '#334155' : '#e2e8f0' },
            yAxis: { min: 0, allowDecimals: false, title: { text: 'Jumlah site', style: { color: text } }, labels: { style: { color: text }, formatter() { return number(this.value); } }, gridLineColor: dark ? '#334155' : '#e2e8f0' },
            tooltip: { backgroundColor: dark ? '#1e293b' : '#fff', style: { color: text } },
            plotOptions: { series: { cursor: 'pointer', animation: { duration: 300 }, borderWidth: 0, dataLabels: { style: { color: text, textOutline: 'none' }, formatter() { return number(this.y); } } } },
        };
        const draw = (id, hasData, options) => {
            const container = document.getElementById(id);
            if (!hasData) {
                container.innerHTML = '<p class="feasibility-note p-4 text-center">Belum ada data untuk ditampilkan.</p>';
                return;
            }
            instances.push(Highcharts.chart(container, Highcharts.merge(base, options)));
        };
        const manyClasses = data.siteClass.series.length > 6 || data.siteClass.categories.length > 5;
        draw('feasibility-class-chart', data.siteClass.categories.length > 0, {
            chart: { type: manyClasses ? 'bar' : 'column', height: manyClasses ? Math.max(370, data.siteClass.categories.length * data.siteClass.series.length * 20 + 110) : 390 },
            xAxis: { categories: data.siteClass.categories },
            legend: { enabled: true, align: 'center', verticalAlign: 'top', maxHeight: 90 },
            tooltip: { shared: true, formatter() {
                return `<b>${escape(data.siteClass.categories[this.x] ?? this.key)}</b><br>` + (this.points || []).map(point => `<span style="color:${point.color}">●</span> ${escape(point.series.name)}: <b>${number(point.y)}</b> site`).join('<br>');
            } },
            plotOptions: { column: { borderRadius: 4, groupPadding: .18, pointPadding: .04 }, series: { dataLabels: { enabled: false }, point: { events: { click() {
                window.SimasterFeasibilityDetails.open({title:'Site per NOP & Site Class', filters:{nop:data.siteClass.categories[this.x], site_class:this.series.name}});
            } } } } },
            series: data.siteClass.series.map(series => ({ ...series, color: dark && series.name === 'Platinum' ? '#cbd5e1' : series.color })),
        });
        draw('feasibility-mla-chart', data.mla.some(point => point.y > 0), {
            chart: { type: 'pie', height: 220, spacing: [5, 0, 5, 0] }, legend: { enabled: false },
            tooltip: { formatter() { return `<b>${escape(this.name)}</b><br>${number(this.y)} site (${Highcharts.numberFormat(this.percentage, 1, ',', '.')}%)`; } },
            plotOptions: { pie: { innerSize: '74%', size: '95%', dataLabels: { enabled: false }, borderRadius: 3 }, series: {point:{events:{click() {
                window.SimasterFeasibilityDetails.open({title:'Site berdasarkan Status MLA', filters:{status_mla:this.name}});
            }}}} },
            series: [{ name: 'Site', data: data.mla.map(point => ({ ...point, color: statusColors[point.name] || '#a78bfa' })) }],
        });
        [['nop', 'site'], ['tower', 'baris aset']].forEach(([key, unit]) => {
            const points = data[key];
            draw(`feasibility-${key}-chart`, points.some(point => point.y > 0), {
                chart: { type: 'bar', height: Math.max(340, points.length * 33 + 65) },
                colors: window.SimasterChartPalette.distinctColors(points.length),
                legend: { enabled: false }, xAxis: { type: 'category' },
                yAxis: { title: { text: key === 'tower' ? 'Jumlah aset' : 'Jumlah site' } },
                tooltip: { formatter() { return `${escape(this.key)}: <b>${number(this.y)}</b> ${unit}`; } },
                plotOptions: { series: { colorByPoint: true, borderRadius: 3, dataLabels: { enabled: true }, point:{events:{click() {
                    window.SimasterFeasibilityDetails.open({dataset:key === 'tower' ? 'tower' : 'sites', title:key === 'tower' ? 'Asset berdasarkan Tipe Tower' : 'Site per NOP', filters:{[key === 'tower' ? 'tower_type' : 'nop']:this.name}});
                }}} } },
                series: [{ name: unit, data: points }],
            });
        });
    }
    render();
    window.addEventListener('simaster:theme-changed', render);
});
</script>
@endpush
