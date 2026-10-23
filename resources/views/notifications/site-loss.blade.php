@extends('layouts.app')

@section('title', 'Site Loss - SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 mb-1">Daftar Site Loss</h1>
            @if ($year !== null)
                <p class="text-muted mb-0">
                    Seluruh site dengan profit/loss negatif pada periode {{ sprintf('%02d/%d', $month, $year) }}.
                </p>
            @else
                <p class="text-muted mb-0">Belum ada data periode site.</p>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('notifications.site-loss.export') }}" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
            <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary btn-sm">Kembali ke notifikasi</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
            <strong>Site Loss</strong>
            <span class="badge text-bg-danger">{{ $sites->count() }} site</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Site ID</th>
                        <th>Nama Site</th>
                        <th>Region</th>
                        <th class="text-end pe-4">Profit/Loss</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sites as $metric)
                        <tr>
                            <td class="ps-4 fw-semibold">{{ $metric->site?->site_id ?? '-' }}</td>
                            <td>{{ $metric->site?->site_name ?? '-' }}</td>
                            <td>{{ $metric->site?->region?->nama ?? '-' }}</td>
                            <td class="text-end pe-4 text-danger fw-semibold">
                                Rp {{ number_format((float) $metric->profit_loss, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">Tidak ada site Loss pada periode terbaru.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
