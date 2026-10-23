@extends('layouts.app')

@section('title', 'Data Site (All Resource) — Feasibility Site — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">Data Site (All Resource)</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'site-owner'])
        <a id="download-excel" href="{{ route('data-potensi.data-site.export-excel') }}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
        </div>
    </div>

    <div class="card mb-3" data-simaster-filter-panel="Filter Data Site">
        <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <label for="owner-filter" class="form-label mb-0 small fw-semibold text-nowrap">Site Owner:</label>
                <select id="owner-filter" class="form-select form-select-sm" style="max-width: 320px">
                    <option value="">All</option>
                    @foreach ($owners as $owner)
                        <option value="{{ $owner }}">{{ $owner }}</option>
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
            <div class="d-flex align-items-center gap-2">
                <label for="nop-filter" class="form-label mb-0 small fw-semibold text-nowrap">NOP:</label>
                <select id="nop-filter" class="form-select form-select-sm" style="max-width: 280px">
                    <option value="">All</option>
                    @foreach ($nops as $nop)
                        <option value="{{ $nop }}">{{ $nop }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="data-site-table" class="display align-middle text-nowrap" style="width:100%; cursor:pointer">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Site ID (Dapot)</th>
                        <th>Site Name (Dapot)</th>
                        <th>Class (Dapot)</th>
                        <th>City (Dapot)</th>
                        <th>NOP (Dapot)</th>
                        <th>Coverage (Dapot)</th>
                        <th>PLN (Dapot)</th>
                        <th>Capacity (Dapot)</th>
                        <th>ID Pel (Dapot)</th>
                        <th>Tgl Update (Dapot)</th>
                        <th>ANT Site (ANT)</th>
                        <th>Site Owner (ANT)</th>
                        <th>RTP (ANT)</th>
                        <th>Type (ANT)</th>
                        <th>Alamat (ANT)</th>
                        <th>Tgl Update (ANT)</th>
                        <th>Contract (Ipas)</th>
                        <th>Contract Type (Ipas)</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="data-site-detail-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Data Site: <span id="data-site-detail-title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered align-middle mb-0">
                        <tbody id="data-site-detail-body"></tbody>
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

    const table = new DataTable('#data-site-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        pageLength: 10,
        lengthChange: false,
        ajax: {
            url: @json(route('data-potensi.data-site.data')),
            data: function (data) {
                data.site_owner = $('#owner-filter').val();
                data.city = $('#city-filter').val();
                data.nop = $('#nop-filter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
            { data: 'site_id', name: 'site_id', render: value },
            { data: 'site_name', name: 'site_name', render: value },
            { data: 'site_class', name: 'site_class', render: value },
            { data: 'city', name: 'city', render: value },
            { data: 'nop', name: 'nop', render: value },
            { data: 'coverage_type', name: 'coverage_type', render: value },
            { data: 'pln_connection', name: 'pln_connection', render: value },
            { data: 'capacity', name: 'capacity', render: value },
            { data: 'id_pel', name: 'id_pel', render: value },
            { data: 'tgl_update', name: 'tgl_update', render: value },
            { data: 'ant_site', name: 'ant_site', render: value },
            { data: 'ant_site_owner', name: 'ant_site_owner', render: value },
            { data: 'ant_rtp', name: 'ant_rtp', render: value },
            { data: 'ant_type', name: 'ant_type', render: value },
            { data: 'ant_alamat', name: 'ant_alamat', render: value },
            { data: 'ant_tgl_update', name: 'ant_tgl_update', render: value },
            { data: 'ipas_contract', name: 'ipas_contract', render: value },
            { data: 'ipas_contract_type', name: 'ipas_contract_type', render: value }
        ],
        order: []
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val(), 10)).draw();
    });

    $('#owner-filter').on('change', function () {
        table.ajax.reload(refreshDownloadUrl);
    });
    $('#city-filter').on('change', function () {
        table.ajax.reload(refreshDownloadUrl);
    });
    $('#nop-filter').on('change', function () {
        table.ajax.reload(refreshDownloadUrl);
    });

    function refreshDownloadUrl() {
        const params = new URLSearchParams({
            site_owner: $('#owner-filter').val(),
            city: $('#city-filter').val(),
            nop: $('#nop-filter').val(),
            search: $('#data-site-table_wrapper input[type="search"]').val() || ''
        });
        $('#download-excel').attr(
            'href',
            @json(route('data-potensi.data-site.export-excel')) + '?' + params.toString()
        );
    }

    table.on('draw', refreshDownloadUrl);
    $('#data-site-table_wrapper input[type="search"]').on('input', function () {
        refreshDownloadUrl();
    });
    refreshDownloadUrl();

    $('#data-site-table tbody').on('click', 'tr', function () {
        const row = table.row(this).data();
        if (!row || !row.site_id) return;

        fetch(`${@json(url('data-potensi/data-site'))}/${encodeURIComponent(row.site_id)}`, {
            headers: { Accept: 'application/json' }
        })
            .then(response => {
                if (!response.ok) throw new Error('Gagal memuat detail Data Site.');
                return response.json();
            })
            .then(({ data }) => {
                const detailValue = (item) => item === null || item === undefined || item === ''
                    ? '-'
                    : $('<div>').text(item).html();
                const rows = [
                    ['Site ID (Dapot)', data.site_id],
                    ['Site Name (Dapot)', data.site_name],
                    ['Class (Dapot)', data.site_class],
                    ['City (Dapot)', data.city],
                    ['NOP (Dapot)', data.nop],
                    ['Coverage (Dapot)', data.coverage_type],
                    ['PLN (Dapot)', data.pln_connection],
                    ['Capacity (Dapot)', data.capacity],
                    ['ID Pel (Dapot)', data.id_pel],
                    ['Tgl Update (Dapot)', data.tgl_update],
                    ['ANT Site', data.ant_site],
                    ['Site Owner (ANT)', data.ant_site_owner],
                    ['RTP (ANT)', data.ant_rtp],
                    ['Type (ANT)', data.ant_type],
                    ['Alamat (ANT)', data.ant_alamat],
                    ['Tgl Update (ANT)', data.ant_tgl_update],
                    ['Contract (Ipas)', data.ipas_contract],
                    ['Contract Type (Ipas)', data.ipas_contract_type]
                ];
                $('#data-site-detail-title').text(data.site_id || 'Detail Data Site');
                $('#data-site-detail-body').html(rows.map(([label, item]) =>
                    `<tr><th>${label}</th><td>${detailValue(item)}</td></tr>`).join(''));
                bootstrap.Modal.getOrCreateInstance('#data-site-detail-modal').show();
            })
            .catch(error => window.alert(error.message));
    });
});
</script>
@endpush
