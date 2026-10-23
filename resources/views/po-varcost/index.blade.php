@extends('layouts.app')

@section('title', 'PO Varcost — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">PO Varcost</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'po-varcost', 'entryLabel' => 'Upload Excel'])
            <a id="download-excel" href="{{ route('po-varcost.export-excel') }}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1 text-nowrap"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
            @role('admin')
                <button type="button" class="btn btn-brand btn-sm" id="add-po-varcost">Tambah Data</button>
            @endrole
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-2 mb-3" data-simaster-filter-panel="Filter PO Varcost">
                <div class="col-md-4">
                    <label for="expense-filter" class="form-label">Capex/Opex</label>
                    <select id="expense-filter" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach (\App\Models\PoVarcost::EXPENSE_TYPES as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status-filter" class="form-label">GR Status</label>
                    <select id="status-filter" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($grStatuses as $status)
                            <option value="{{ $status }}">{{ $status }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="year-filter" class="form-label">PO Year</label>
                    <select id="year-filter" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($poYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <table id="po-varcost-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        @role('admin')<th>Aksi</th>@endrole
                        <th>PO Number</th>
                        <th>Capex/Opex</th>
                        <th>Description</th>
                        <th>GR Status</th>
                        <th>PO Year</th>
                        <th>Delivery Date</th>
                        <th>Update By</th>
                        <th>Update At</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    @role('admin')
        <div class="modal fade" id="po-varcost-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="po-varcost-form" class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="po-varcost-modal-title">Tambah Data PO Varcost</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <div class="modal-body">
                        <div id="po-varcost-errors" class="alert alert-danger d-none"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="po_number">PO Number</label>
                                <input class="form-control" id="po_number" name="po_number" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="expense_type">Capex/Opex</label>
                                <select class="form-select" id="expense_type" name="expense_type" required>
                                    @foreach (\App\Models\PoVarcost::EXPENSE_TYPES as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="gr_status">GR Status</label>
                                <input class="form-control" id="gr_status" name="gr_status">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="po_year">PO Year</label>
                                <input class="form-control" id="po_year" name="po_year" type="number" min="1900" max="2200" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="delivery_date">Delivery Date</label>
                                <input class="form-control" id="delivery_date" name="delivery_date" type="date">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-brand">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endrole
@endsection

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const modal = isAdmin ? new bootstrap.Modal('#po-varcost-modal') : null;
    let editingId = null;
    const table = new DataTable('#po-varcost-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        pageLength: 10,
        lengthMenu: [[10, 20, 40, 80, 100, 5000], [10, 20, 40, 80, 100, 5000]],
        ajax: {
            url: @json(route('po-varcost.data')),
            data: function (data) {
                data.expense_type = $('#expense-filter').val();
                data.gr_status = $('#status-filter').val();
                data.po_year = $('#year-filter').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
            ...(isAdmin ? [{ data: 'aksi', name: 'aksi', orderable: false, searchable: false }] : []),
            { data: 'po_number', name: 'po_number' },
            { data: 'expense_type', name: 'expense_type' },
            { data: 'description', name: 'description' },
            { data: 'gr_status', name: 'gr_status' },
            { data: 'po_year', name: 'po_year' },
            { data: 'delivery_date', name: 'delivery_date' },
            { data: 'update_by', name: 'update_by' },
            { data: 'update_at', name: 'update_at' }
        ],
        order: []
    });

    $('#expense-filter, #status-filter, #year-filter').on('change', function () {
        table.ajax.reload();
    });

    function refreshDownloadUrl() {
        const params = new URLSearchParams({
            expense_type: $('#expense-filter').val(),
            gr_status: $('#status-filter').val(),
            po_year: $('#year-filter').val(),
            search: table.search()
        });
        $('#download-excel').attr('href', @json(route('po-varcost.export-excel')) + '?' + params.toString());
    }

    table.on('draw', refreshDownloadUrl);

    if (!isAdmin) return;

    function resetForm() {
        editingId = null;
        $('#po-varcost-form')[0].reset();
        $('#po_year').val(new Date().getFullYear());
        $('#po-varcost-modal-title').text('Tambah Data PO Varcost');
        $('#po-varcost-errors').addClass('d-none').empty();
    }

    $('#add-po-varcost').on('click', function () {
        resetForm();
        modal.show();
    });

    $('#po-varcost-table').on('click', '.edit-po', function () {
        const row = table.row($(this).closest('tr')).data();
        editingId = row.id;
        $('#po_number').val(row.po_number);
        $('#expense_type').val(row.expense_type);
        $('#description').val(row.description);
        $('#gr_status').val(row.gr_status);
        $('#po_year').val(row.po_year);
        const date = row.delivery_date && row.delivery_date !== '-' ? row.delivery_date.split('/').reverse().join('-') : '';
        $('#delivery_date').val(date);
        $('#po-varcost-modal-title').text('Edit Data PO Varcost');
        $('#po-varcost-errors').addClass('d-none').empty();
        modal.show();
    });

    $('#po-varcost-table').on('click', '.delete-po', function () {
        const id = $(this).data('id');
        if (!window.confirm('Hapus data PO Varcost ini?')) return;
        fetch(`${@json(url('po-varcost'))}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        }).then(async response => {
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Gagal menghapus data.');
            table.ajax.reload(null, false);
        }).catch(error => window.alert(error.message));
    });

    $('#po-varcost-form').on('submit', function (event) {
        event.preventDefault();
        const form = this;
        const payload = Object.fromEntries(new FormData(form).entries());
        const url = editingId ? `${@json(url('po-varcost'))}/${editingId}` : @json(route('po-varcost.store'));
        fetch(url, {
            method: editingId ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(async response => {
            const body = await response.json();
            if (!response.ok) {
                const messages = Object.values(body.errors || {}).flat();
                throw new Error(messages.join('<br>') || body.message || 'Gagal menyimpan data.');
            }
            modal.hide();
            table.ajax.reload(null, false);
        }).catch(error => $('#po-varcost-errors').removeClass('d-none').html(error.message));
    });
});
</script>
@endpush
