<div class="modal fade" id="feasibility-detail-modal" tabindex="-1" aria-labelledby="feasibility-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <header class="modal-header align-items-start gap-3">
                <div><h2 class="modal-title h5" id="feasibility-detail-title">Data Feasibility Site</h2><p id="feasibility-detail-count" class="feasibility-note mb-0 mt-1" role="status">Memuat data…</p></div>
                <a id="feasibility-download" class="btn btn-success btn-sm ms-auto text-nowrap d-inline-flex align-items-center gap-1 text-nowrap" href="{{ route('data-potensi.site-owner.export-excel') }}"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true" focusable="false"><path d="M.5 9.9a.5.5 0 0 1 .5.1v2.5A1.5 1.5 0 0 0 2.5 14h11a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 1 1 0v2.5a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 0 12.5V10a.5.5 0 0 1 .5-.1"/><path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/></svg> Excel</a>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup rincian"></button>
            </header>
            <div class="modal-body">
                <div id="feasibility-detail-layout" class="feasibility-detail-layout without-overview">
                    <aside class="feasibility-detail-sidebar" hidden>
                        @foreach (['owners' => ['Komposisi Kepemilikan Site', 'site owner', 'site_owner', 'mint'], 'classes' => ['Klasifikasi Site Class', 'site class', 'site_class', 'amber'], 'coverage' => ['Jenis Coverage Site', 'coverage', 'coverage_type', 'violet']] as $key => [$heading, $searchLabel, $field, $tone])
                            <section data-classification="{{ $key }}" hidden>
                                <h3 class="h6 fw-bold mb-2">{{ $heading }}</h3>
                                <label class="visually-hidden" for="feasibility-search-{{ $key }}">Cari {{ $searchLabel }}</label>
                                <input id="feasibility-search-{{ $key }}" class="feasibility-search" type="search" placeholder="Cari {{ $searchLabel }}…" aria-controls="feasibility-list-{{ $key }}">
                                <div class="feasibility-category-status" role="status" aria-live="polite">{{ count($classifications[$key]) }} kategori</div>
                                <div id="feasibility-list-{{ $key }}" class="feasibility-category-list" tabindex="0" role="region" aria-label="Daftar {{ $heading }}">
                                    <div class="feasibility-category-grid">
                                        @foreach ($classifications[$key] as $point)
                                            <button type="button" class="feasibility-category-card feasibility-tone-{{ $tone }}" data-category-name="{{ $point['name'] }}" data-category-field="{{ $field }}" aria-pressed="false">
                                                <span class="feasibility-category-name">{{ $point['name'] }}</span>
                                                <span class="feasibility-category-count">{{ number_format($point['y'], 0, ',', '.') }} <small>site</small></span>
                                                <span class="feasibility-category-meta">{{ $formatPercentage($point['y']) }}% dari total site</span>
                                                <span class="feasibility-category-progress" aria-hidden="true"><span style="--share:{{ round($percentage($point['y']), 4) }}%"></span></span>
                                            </button>
                                        @endforeach
                                    </div>
                                    <p class="feasibility-no-results feasibility-note" @if (count($classifications[$key])) hidden @endif>{{ count($classifications[$key]) ? 'Kategori tidak ditemukan.' : 'Belum ada data untuk ditampilkan.' }}</p>
                                </div>
                            </section>
                        @endforeach
                    </aside>
                    <section class="feasibility-detail-table" aria-label="Data rincian Feasibility Site">
                        <div class="feasibility-detail-controls">
                            <label class="visually-hidden" for="feasibility-detail-search">Cari Site ID atau Site Name</label>
                            <input id="feasibility-detail-search" type="search" class="feasibility-search" placeholder="Cari Site ID atau Site Name…">
                            <button id="feasibility-reset" type="button" class="btn btn-sm btn-outline-secondary text-nowrap">Tampilkan semua</button>
                        </div>
                        <div id="feasibility-active-filters" class="feasibility-active-filters" aria-label="Filter data aktif"></div>
                        <div id="feasibility-detail-error" class="alert alert-danger py-2" role="alert" hidden></div>
                        <p class="feasibility-note mb-2">Excel memuat seluruh baris yang cocok dengan filter dan pencarian.</p>
                        <table id="feasibility-detail-table" class="table table-hover align-middle" style="width:100%"><thead></thead><tbody></tbody></table>
                    </section>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function () {
    const modalElement = document.getElementById('feasibility-detail-modal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const search = document.getElementById('feasibility-detail-search');
    const download = document.getElementById('feasibility-download');
    const count = document.getElementById('feasibility-detail-count');
    const error = document.getElementById('feasibility-detail-error');
    const routes = {
        sites: { data: @json(route('data-potensi.site-owner.data')), export: @json(route('data-potensi.site-owner.export-excel')), detail: @json(url('data-potensi/site-owner')) },
        tower: { data: @json(route('data-potensi.asset-tower.data')), export: @json(route('data-potensi.asset-tower.export-excel')), detail: @json(url('data-potensi/asset-tower')) },
    };
    const fields = { site_owner: 'Site Owner', site_class: 'Site Class', coverage_type: 'Coverage Type', status_mla: 'Status', nop: 'NOP', tower_type: 'Tipe Tower' };
    const siteColumns = [['site_code', 'Site ID'], ['site_name', 'Site Name'], ['nop', 'NOP'], ['site_class', 'Site Class'], ['site_owner', 'Site Owner'], ['coverage_type', 'Coverage Type'], ['status_mla', 'Status MLA']];
    const towerColumns = [['site_id', 'Site ID'], ['site_name', 'Site Name'], ['tower_type', 'Tipe Tower'], ['site_company', 'Site Company'], ['owner', 'Owner'], ['ownership_status', 'Status Kepemilikan'], ['tower_height', 'Tinggi Tower']];
    let table = null;
    let dataset = 'sites';
    let filters = {};
    let searchTimer;
    const number = value => Number(value).toLocaleString('id-ID');

    function refreshDownload() {
        download.href = routes[dataset].export + '?' + new URLSearchParams({ ...filters, search: search.value.trim() });
    }
    function refreshFilters() {
        const chips = document.getElementById('feasibility-active-filters');
        chips.replaceChildren();
        Object.entries(filters).forEach(([key, value]) => {
            const chip = document.createElement('span');
            chip.textContent = `${fields[key] || key}: ${value}`;
            chips.append(chip);
        });
        modalElement.querySelectorAll('[data-category-field]').forEach(card => {
            card.setAttribute('aria-pressed', String(filters[card.dataset.categoryField] === card.dataset.categoryName));
        });
        refreshDownload();
    }
    function loadRows() {
        error.hidden = true;
        count.textContent = 'Memuat data…';
        refreshFilters();
        table.search(search.value.trim()).ajax.reload();
    }
    function createTable() {
        const columns = dataset === 'sites' ? siteColumns : towerColumns;
        const head = document.querySelector('#feasibility-detail-table thead');
        head.replaceChildren();
        const header = document.createElement('tr');
        columns.forEach(([, label]) => { const th = document.createElement('th'); th.textContent = label; header.append(th); });
        head.append(header);
        table = new DataTable('#feasibility-detail-table', {
            processing: true, serverSide: true, scrollX: true, pageLength: 10, lengthMenu: [10, 25, 50, 100], ordering: false,
            dom: 'rt<"d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3"lip>',
            ajax: {
                url: routes[dataset].data,
                data: params => Object.assign(params, filters),
                error: () => { error.textContent = 'Data gagal dimuat. Coba pilih kategori kembali atau tutup dan buka rincian.'; error.hidden = false; count.textContent = 'Gagal memuat data.'; },
            },
            columns: columns.map(([key]) => ({data: key, name: key, defaultContent: '—'})),
            language: {emptyTable: 'Belum ada data.', zeroRecords: 'Tidak ada site yang cocok.', processing: 'Memuat data…', lengthMenu: '_MENU_ baris', info: '_START_–_END_ dari _TOTAL_ baris', infoEmpty: '0 baris', infoFiltered: '', paginate: {previous: 'Sebelumnya', next: 'Berikutnya'}},
            createdRow: (row, data) => { row.tabIndex = 0; row.setAttribute('aria-label', `Lihat detail site ${data.site_code || data.site_id}`); },
        });
        table.on('draw.feasibility', () => {
            count.textContent = `${number(table.page.info().recordsDisplay)} ${dataset === 'tower' ? 'baris aset' : 'site'} sesuai pilihan`;
            refreshDownload();
        });
    }
    function open({dataset: nextDataset = 'sites', overview = null, title = 'Daftar Site', filters: nextFilters = {}} = {}) {
        clearTimeout(searchTimer);
        const changed = dataset !== nextDataset;
        if (table) {
            table.settings()[0].jqXHR?.abort();
            if (changed) { $('#feasibility-detail-table').off('.feasibility'); table.destroy(); table = null; document.querySelector('#feasibility-detail-table tbody').replaceChildren(); }
        }
        dataset = nextDataset;
        filters = {...nextFilters};
        search.value = '';
        error.hidden = true;
        count.textContent = 'Memuat data…';
        document.getElementById('feasibility-detail-title').textContent = title;
        document.querySelector('.feasibility-detail-sidebar').hidden = !overview;
        document.getElementById('feasibility-detail-layout').classList.toggle('without-overview', !overview);
        modalElement.querySelectorAll('[data-classification]').forEach(section => {
            section.hidden = section.dataset.classification !== overview;
            const input = section.querySelector('input');
            input.value = '';
            input.dispatchEvent(new Event('input'));
        });
        modal.show();
        if (!table) { createTable(); refreshFilters(); } else { loadRows(); }
    }
    window.SimasterFeasibilityDetails = {open};
    document.querySelectorAll('[data-feasibility-open]').forEach(button => button.addEventListener('click', () => {
        const key = button.dataset.feasibilityOpen;
        const names = {sites:'Seluruh Site', owners:'Komposisi Kepemilikan Site', classes:'Klasifikasi Site Class', coverage:'Jenis Coverage Site', tower:'Data Asset Tower'};
        open({dataset: key === 'tower' ? 'tower' : 'sites', overview: ['owners','classes','coverage'].includes(key) ? key : null, title:names[key]});
    }));
    document.querySelectorAll('[data-mla-status]').forEach(button => button.addEventListener('click', () => open({title:'Site berdasarkan Status MLA', filters:{status_mla:button.dataset.mlaStatus}})));
    modalElement.querySelectorAll('[data-category-field]').forEach(card => card.addEventListener('click', () => {
        filters = {[card.dataset.categoryField]:card.dataset.categoryName};
        loadRows();
    }));
    search.addEventListener('input', () => { clearTimeout(searchTimer); refreshDownload(); searchTimer = setTimeout(loadRows, 250); });
    document.getElementById('feasibility-reset').addEventListener('click', () => { clearTimeout(searchTimer); filters = {}; search.value = ''; loadRows(); });
    modalElement.addEventListener('shown.bs.modal', () => table?.columns.adjust());
    modalElement.addEventListener('hidden.bs.modal', () => clearTimeout(searchTimer));

    async function toggleRow(rowElement) {
        const row = table.row(rowElement);
        const data = row.data();
        if (!data?.id) return;
        if (row.child.isShown()) { row.child.hide(); return; }
        const detail = $('<table class="table table-sm mb-0 feasibility-row-detail"><tbody></tbody></table>');
        row.child(detail).show();
        detail.find('tbody').append($('<tr>').append($('<td>').text('Memuat detail…')));
        try {
            const response = await fetch(`${routes[dataset].detail}/${data.id}`, {headers:{Accept:'application/json'}});
            if (!response.ok) throw new Error('Detail gagal dimuat.');
            const {data:record} = await response.json();
            const labels = dataset === 'sites'
                ? {alamat:'Alamat', city:'City', nop:'NOP', site_class:'Site Class', coverage_type:'Coverage Type', status_mla:'Status MLA', site_owner:'Site Owner', pln_connection:'PLN Connection', capacity:'Capacity', id_pel:'ID Pelanggan', tower_height:'Tinggi Tower', tgl_update:'Tanggal Update'}
                : {ownership_status:'Status Kepemilikan', tower_type:'Tipe Tower', tower_height:'Tinggi Tower', building_height:'Tinggi Bangunan', note:'Catatan', update_by:'Update Oleh', tanggal_update:'Tanggal Update'};
            detail.find('tbody').empty();
            Object.entries(labels).forEach(([key,label]) => detail.find('tbody').append($('<tr>').append($('<th>').text(label), $('<td>').text(record[key] ?? '—'))));
        } catch (e) { detail.find('tbody').empty().append($('<tr>').append($('<td>').text(e.message))); }
    }
    $('#feasibility-detail-table tbody').on('click', 'tr', function () { toggleRow(this); }).on('keydown', 'tr', function (event) {
        if (event.target !== this || !['Enter',' '].includes(event.key)) return;
        event.preventDefault(); toggleRow(this);
    });
});
</script>
@endpush
