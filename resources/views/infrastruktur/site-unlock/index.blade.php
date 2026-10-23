@extends('layouts.app')

@section('title', 'Data Site Unlock — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — Data Site Unlock</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('infrastruktur.partials.entry-actions', ['dataset' => 'site-unlock'])
            <a href="{{ route('infrastruktur.upload.export', 'site-unlock') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data Site Unlock">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="unlock-table" class="display align-middle text-nowrap" style="width:100%">
                <thead><tr>
                    <th>No</th>
                    <th>Site ID</th>
                    <th>Site Name</th>
                    <th>Class</th>
                    <th>City</th>
                    <th>Batch</th>
                    <th>Status</th>
                    <th>Final Status</th>
                    <th>Update By</th>
                    <th>Tanggal</th>
                </tr></thead>
            </table>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'site-unlock'])
@endsection

@push('styles')
<style>
    /* Badge status colors — mengikuti design system SIMAWAR */
    .badge-done   { background: #DCFCE7; color: #166534; }
    .badge-progress { background: #DBEAFE; color: #1E40AF; }
    .badge-pending  { background: #FEF3C7; color: #92400E; }
    .badge-cancel   { background: #FFEDD5; color: #9A3412; }
    .badge-default  { background: #F1F5F9; color: #64748B; }
    .status-badge {
        font-weight: 600; font-size: 0.72rem;
        padding: 0.3em 0.65em; border-radius: 0.45rem;
        display: inline-block; white-space: nowrap;
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    // --- Status badge color mapping ---
    function statusBadge(value) {
        if (!value || value === '-') return '<span class="status-badge badge-default">-</span>';
        const v = String(value).toLowerCase();
        let cls = 'badge-default';
        if (v.includes('done') || v.includes('selesai') || v.includes('complete') || v.includes('aktif'))
            cls = 'badge-done';
        else if (v.includes('progress') || v.includes('proses') || v.includes('running'))
            cls = 'badge-progress';
        else if (v.includes('pending') || v.includes('hold') || v.includes('waiting') || v.includes('review'))
            cls = 'badge-pending';
        else if (v.includes('cancel') || v.includes('batal') || v.includes('reject') || v.includes('dismantle'))
            cls = 'badge-cancel';
        return `<span class="status-badge ${cls}">${$('<div>').text(value).html()}</span>`;
    }

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
        { data: 'site_code', name: 'site_code' },
        { data: 'site_name', name: 'site_name' },
        { data: 'site_class', name: 'site_class' },
        { data: 'city', name: 'city' },
        { data: 'batch', name: 'batch' },
        { data: 'status', name: 'status', render: (d) => statusBadge(d) },
        { data: 'final_status', name: 'final_status', render: (d) => statusBadge(d) },
        { data: 'update_by', name: 'update_by' },
        { data: 'tanggal', name: 'tanggal',
          render: (d) => (!d || d === '-') ? '<span class="status-badge badge-default">-</span>' : d },
    ];

    const table = new DataTable('#unlock-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('infrastruktur.site-unlock.data')) },
        columns,
        order: [],
        pageLength: 10,
    });

    $('#page-size').on('change', function () { table.page.len(parseInt($(this).val())).draw(); });
});
</script>
@endpush
