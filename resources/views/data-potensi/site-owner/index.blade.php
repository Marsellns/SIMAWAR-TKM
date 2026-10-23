@extends('layouts.app')

@section('title', 'Site Owner — Feasibility Site — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">Site Owner (Dapot &amp; ANT)</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'site-owner'])
        <a id="download-excel" href="{{ route('data-potensi.site-owner.export-excel') }}" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
        </div>
    </div>

    <div class="card mb-3" data-simaster-filter-panel="Filter Site Owner">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <label for="owner-filter" class="form-label mb-0 small fw-semibold">Site Owner:</label>
            <select id="owner-filter" class="form-select form-select-sm" style="max-width: 320px">
                <option value="">All</option>
                @foreach ($owners as $owner)
                    <option value="{{ $owner }}">{{ $owner }}</option>
                @endforeach
            </select>
            <label for="city-filter" class="form-label mb-0 small fw-semibold">City:</label>
            <select id="city-filter" class="form-select form-select-sm" style="max-width: 280px">
                <option value="">All</option>
                @foreach ($cities as $city)
                    <option value="{{ $city }}">{{ $city }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="site-owner-table" class="display align-middle text-nowrap" style="width:100%; cursor:pointer">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID</th>
                        <th>Site Name</th>
                        <th>Site Class</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="site-owner-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Site Owner: <span id="site-owner-detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered align-middle mb-0">
                        <tbody id="site-owner-detail-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const table = new DataTable('#site-owner-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        pageLength: 10,
        lengthMenu: [[10, 20, 40, 80, 100, 5000], [10, 20, 40, 80, 100, 5000]],
        ajax: {
            url: @json(route('data-potensi.site-owner.data')),
            data: function (data) {
                data.site_owner = $('#owner-filter').val();
                data.city = $('#city-filter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
            { data: 'site_code', name: 'site_code' },
            { data: 'site_name', name: 'site_name' },
            { data: 'site_class', name: 'site_class' }
        ],
        order: []
    });

    $('#owner-filter').on('change', function () {
        table.ajax.reload();
    });
    $('#city-filter').on('change', function () {
        table.ajax.reload();
    });

    function refreshDownloadUrl() {
        const params = new URLSearchParams({
            site_owner: $('#owner-filter').val(),
            city: $('#city-filter').val(),
            search: table.search()
        });
        $('#download-excel').attr('href', @json(route('data-potensi.site-owner.export-excel')) + '?' + params.toString());
    }

    table.on('draw', refreshDownloadUrl);

    $('#site-owner-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row || !row.id) return;
        fetch(`${@json(url('data-potensi/site-owner'))}/${row.id}`, {
            headers: { 'Accept': 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat detail Site Owner.');
                return response.json();
            })
            .then(({ data }) => {
                $('#site-owner-detail-title').text(data.site_code || 'Detail Site Owner');
                const value = (item) => item === null || item === undefined || item === '' ? '-' : $('<div>').text(item).html();
                $('#site-owner-detail-body').html(`
                    <tr><th>Alamat</th><td>${value(data.alamat)}</td></tr>
                    <tr><th>City</th><td>${value(data.city)}</td></tr>
                    <tr><th>NOP</th><td>${value(data.nop)}</td></tr>
                    <tr><th>Coverage Type</th><td>${value(data.coverage_type)}</td></tr>
                    <tr><th>Status</th><td>${value(data.status_mla)}</td></tr>
                    <tr><th>PLN Connection</th><td>${value(data.pln_connection)}</td></tr>
                    <tr><th>Capacity</th><td>${value(data.capacity)}</td></tr>
                    <tr><th>ID Pel</th><td>${value(data.id_pel)}</td></tr>
                    <tr><th>Tower Height</th><td>${value(data.tower_height)}</td></tr>
                    <tr><th>Site Owner</th><td>${value(data.site_owner)}</td></tr>
                    <tr><th>Tgl. Update</th><td>${value(data.tgl_update)}</td></tr>
                `);
                bootstrap.Modal.getOrCreateInstance('#site-owner-detail-modal').show();
            })
            .catch(error => window.alert(error.message));
    });
});
</script>
@endpush
