@extends('layouts.app')

@section('title', 'Listrik Inbuilding — SIMAWAR')

@section('content')
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Listrik Inbuilding</h1>
        </div>
        <div class="d-flex gap-2">
            @if (auth()->user()->hasRole('admin'))
                <button type="button" class="btn btn-brand btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalCreateInbuilding">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/></svg>
                    Tambah Data
                </button>
                <a href="{{ route('electricity.inbuilding.upload-tagihan-ibc') }}" class="btn btn-outline-brand btn-sm">Upload Excel</a>
            @endif
            <a href="{{ route('electricity.inbuilding.listrik-inbuilding.export-excel') }}" class="btn btn-outline-brand btn-sm d-flex align-items-center gap-1 text-nowrap">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg>
                Excel
            </a>
        </div>
    </div>

    {{-- Filter & Page Size Toolbar --}}
    <div class="card mb-3" data-simaster-filter-panel="Pengaturan Data Listrik Inbuilding">
        <div class="card-body py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <label for="page-size" class="form-label mb-0 small fw-semibold text-nowrap">Show:</label>
                <select id="page-size" class="form-select form-select-sm" style="width:auto">
                    @foreach ([10, 20, 40, 80, 100, 5000] as $size)
                        <option value="{{ $size }}" @selected($size === 10)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="text-body-secondary small">
                Total: <span id="total-rows-info" class="fw-semibold text-dark">-</span> data
            </div>
        </div>
    </div>

    {{-- Main DataTable --}}
    <div class="card">
        <div class="card-body">
            <table id="inbuilding-table" class="display align-middle text-nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>No</th>
                            @if (auth()->user()->hasRole('admin'))
                                <th class="text-center" style="min-width: 150px;">Aksi</th>
                            @endif
                            <th>Site ID</th>
                            <th>Site Name</th>
                            <th>Status</th>
                            <th>Nama BM</th>
                            <th>No NPWP</th>
                            <th>Alamat</th>
                            <th>Telkomsel / TP</th>
                            <th>Daya</th>
                            <th>Harga/kWh</th>
                            <th>Update By</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                </table>
        </div>
    </div>

    {{-- MODAL 1: LIHAT DETAIL READ-ONLY --}}
    <div class="modal fade" id="modalDetailInbuilding" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Rincian Listrik Inbuilding</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body" id="modalDetailContent">
                    <div class="text-center py-4 text-secondary">Memuat data detail...</div>
                </div>
                <div class="modal-footer">
                    @if (auth()->user()->hasRole('admin'))
                        <div class="me-auto d-flex gap-2">
                            <button type="button" class="btn btn-outline-warning btn-sm" id="btn-detail-edit">
                                Edit
                            </button>
                            <form method="POST" id="form-detail-delete" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    @endif
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-detail-grafik">
                        Grafik Tagihan
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 2: GRAFIK TAGIHAN BULANAN DENGAN YEAR SELECTOR & CLICKABLE DETAIL --}}
    <div class="modal fade" id="modalGrafikInbuilding" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0">Grafik Tagihan Bulanan Listrik Inbuilding</h5>
                        <p class="text-secondary small mb-0">
                            Site: <strong id="grafikSiteId" class="text-dark"></strong> (<span id="grafikSiteName"></span>)
                        </p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    {{-- Selector Tahun & Summary Stat Cards --}}
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <label for="grafikYearSelect" class="form-label mb-0 fw-semibold small">Pilih Tahun:</label>
                            <select id="grafikYearSelect" class="form-select form-select-sm" style="width: 120px;">
                                @foreach ($years as $yr)
                                    <option value="{{ $yr }}" @selected($loop->first)>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- 4 Stat Cards --}}
                    <div class="row g-2 mb-3 text-center">
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-label">Total Tagihan</div>
                                <div class="fs-6 stat-number text-primary" id="grafikStatTotal">-</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-label">Rata-Rata / Bulan</div>
                                <div class="fs-6 stat-number" id="grafikStatRata">-</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-label">Tagihan Tertinggi</div>
                                <div class="fs-6 stat-number text-danger" id="grafikStatMax">-</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-card">
                                <div class="stat-label">Bulan Terisi</div>
                                <div class="fs-6 stat-number text-success" id="grafikStatTerisi">-</div>
                            </div>
                        </div>
                    </div>

                    {{-- Container Chart --}}
                    <div class="border rounded p-3 bg-light-subtle">
                        <div id="inbuildingMonthlyChart" style="min-height: 320px;"></div>
                    </div>

                    {{-- Container Detail Bulan Terpilih --}}
                    <div id="grafikMonthDetailContainer" class="mt-3 d-none">
                        <h6 class="fw-bold mb-2">Detail Tagihan Bulan <span id="detailNamaBulan" class="text-primary"></span></h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Bulan</th>
                                        <th>Jumlah Tagihan</th>
                                        <th>Status</th>
                                        <th>Invoice</th>
                                        <th>Update By</th>
                                        <th>Tanggal Update</th>
                                    </tr>
                                </thead>
                                <tbody id="detailMonthRow"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasRole('admin'))
        {{-- MODAL 3: TAMBAH DATA --}}
        <div class="modal fade" id="modalCreateInbuilding" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('electricity.inbuilding.listrik-inbuilding.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Tambah Data Listrik Inbuilding</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Site ID <span class="text-danger">*</span></label>
                                    <input type="text" name="site_id" class="form-control form-control-sm" required placeholder="Contoh: BKS008">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Site Name</label>
                                    <input type="text" name="site_name" class="form-control form-control-sm" placeholder="Nama Site">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Status</label>
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="Active">Active</option>
                                        <option value="Non Active">Non Active</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nama BM (Building Management)</label>
                                    <input type="text" name="nama_bm" class="form-control form-control-sm" placeholder="Nama BM">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">No NPWP</label>
                                    <input type="text" name="no_npwp" class="form-control form-control-sm" placeholder="Nomor NPWP">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Telkomsel / TP</label>
                                    <input type="text" name="telkomsel_tp" class="form-control form-control-sm" placeholder="Telkomsel">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Daya (VA)</label>
                                    <input type="number" name="daya" class="form-control form-control-sm" placeholder="16000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Harga / kWh (Rp)</label>
                                    <input type="number" step="0.01" name="harga_per_kwh" class="form-control form-control-sm" placeholder="1444">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Alamat Lengkap</label>
                                    <textarea name="alamat" rows="2" class="form-control form-control-sm" placeholder="Alamat lengkap gedung/site"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-brand btn-sm">Simpan Data</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL 4: EDIT DATA --}}
        <div class="modal fade" id="modalEditInbuilding" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <form id="formEditInbuilding" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Data Listrik Inbuilding</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Site ID <span class="text-danger">*</span></label>
                                    <input type="text" name="site_id" id="edit_site_id" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Site Name</label>
                                    <input type="text" name="site_name" id="edit_site_name" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Status</label>
                                    <select name="status" id="edit_status" class="form-select form-select-sm">
                                        <option value="Active">Active</option>
                                        <option value="Non Active">Non Active</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nama BM (Building Management)</label>
                                    <input type="text" name="nama_bm" id="edit_nama_bm" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">No NPWP</label>
                                    <input type="text" name="no_npwp" id="edit_no_npwp" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Telkomsel / TP</label>
                                    <input type="text" name="telkomsel_tp" id="edit_telkomsel_tp" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Daya (VA)</label>
                                    <input type="number" name="daya" id="edit_daya" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Harga / kWh (Rp)</label>
                                    <input type="number" step="0.01" name="harga_per_kwh" id="edit_harga_per_kwh" class="form-control form-control-sm">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Alamat Lengkap</label>
                                    <textarea name="alamat" id="edit_alamat" rows="2" class="form-control form-control-sm"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-brand btn-sm">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .detail-item {
        background: var(--neutral-50);
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
    }
    .detail-item .label {
        font-size: 0.72rem;
        text-transform: uppercase;
        color: var(--text-muted);
        font-weight: 600;
    }
    .detail-item .val {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-main);
        margin-top: 2px;
    }
    #inbuilding-table tbody tr { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
$(function () {
    const isAdmin = @json(auth()->user()->hasRole('admin'));
    const dataUrl = @json(route('electricity.inbuilding.listrik-inbuilding.data'));
    const updateBaseUrl = @json(url('electricity/inbuilding/listrik-inbuilding'));
    const palette = window.SimasterChartPalette;

    let columns = [
        { data: 'DT_RowIndex', name: 'no', orderable: false, searchable: false },
    ];

    if (isAdmin) {
        columns.push({ data: 'aksi', name: 'aksi', orderable: false, searchable: false });
    }

    columns.push(
        { data: 'site_id', name: 'site_id' },
        { data: 'site_name', name: 'site_name' },
        { data: 'status', name: 'status', className: 'text-center' },
        { data: 'nama_bm', name: 'nama_bm' },
        { data: 'no_npwp', name: 'no_npwp' },
        { data: 'alamat', name: 'alamat' },
        { data: 'telkomsel_tp', name: 'telkomsel_tp' },
        { data: 'daya', name: 'daya', className: 'text-end' },
        { data: 'harga_per_kwh', name: 'harga_per_kwh', className: 'text-end' },
        { data: 'update_by', name: 'update_by' },
        { data: 'tanggal', name: 'tanggal', className: 'text-center' }
    );

    const table = new DataTable('#inbuilding-table', {
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: dataUrl,
        columns: columns,
        order: [],
        pageLength: 10,
        drawCallback: function (settings) {
            $('#total-rows-info').text(settings.fnRecordsTotal());
        }
    });

    $('#page-size').on('change', function () {
        table.page.len(parseInt($(this).val())).draw();
    });

    function esc(v) { return $('<div>').text(v == null || v === '' ? '-' : String(v)).html(); }

    // ==========================================
    // 1. LIHAT DETAIL (MODAL POPUP READ-ONLY)
    // ==========================================
    const modalDetail = new bootstrap.Modal('#modalDetailInbuilding');

    let selectedDetail = null;

    function showDetail(id) {
        $('#modalDetailContent').html('<div class="text-center py-4 text-secondary">Memuat data detail...</div>');
        modalDetail.show();

        $.ajax({
            url: updateBaseUrl + '/' + id,
            method: 'GET',
            success: function (d) {
                const html = `
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="label">Site ID</div>
                        <div class="val text-primary">${esc(d.site_id)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Site Name</div>
                        <div class="val">${esc(d.site_name)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Status</div>
                        <div class="val">${esc(d.status)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Nama BM</div>
                        <div class="val">${esc(d.nama_bm)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">No NPWP</div>
                        <div class="val">${esc(d.no_npwp)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Telkomsel / TP</div>
                        <div class="val">${esc(d.telkomsel_tp)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Daya</div>
                        <div class="val">${esc(d.daya)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Harga / kWh</div>
                        <div class="val">${esc(d.harga_per_kwh)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Update By</div>
                        <div class="val">${esc(d.update_by)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="label">Tanggal</div>
                        <div class="val">${esc(d.tanggal)}</div>
                    </div>
                    <div class="detail-item" style="grid-column: span 2;">
                        <div class="label">Alamat Lengkap</div>
                        <div class="val fw-normal">${esc(d.alamat)}</div>
                    </div>
                </div>`;
                $('#modalDetailContent').html(html);
            },
            error: function () {
                $('#modalDetailContent').html('<div class="alert alert-danger mb-0">Gagal memuat rincian data.</div>');
            }
        });
    }

    @if (auth()->user()->hasRole('admin'))
        $('#btn-detail-edit').on('click', function () {
            if (!selectedDetail) return;
            modalDetail.hide();
            $('.btn-edit-inbuilding[data-id="' + selectedDetail.id + '"]').trigger('click');
        });

        $('#form-detail-delete').on('submit', function (event) {
            if (!selectedDetail) {
                event.preventDefault();
                return;
            }
            this.action = updateBaseUrl + '/' + selectedDetail.id;
        });
    @endif

    $('#inbuilding-table tbody').on('click', 'tr', function (event) {
        if ($(event.target).closest('button, a, form').length) return;
        const row = table.row(this).data();
        if (!row || !row.id) return;
        selectedDetail = row;
        showDetail(row.id);
    });

    // ==========================================
    // 2. GRAFIK TAGIHAN (MONTHLY CHART WITH CLICKABLE DETAIL)
    // ==========================================
    const modalGrafik = new bootstrap.Modal('#modalGrafikInbuilding');
    let currentGrafikId = null;
    let chartInstance = null;
    let currentChartDetails = [];

    function openGrafik(id, siteId, siteName) {
        currentGrafikId = id;

        $('#grafikSiteId').text(siteId);
        $('#grafikSiteName').text(siteName);
        $('#grafikMonthDetailContainer').addClass('d-none');

        modalGrafik.show();
        loadGrafikData(currentGrafikId, $('#grafikYearSelect').val());
    }

    $('#btn-detail-grafik').on('click', function () {
        if (!selectedDetail) return;
        modalDetail.hide();
        openGrafik(selectedDetail.id, selectedDetail.site_id, selectedDetail.site_name || '-');
    });

    $('#grafikYearSelect').on('change', function () {
        if (currentGrafikId) {
            $('#grafikMonthDetailContainer').addClass('d-none');
            loadGrafikData(currentGrafikId, $(this).val());
        }
    });

    function loadGrafikData(id, year) {
        $.ajax({
            url: updateBaseUrl + '/' + id + '/chart-data?tahun=' + year,
            method: 'GET',
            success: function (res) {
                $('#grafikStatTotal').text(res.summary.total);
                $('#grafikStatRata').text(res.summary.rata_rata);
                $('#grafikStatMax').text(res.summary.tertinggi);
                $('#grafikStatTerisi').text(res.summary.bulan_terisi);

                currentChartDetails = res.details || [];
                renderMonthlyApexChart(res.labels, res.values);
            },
            error: function () {
                console.error('Gagal memuat data grafik tagihan.');
            }
        });
    }

    function renderMonthlyApexChart(labels, values) {
        const isDark = (document.documentElement.getAttribute('data-bs-theme') === 'dark');

        const options = {
            series: [{
                name: 'Jumlah Tagihan',
                data: values
            }],
            chart: {
                type: 'bar',
                height: 320,
                toolbar: { show: false },
                events: {
                    click: function (event, chartContext, config) {
                        const selectedIdx = config.dataPointIndex;
                        if (currentChartDetails[selectedIdx]) {
                            showMonthDetailRow(currentChartDetails[selectedIdx]);
                        }
                    }
                }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '55%',
                    distributed: true,
                    dataLabels: { position: 'top' }
                }
            },
            colors: palette.distinctColors(labels.length),
            dataLabels: {
                enabled: false
            },
            xaxis: {
                categories: labels,
                labels: { style: { colors: isDark ? '#94A3B8' : '#64748B' } }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
                    },
                    style: { colors: isDark ? '#94A3B8' : '#64748B' }
                }
            },
            grid: {
                borderColor: isDark ? '#1F2937' : '#F1F5F9'
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light',
                y: {
                    formatter: function (val, options) {
                        const percentage = window.SimasterChartMetrics.format(
                            window.SimasterChartMetrics.apex(val, options)
                        );

                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
                            + ' (' + percentage + ' dari total tagihan)';
                    }
                }
            }
        };

        if (chartInstance) {
            chartInstance.destroy();
        }
        chartInstance = new ApexCharts(document.querySelector("#inbuildingMonthlyChart"), options);
        chartInstance.render();
    }

    function showMonthDetailRow(detail) {
        $('#detailNamaBulan').text(detail.nama_bulan);
        const html = `
        <tr>
            <td><strong>${esc(detail.nama_bulan)}</strong></td>
            <td class="text-primary fw-bold">${esc(detail.nominal_format)}</td>
            <td><span class="badge ${!String(detail.status || '').toLowerCase().includes('inactive') && !String(detail.status || '').toLowerCase().includes('tidak') && (String(detail.status || '').toLowerCase().includes('active') || String(detail.status || '').toLowerCase().includes('aktif') || detail.status === 'Done') ? 'badge-profit' : 'badge-loss'}">${esc(detail.status)}</span></td>
            <td>${esc(detail.invoice)}</td>
            <td>${esc(detail.update_by)}</td>
            <td>${esc(detail.tanggal)}</td>
        </tr>`;
        $('#detailMonthRow').html(html);
        $('#grafikMonthDetailContainer').removeClass('d-none');
    }

    // ==========================================
    // 3. EDIT DATA (MODAL EDIT)
    // ==========================================
    const modalEdit = new bootstrap.Modal('#modalEditInbuilding');

    $(document).on('click', '.btn-edit-inbuilding', function () {
        const id = $(this).data('id');
        $.ajax({
            url: updateBaseUrl + '/' + id,
            method: 'GET',
            success: function (d) {
                $('#formEditInbuilding').attr('action', updateBaseUrl + '/' + id);
                $('#edit_site_id').val(d.site_id);
                $('#edit_site_name').val(d.site_name === '-' ? '' : d.site_name);
                $('#edit_status').val(d.status);
                $('#edit_nama_bm').val(d.nama_bm === '-' ? '' : d.nama_bm);
                $('#edit_no_npwp').val(d.no_npwp === '-' ? '' : d.no_npwp);
                $('#edit_alamat').val(d.alamat === '-' ? '' : d.alamat);
                $('#edit_telkomsel_tp').val(d.telkomsel_tp === '-' ? '' : d.telkomsel_tp);
                $('#edit_daya').val(d.raw_daya);
                $('#edit_harga_per_kwh').val(d.raw_harga);
                modalEdit.show();
            },
            error: function () {
                alert('Gagal memuat data untuk diedit.');
            }
        });
    });
});
</script>
@endpush
