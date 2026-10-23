@extends('layouts.app')

@section('title', 'BAPSS — Infrastruktur Management — SIMAWAR')

@section('content')
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Infrastruktur Management — BAPSS</h1>
        @include('infrastruktur.partials.entry-actions', ['dataset' => 'bapss'])
    </div>

    {{-- Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data BAPSS">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex gap-2 ms-auto">
                <a href="{{ route('infrastruktur.bapss.export-excel') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 text-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                    Excel
                </a>
                <a href="{{ route('infrastruktur.bapss.export-csv') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 text-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                    CSV
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="bapss-table" class="display align-middle text-nowrap" style="width:100%">
                <thead><tr>
                    <th>No</th>
                    @role('admin') <th>Aksi</th> @endrole
                    <th>Site ID</th>
                    <th>Site Name</th>
                    <th>Tgl BAPSS</th>
                    <th>Tgl Dismantle</th>
                    <th>Remark</th>
                    <th>PDF BAPSS</th>
                    <th>PDF BA Dismantle</th>
                    <th>Update By</th>
                    <th>Tgl Update</th>
                </tr></thead>
            </table>
        </div>
    </div>
    @include('infrastruktur.partials.manual-entry-modal', ['dataset' => 'bapss'])
@endsection

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};

    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];
    if (isAdmin) {
        columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false });
    }
    columns.push(
        { data: 'site_code', name: 'site_code' },
        { data: 'site_name', name: 'site_name' },
        { data: 'tgl_bapss', name: 'tgl_bapss' },
        { data: 'tgl_dismantle', name: 'tgl_dismantle' },
        { data: 'remark', name: 'remark' },
        { data: 'pdf_bapss_link', name: 'pdf_bapss_link', orderable: false, searchable: false },
        { data: 'pdf_ba_dismantle_link', name: 'pdf_ba_dismantle_link', orderable: false, searchable: false },
        { data: 'update_by', name: 'update_by' },
        { data: 'tgl_update', name: 'tgl_update' },
    );

    const table = new DataTable('#bapss-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('infrastruktur.bapss.data')) },
        columns,
        order: [],
        pageLength: 10,
        createdRow: function (row, data) {
            row.classList.add('table-row-link');
            row.addEventListener('click', function (event) {
                if (event.target.closest('a,button,form')) return;
                window.location = @json(route('infrastruktur.bapss.show', '__id__')).replace('__id__', data.id);
            });
        },
    });

    $('#page-size').on('change', function () { table.page.len(parseInt($(this).val())).draw(); });
});
</script>
@endpush
