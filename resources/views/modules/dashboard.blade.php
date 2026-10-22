@extends('layouts.app')

@section('title', $title.' — SIMAWAR')

@push('styles')
<style>
    .po-tone-blue { --po-accent: var(--report-blue, #3b82f6); }
    .po-tone-mint { --po-accent: var(--report-mint, #0d9488); }
    .po-tone-violet { --po-accent: var(--report-violet, #8b5cf6); }
    .simaster-report button.po-summary-card {
        border: 1px solid color-mix(in srgb, var(--po-accent) 50%, transparent);
        background: linear-gradient(120deg, color-mix(in srgb, var(--po-accent) 12%, var(--bs-body-bg)), var(--bs-body-bg));
        color: var(--bs-body-color); cursor: pointer; transition: box-shadow .15s, transform .15s;
    }
    .simaster-report button.po-summary-card:hover { transform: translateY(-2px); box-shadow: 0 .35rem 1rem color-mix(in srgb, var(--po-accent) 15%, transparent); }
    .simaster-report button.po-summary-card:focus-visible { outline: 3px solid var(--po-accent); outline-offset: 3px; }
    .po-summary-icon { display: grid; place-items: center; width: 48px; height: 48px; border-radius: 14px; background: color-mix(in srgb, var(--po-accent) 16%, transparent); color: var(--po-accent); flex-shrink: 0; }
    .po-summary-icon svg { width: 25px; height: 25px; }
    #po-detail-table td { vertical-align: top; min-width: 110px; max-width: 360px; white-space: normal; overflow-wrap: anywhere; }
    #po-detail-modal .modal-body { min-width: 0; }
</style>
@endpush

@section('content')
    <header class="page-hero d-flex align-items-center gap-3 mb-4">
        <img src="{{ asset('assets/modules/'.$image.'.svg') }}" alt="" width="64" height="64">
        <div><h1 class="h4 mb-0">{{ $title }}</h1></div>
    </header>

    <div class="row g-3 mb-4">
        @foreach ($datasets as $key => $dataset)
            <div class="col-md-4 col-12">
                <button type="button" class="card po-summary-card po-tone-{{ $dataset['tone'] }} h-100 w-100 text-start" data-po-open="{{ $key }}" aria-label="Lihat data {{ $dataset['title'] }}">
                    <span class="card-body d-flex justify-content-between align-items-center gap-3 w-100">
                        <span><span class="d-block small text-body-secondary mb-1">{{ $dataset['title'] }}</span><span class="d-block fs-3 fw-bold">{{ number_format($stats[$dataset['title']], 0, ',', '.') }}</span></span>
                        <span class="po-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                @if ($key === 'hq')
                                    <path d="M6 3h8l4 4v14H6zM14 3v5h4M9 12h6M9 16h4"/>
                                @elseif ($key === 'varcost')
                                    <rect x="4" y="5" width="16" height="16" rx="2"/><path d="M8 3v4M16 3v4M4 10h16m-12 5 3 3 5-5"/>
                                @else
                                    <path d="M3 7h7l2 2h9v11H3zM3 7V4h7l2 3h7"/><path d="m8 14 3 3 5-5"/>
                                @endif
                            </svg>
                        </span>
                    </span>
                </button>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        @foreach ($charts as $index => $chart)
            <div class="col-xl-6">
                <section class="card h-100">
                    <header class="card-header d-flex justify-content-between align-items-center gap-2">
                        <h2 class="h6 mb-0">{{ $chart['title'] }}</h2>
                        <button type="button" data-po-open="{{ $chart['dataset'] }}" class="btn btn-sm btn-outline-secondary text-nowrap" aria-label="Lihat data {{ $chart['title'] }}">Buka data</button>
                    </header>
                    <div class="card-body"><div id="module-chart-{{ $index }}" style="min-height:330px" aria-label="{{ $chart['title'] }}"></div></div>
                </section>
            </div>
        @endforeach
    </div>

    <div class="modal fade" id="po-detail-modal" tabindex="-1" aria-labelledby="po-detail-title" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header gap-3">
                    <div><h2 class="modal-title h5" id="po-detail-title">Data PO Monitoring</h2><p class="small text-body-secondary mb-0 mt-1" id="po-detail-count" role="status" aria-live="polite"></p></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup rincian"></button>
                </header>
                <div class="modal-body">
                    <div class="row g-2 align-items-end mb-3">
                        <div class="col-md-3"><label for="po-detail-dataset" class="form-label small">Submodul</label><select id="po-detail-dataset" class="form-select">@foreach ($datasets as $key => $dataset)<option value="{{ $key }}">{{ $dataset['title'] }}</option>@endforeach</select></div>
                        <div class="col-md"><label for="po-detail-search" class="form-label small">Pencarian</label><input id="po-detail-search" type="search" maxlength="255" class="form-control" placeholder="Cari data…" aria-controls="po-detail-table"></div>
                        <div class="col-md-auto"><button type="button" id="po-detail-reset" class="btn btn-outline-secondary w-100">Tampilkan semua</button></div>
                    </div>
                    <div id="po-detail-error" class="alert alert-danger py-2" role="alert" hidden></div>
                    <table id="po-detail-table" class="table table-hover align-middle" style="width:100%"><thead></thead><tbody></tbody></table>
                </div>
                <footer class="modal-footer"><a id="po-detail-source" class="btn btn-primary" href="{{ route('po-hq.index') }}">Buka modul</a><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></footer>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script>
window.SimasterPoMonitoring = { datasets: @json($datasets), charts: @json($charts), detailsUrl: @json(route('po-monitoring.details')) };
</script>
<script src="{{ asset('assets/po-monitoring/js/dashboard.js') }}?v={{ filemtime(public_path('assets/po-monitoring/js/dashboard.js')) }}"></script>
@endpush
