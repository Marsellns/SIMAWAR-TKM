@extends('layouts.app')

@section('title', 'PO HQ — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h1 class="h4 mb-0">PO HQ</h1>
        <div class="d-flex flex-wrap gap-2">
            @include('operations.actions', ['dataset' => 'po-hq', 'entryLabel' => 'Upload Excel'])
        @role('admin')
            <a href="{{ route('po-hq.create') }}" class="btn btn-brand btn-sm">+ Tambah PO</a>
        @endrole
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="po-table" class="display align-middle text-nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        @role('admin')
                            <th>Aksi</th>
                        @endrole
                        <th>PO Number</th>
                        <th>Agreement Number</th>
                        <th>Vendor Name</th>
                        <th>Description</th>
                        <th>Capex/Opex</th>
                        <th>Status</th>
                        <th>Location</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    {{-- Modal drilldown: muncul saat baris tabel diklik (bukan halaman terpisah) --}}
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail PO <span id="detail-po-number"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered align-middle mb-0">
                        <tbody id="detail-body">
                            <tr><td colspan="2" class="text-center text-body-secondary">Memuat...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(function () {
    const isAdmin = {{ auth()->user()->hasRole('admin') ? 'true' : 'false' }};
    const detailBaseUrl = @json(url('po-hq'));

    // Susunan kolom mengikuti header (No, [Aksi], PO Number, ...).
    const columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];
    if (isAdmin) {
        columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false });
    }
    columns.push(
        { data: 'po_number', name: 'po_number' },
        { data: 'agreement_number', name: 'agreement_number' },
        { data: 'vendor_name', name: 'vendor_name' },
        { data: 'description', name: 'description' },
        { data: 'expense_type', name: 'expense_type' },
        { data: 'status', name: 'status' },
        { data: 'location', name: 'location' },
    );

    const table = new DataTable('#po-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: { url: @json(route('po-hq.data')) },
        columns: columns,
        order: [], // urutan default diatur server (latest id)
    });

    const detailModal = new bootstrap.Modal('#detailModal');

    // Escape helper agar nilai dari server aman dirender ke HTML modal.
    function esc(value) {
        return $('<div>').text(value === null || value === undefined || value === '' ? '-' : String(value)).html();
    }

    // Klik tombol Edit/Delete di kolom Aksi TIDAK boleh memicu modal detail.
    // stopPropagation di sini + guard closest('.aksi-cell') pada handler baris.
    $('#po-table tbody').on('click', '.aksi-cell button, .aksi-cell a', function (e) {
        e.stopPropagation();
    });

    // Klik baris -> fetch detail via AJAX -> isi modal -> tampilkan.
    $('#po-table tbody').on('click', 'tr', function (e) {
        if ($(e.target).closest('.aksi-cell').length > 0) {
            return;
        }

        const row = table.row(this).data();
        if (!row || !row.id) return;

        $('#detail-body').html('<tr><td colspan="2" class="text-center text-body-secondary">Memuat...</td></tr>');
        $('#detail-po-number').text('');
        detailModal.show(this);

        fetch(`${detailBaseUrl}/${row.id}`, { headers: { 'Accept': 'application/json' } })
            .then((res) => {
                if (!res.ok) throw new Error('Gagal memuat detail PO.');
                return res.json();
            })
            .then(({ data }) => {
                $('#detail-po-number').text(data.po_number);
                $('#detail-body').html(`
                    <tr><th style="width:220px">PO Number</th><td>${esc(data.po_number)}</td></tr>
                    <tr><th>Agreement Number</th><td>${esc(data.agreement_number)}</td></tr>
                    <tr><th>Vendor Name</th><td>${esc(data.vendor_name)}</td></tr>
                    <tr><th>Description</th><td>${esc(data.description)}</td></tr>
                    <tr><th>Capex/Opex</th><td>${esc(data.expense_type)}</td></tr>
                    <tr><th>Status</th><td>${esc(data.status)}</td></tr>
                    <tr><th>Location</th><td>${esc(data.location)}</td></tr>
                    <tr><th>Dibuat</th><td>${esc(data.created_at)}</td></tr>
                    <tr><th>Diperbarui</th><td>${esc(data.updated_at)}</td></tr>
                `);
            })
            .catch((err) => {
                $('#detail-body').html(`<tr><td colspan="2" class="text-danger">${esc(err.message)}</td></tr>`);
            });
    });
});
</script>
@endpush
