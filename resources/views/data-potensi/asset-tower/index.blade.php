@extends('layouts.app')

@section('title', 'Data Asset Tower — Feasibility Site — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">Data Asset Tower</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'asset-tower'])
        <a id="download-excel" href="{{ route('data-potensi.asset-tower.export-excel') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
        </div>
    </div>

    <div class="card mb-3" data-simaster-filter-panel="Filter Asset Tower">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="nop-filter" class="form-label mb-0 small fw-semibold text-nowrap">NOP:</label>
                <select id="nop-filter" class="form-select form-select-sm" style="max-width: 280px">
                    <option value="">All</option>
                    @foreach ($nops as $nop)
                        <option value="{{ $nop }}">{{ $nop }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="city-filter" class="form-label mb-0 small fw-semibold text-nowrap">City:</label>
                <select id="city-filter" class="form-select form-select-sm" style="max-width: 280px">
                    <option value="">All</option>
                    @foreach ($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="asset-tower-table" class="display align-middle text-nowrap" style="width:100%; cursor:pointer">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Site Company</th>
                        <th>Site Type</th>
                        <th>Grouping</th>
                        <th>Brand</th>
                        <th>Part Name</th>
                        <th>Owner</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="asset-tower-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Asset Tower: <span id="asset-tower-detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered align-middle mb-0">
                        <tbody id="asset-tower-detail-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const value = (data) => data === null || data === undefined || data === ''
        ? '-'
        : $('<div>').text(data).html();

    const table = new DataTable('#asset-tower-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        pageLength: 10,
        lengthMenu: [[10, 20, 40, 80, 100, 5000], [10, 20, 40, 80, 100, 5000]],
        ajax: {
            url: @json(route('data-potensi.asset-tower.data')),
            data: function (data) {
                data.nop = $('#nop-filter').val();
                data.city = $('#city-filter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
            { data: 'site_id', name: 'site_id', render: value },
            { data: 'site_name', name: 'site_name', render: value },
            { data: 'site_company', name: 'site_company', render: value },
            { data: 'site_type', name: 'site_type', render: value },
            { data: 'grouping', name: 'grouping', render: value },
            { data: 'brand', name: 'brand', render: value },
            { data: 'part_name', name: 'part_name', render: value },
            { data: 'owner', name: 'owner', render: value }
        ],
        order: []
    });

    $('#nop-filter, #city-filter').on('change', function () {
        table.ajax.reload();
    });

    function refreshDownloadUrl() {
        const search = $('#asset-tower-table_wrapper input[type="search"]').val() || '';
        const params = new URLSearchParams({
            nop: $('#nop-filter').val(),
            city: $('#city-filter').val(),
            search
        });
        $('#download-excel').attr(
            'href',
            @json(route('data-potensi.asset-tower.export-excel')) + '?' + params.toString()
        );
    }

    table.on('draw', refreshDownloadUrl);
    $('#asset-tower-table_wrapper input[type="search"]').on('input', refreshDownloadUrl);
    refreshDownloadUrl();

    $('#asset-tower-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row || !row.id) return;

        fetch(`${@json(url('data-potensi/asset-tower'))}/${row.id}`, {
            headers: { Accept: 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat detail Data Asset Tower.');
                return response.json();
            })
            .then(({ data }) => {
                const value = (item) => item === null || item === undefined || item === ''
                    ? '-'
                    : $('<div>').text(item).html();
                $('#asset-tower-detail-title').text(data.site_id || 'Detail Asset Tower');
                $('#asset-tower-detail-body').html(`
                    <tr><th>Ownership Status</th><td>${value(data.ownership_status)}</td></tr>
                    <tr><th>Note</th><td>${value(data.note)}</td></tr>
                    <tr><th>Tower Height</th><td>${value(data.tower_height)}</td></tr>
                    <tr><th>Building Height</th><td>${value(data.building_height)}</td></tr>
                    <tr><th>Tower Type</th><td>${value(data.tower_type)}</td></tr>
                    <tr><th>Update By</th><td>${value(data.update_by)}</td></tr>
                    <tr><th>Tanggal Update</th><td>${value(data.tanggal_update)}</td></tr>
                `);
                bootstrap.Modal.getOrCreateInstance('#asset-tower-detail-modal').show();
            })
            .catch(error => window.alert(error.message));
    });
});
</script>
@endpush
