@extends('layouts.app')

@section('title', 'Dashboard — SIMAWAR')

@section('content')
    <div class="d-flex justify-content-between align-items-end mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 mb-1">Dashboard PnL Site</h1>
            <p class="text-body-secondary small mb-0" id="period-caption">Memuat periode...</p>
        </div>
        <div style="min-width: 200px;" data-simaster-filter-panel="Filter Periode Dashboard">
            <label for="period-select" class="form-label small mb-1">Periode (bulan)</label>
            <select id="period-select" class="form-select form-select-sm" disabled>
                <option>Memuat...</option>
            </select>
        </div>
    </div>

    <div class="row g-3">
        {{-- Level 1: donut distribusi status --}}
        <div class="col-lg-5">
            <div class="card h-100" id="card-donut">
                <div class="card-header bg-white">Distribusi Status Site</div>
                <div class="card-body">
                    <div id="status-donut" style="min-height: 340px;"></div>
                </div>
            </div>
        </div>

        {{-- Ringkasan keuangan periode terpilih --}}
        <div class="col-lg-7">
            <div class="card h-100" id="card-fin">
                <div class="card-header bg-white">Ringkasan Keuangan <span id="fin-period" class="text-body-secondary"></span></div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-label">Total Revenue</div>
                                <div class="fs-5 stat-number" id="stat-revenue">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-label">Total Cost</div>
                                <div class="fs-5 stat-number" id="stat-cost">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-label">Total PnL</div>
                                <div class="fs-5 stat-number stat-accent" id="stat-pnl">—</div>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 text-center mt-1">
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-label">Site Profit</div>
                                <div class="fs-5 stat-number stat-profit" id="stat-profit">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-label">Site Loss</div>
                                <div class="fs-5 stat-number stat-loss" id="stat-loss">—</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-label">Tidak Aktif</div>
                                <div class="fs-5 stat-number stat-inactive" id="stat-inactive">—</div>
                            </div>
                        </div>
                    </div>
                    <div class="small text-body-secondary mt-3" id="anomaly-note"></div>
                    <div class="small text-body-secondary mt-1">
                        Total site terdaftar: <span id="stat-total" class="fw-semibold">—</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Level 2: drawer samping (bukan modal fullscreen) — chart tetap terlihat --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="siteDrawer" style="width: 640px; max-width: 90vw;" aria-labelledby="drawer-title">
        <div class="offcanvas-header border-bottom pb-2 flex-column align-items-stretch">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="offcanvas-title" id="drawer-title">Daftar Site</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
            </div>
            <div class="position-relative">
                <input type="text" class="form-control form-control-sm" id="drawer-search"
                       placeholder="Cari Site ID atau Nama Site..." autocomplete="off">
                <button type="button" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y border-0 text-body-secondary d-none" id="drawer-search-clear" style="margin-right: 4px;" title="Hapus pencarian">&times;</button>
            </div>
        </div>
        <div class="offcanvas-body d-flex flex-column pt-2">
            <div id="drawer-list" class="flex-grow-1 overflow-auto"></div>
            <div id="drawer-pagination" class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top"></div>
        </div>
    </div>

    {{-- Level 3: modal detail site (histori bulanan + badge) --}}
    <div class="modal fade" id="siteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span id="site-modal-title">Detail Site</span>
                        <span id="site-modal-region" class="text-body-secondary fw-normal small"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3 text-center" id="site-totals"></div>

                    <h6 class="mb-2">Tren PnL Bulanan</h6>
                    <div id="site-trend-chart" style="min-height: 260px;"></div>

                    <h6 class="mt-3 mb-2">Status per Bulan</h6>
                    <div id="month-strip" class="d-flex flex-wrap gap-1 mb-2"></div>
                    <div class="small text-body-secondary mb-3" id="month-strip-legend">
                        <span class="month-chip chip-profit">&nbsp;&nbsp;</span> Profit
                        <span class="month-chip chip-loss ms-2">&nbsp;&nbsp;</span> Loss
                        <span class="month-chip chip-inactive ms-2">&nbsp;&nbsp;</span> Tidak Aktif (tanpa data)
                        <span class="month-chip chip-anomaly ms-2">&nbsp;&nbsp;</span> Anomali (revenue tidak dapat dipercaya)
                    </div>

                    <h6 class="mb-2">Histori Lengkap</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Bulan</th>
                                    <th class="text-end">Revenue</th>
                                    <th class="text-end">Cost</th>
                                    <th class="text-end">Profit/Loss</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="site-history-body"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="metricDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detail-modal-title">Detail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody id="detail-modal-body"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
$(function () {
    const palette = window.SimasterChartPalette;

    // ====== Palet status (dipertahankan konsisten di seluruh modul) ======
    const COLORS = {
        profit: palette.financial.profit,
        loss: palette.financial.loss,
        inactive: palette.financial.inactive,
        anomaly: palette.status.warning,
    };
    const STATUS_DEFS = [
        { key: 'Profit', label: 'Profit', color: COLORS.profit },
        { key: 'Loss', label: 'Loss', color: COLORS.loss },
        { key: 'TidakAktif', label: 'Tidak Aktif', color: COLORS.inactive },
    ];

    const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const fmt = new Intl.NumberFormat('id-ID');

    const periodLabel = (b, t) => b === 0 ? `Semua bulan ${t}` : `${BULAN[b - 1]} ${t}`;
    const money = (v) => (v === null || v === undefined) ? '—' : fmt.format(Math.round(v));
    const esc = (v) => $('<div>').text(v === null || v === undefined || v === '' ? '—' : String(v)).html();

    let current = { bulan: null, tahun: null };
    let drawerState = { status: null, page: 1, lastPage: 1 };
    let donut = null;
    let trendChart = null;

    // Cache klien hasil summary per periode (`${tahun}-${bulan}` -> data),
    // diisi saat load & prefetch background, supaya pergantian periode
    // berikutnya render instan tanpa menunggu request.
    const summaryCache = new Map();
    let summaryAbort = null;

    // Cache klien daftar site drawer per status+periode+halaman, diisi saat
    // open & warming background, supaya klik donut / pindah halaman instan.
    const drawerCache = new Map();
    let drawerAbort = null;

    const drawerEl = document.getElementById('siteDrawer');
    const drawer = new bootstrap.Offcanvas(drawerEl);
    const siteModal = new bootstrap.Modal('#siteModal');

    // ======================= Level 1: donut + ringkasan =======================

    async function fetchJson(url, signal) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }, signal });
        if (!res.ok) throw new Error(`Gagal memuat ${url} (HTTP ${res.status})`);
        return res.json();
    }

    function renderDonut(summary) {
        const values = [summary.profit, summary.loss, summary.inactive];

        if (!donut) {
            donut = new ApexCharts(document.querySelector('#status-donut'), {
                chart: {
                    type: 'donut',
                    height: 340,
                    fontFamily: 'Inter, sans-serif',
                    animations: { speed: 350 },
                    events: {
                        // Klik segmen -> buka drawer daftar site status tsb.
                        dataPointSelection: (event, chartContext, config) => {
                            const def = STATUS_DEFS[config.dataPointIndex];
                            if (def) openDrawer(def.key);
                        },
                    },
                },
                series: values,
                labels: STATUS_DEFS.map((s) => s.label),
                colors: STATUS_DEFS.map((s) => s.color),
                legend: { position: 'bottom' },
                dataLabels: {
                    style: { fontSize: '16px', fontWeight: 600 },
                    formatter: (val, { seriesIndex }) =>
                        `${fmt.format(values[seriesIndex])} (${val.toFixed(1)}%)`,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '58%',
                            labels: {
                                show: true,
                                value: { fontWeight: 600 },
                                total: {
                                    show: true,
                                    label: 'Total Site',
                                    fontWeight: 600,
                                    formatter: () => fmt.format(summary.total_sites),
                                },
                            },
                        },
                    },
                },
                tooltip: {
                    y: {
                        formatter: (value, options) => {
                            const percentage = window.SimasterChartMetrics.format(
                                window.SimasterChartMetrics.apex(value, options)
                            );

                            return `${fmt.format(value)} site (${percentage} dari total)`;
                        },
                    },
                },
            });
            donut.render();
        } else {
            donut.updateOptions({
                series: values,
                dataLabels: {
                    style: { fontSize: '16px', fontWeight: 600 },
                    formatter: (val, { seriesIndex }) =>
                        `${fmt.format(values[seriesIndex])} (${val.toFixed(1)}%)`,
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                total: { formatter: () => fmt.format(summary.total_sites) },
                            },
                        },
                    },
                },
            });
        }
    }

    function renderSummary(summary) {
        $('#period-caption').text(
            `Periode ${periodLabel(summary.bulan, summary.tahun)}`
        );
        $('#fin-period').text(`(${periodLabel(summary.bulan, summary.tahun)})`);
        $('#stat-revenue').text(money(summary.total_revenue));
        $('#stat-cost').text(money(summary.total_cost));
        $('#stat-pnl').text(money(summary.total_profit_loss));
        $('#stat-profit').text(fmt.format(summary.profit));
        $('#stat-loss').text(fmt.format(summary.loss));
        $('#stat-inactive').text(fmt.format(summary.inactive));
        $('#stat-total').text(fmt.format(summary.total_sites));
        $('#anomaly-note').html(
            summary.excluded_anomaly_rows > 0
                ? `⚠ Total keuangan mengecualikan <b>${summary.excluded_anomaly_rows} baris anomali</b> (revenue = INT32 max).`
                : ''
        );
    }

    async function loadSummary() {
        const key = `${current.tahun}-${current.bulan}`;
        const cached = summaryCache.get(key);

        // Sudah ada di cache klien (load/prefetch sebelumnya): render instan.
        if (cached) {
            renderDonut(cached);
            renderSummary(cached);
            warmDrawerFirstPages();
            return;
        }

        // Batalkan request periode lama yang masih berjalan (anti-race).
        if (summaryAbort) summaryAbort.abort();
        summaryAbort = new AbortController();

        // Feedback instan: redupkan card selama fetch berjalan.
        setSummaryLoading(true);
        try {
            const { data } = await fetchJson(
                `/api/dashboard/pnl-summary?bulan=${current.bulan}&tahun=${current.tahun}`,
                summaryAbort.signal
            );
            summaryCache.set(key, data);
            renderDonut(data);
            renderSummary(data);
            warmDrawerFirstPages();
        } catch (err) {
            if (err.name !== 'AbortError') $('#period-caption').text(err.message);
        } finally {
            setSummaryLoading(false);
        }
    }

    function setSummaryLoading(on) {
        $('#card-donut, #card-fin').toggleClass('is-loading', on);
    }

    // Prefetch background semua periode di dropdown ke cache klien,
    // sehingga pergantian periode setelah load awal terasa instan.
    function prefetchPeriods() {
        const values = $('#period-select option').map(function () { return this.value; }).get();

        const run = async () => {
            for (const val of values) {
                const [tahun, bulan] = val.split('-').map(Number);
                const key = `${tahun}-${bulan}`;
                if (summaryCache.has(key)) continue;
                try {
                    const { data } = await fetchJson(`/api/dashboard/pnl-summary?bulan=${bulan}&tahun=${tahun}`);
                    summaryCache.set(key, data);
                } catch (e) { /* prefetch gagal tidak kritis; loadSummary tetap menangani */ }
            }
        };

        if ('requestIdleCallback' in window) requestIdleCallback(run, { timeout: 4000 });
        else setTimeout(run, 1200);
    }

    async function initPeriodSelect() {
        const { data } = await fetchJson('/api/dashboard/periods');
        const $select = $('#period-select').empty().prop('disabled', false);

        data.forEach((p) => {
            $select.append($('<option>').val(`${p.tahun}-${p.bulan}`).text(periodLabel(p.bulan, p.tahun)));
        });

        // Default: periode terbaru (urutan pertama dari endpoint).
        current = { bulan: data[0].bulan, tahun: data[0].tahun };
        $select.val(`${data[0].tahun}-${data[0].bulan}`);

        $select.on('change', function () {
            const [tahun, bulan] = this.value.split('-').map(Number);
            current = { bulan, tahun };
            drawer.hide();
            loadSummary();
        });
    }

    // ======================= Level 2: drawer daftar site =======================

    const drawerKey = (statusKey, bulan, tahun, page) => `${statusKey}|${tahun}-${bulan}|p${page}`;
    const statusUrl = (statusKey, page, search = '') => {
        let url = `/api/dashboard/pnl-summary/${statusKey}?bulan=${current.bulan}&tahun=${current.tahun}&page=${page}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        return url;
    };

    // --- Search state & debounce ---
    let searchDebounceTimer = null;
    const $searchInput = $('#drawer-search');
    const $searchClear = $('#drawer-search-clear');

    $searchInput.on('input', function () {
        const val = this.value.trim();
        $searchClear.toggleClass('d-none', val === '');
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            // Reset ke halaman 1 saat search berubah
            if (drawerState.status) {
                openDrawer(drawerState.status, 1, val);
            }
        }, 300);
    });

    $searchClear.on('click', function () {
        $searchInput.val('').trigger('input').focus();
    });

    // Reset search saat drawer ditutup
    drawerEl.addEventListener('hidden.bs.offcanvas', () => {
        $searchInput.val('');
        $searchClear.addClass('d-none');
    });

    async function openDrawer(statusKey, page = 1, search = '') {
        drawerState = { status: statusKey, page, lastPage: drawerState.lastPage };
        const def = STATUS_DEFS.find((s) => s.key === statusKey);

        // Update search input tanpa trigger event (kalau dipanggil dari donut klik)
        if (search === '' && !$searchInput.is(':focus')) {
            $searchInput.val('');
            $searchClear.addClass('d-none');
        }

        const currentSearch = search || $searchInput.val().trim();

        $('#drawer-title').html(
            `Site ${esc(def.label)} <span class="badge text-bg-secondary" id="drawer-count"></span>` +
            `<div class="small text-body-secondary fw-normal">${periodLabel(current.bulan, current.tahun)}</div>`
        );
        drawer.show();

        // Jika ada search query, selalu fetch fresh (tidak cache)
        if (currentSearch !== '') {
            if (drawerAbort) drawerAbort.abort();
            drawerAbort = new AbortController();

            $('#drawer-list').text('Mencari...');
            $('#drawer-pagination').empty();
            setDrawerLoading(true);

            try {
                const { data } = await fetchJson(statusUrl(statusKey, page, currentSearch), drawerAbort.signal);
                if (drawerState.status !== statusKey) return;
                renderDrawerList(data);
            } catch (err) {
                if (err.name !== 'AbortError') $('#drawer-list').text(err.message);
            } finally {
                setDrawerLoading(false);
            }
            return;
        }

        // Tanpa search: gunakan cache biasa
        const key = drawerKey(statusKey, current.bulan, current.tahun, page);
        const cached = drawerCache.get(key);

        if (cached) {
            renderDrawerList(cached);
            prefetchDrawerNeighbors(statusKey, page);
            return;
        }

        if (drawerAbort) drawerAbort.abort();
        drawerAbort = new AbortController();

        $('#drawer-list').text('Memuat...');
        $('#drawer-pagination').empty();
        setDrawerLoading(true);

        try {
            const { data } = await fetchJson(statusUrl(statusKey, page), drawerAbort.signal);
            if (drawerState.status !== statusKey || drawerState.page !== page) return;

            drawerCache.set(key, data);
            renderDrawerList(data);
            prefetchDrawerNeighbors(statusKey, page);
        } catch (err) {
            if (err.name !== 'AbortError') $('#drawer-list').text(err.message);
        } finally {
            setDrawerLoading(false);
        }
    }

    function setDrawerLoading(on) {
        $('#drawer-list').toggleClass('is-loading', on);
    }

    function prefetchDrawerNeighbors(statusKey, page) {
        const lastPage = drawerState.lastPage;

        [page - 1, page + 1].forEach((p) => {
            if (p < 1 || p > lastPage) return;
            const key = drawerKey(statusKey, current.bulan, current.tahun, p);
            if (drawerCache.has(key)) return;

            fetchJson(statusUrl(statusKey, p))
                .then(({ data }) => drawerCache.set(key, data))
                .catch(() => { /* prefetch gagal tidak kritis */ });
        });
    }

    function warmDrawerFirstPages() {
        const { bulan, tahun } = current;

        const run = async () => {
            for (const def of STATUS_DEFS) {
                const key = drawerKey(def.key, bulan, tahun, 1);
                if (drawerCache.has(key)) continue;
                try {
                    const { data } = await fetchJson(
                        `/api/dashboard/pnl-summary/${def.key}?bulan=${bulan}&tahun=${tahun}&page=1`
                    );
                    drawerCache.set(key, data);
                } catch (e) { /* warming gagal tidak kritis; openDrawer tetap fetch */ }
            }
        };

        if ('requestIdleCallback' in window) requestIdleCallback(run, { timeout: 4000 });
        else setTimeout(run, 1500);
    }

    function renderDrawerList(data) {
        const sites = data.sites;
        drawerState.lastPage = sites.last_page;

        $('#drawer-count').text(fmt.format(sites.total));

        const $list = $('#drawer-list').empty();

        if (!sites.data.length) {
            const searchVal = $searchInput.val().trim();
            $list.append(
                searchVal
                    ? `<div class="text-body-secondary small py-3 text-center">Tidak ditemukan site dengan kata kunci "<b>${esc(searchVal)}</b>".</div>`
                    : '<div class="text-body-secondary small py-3 text-center">Tidak ada site pada status ini.</div>'
            );
        }

        sites.data.forEach((site) => {
            const regionLabel = site.region
                ? `${esc(site.region.kode)}${site.region.nama ? ' · ' + esc(site.region.nama) : ''}`
                : '—';

            let financialHtml;
            if (site.profit_loss === null) {
                financialHtml = '<span class="text-body-secondary small">Tidak ada data di periode ini</span>';
            } else {
                const pnlClass = site.profit_loss > 0 ? 'stat-profit' : 'stat-loss';
                financialHtml = `
                    <div class="fw-semibold ${pnlClass}">PnL: ${money(site.profit_loss)}</div>
                    <div class="small text-body-secondary">Rev: ${money(site.revenue)}</div>
                    <div class="small text-body-secondary">Cost: ${money(site.cost)}</div>`;
            }

            const $item = $(`
                <div class="drawer-item border rounded p-2 mb-2" role="button" data-site="${esc(site.site_id)}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1 me-3">
                            <div class="fw-semibold">${esc(site.site_id)}</div>
                            <div class="small">${esc(site.site_name)}</div>
                            <div class="small text-body-secondary">${regionLabel}</div>
                        </div>
                        <div class="text-end flex-shrink-0">${financialHtml}</div>
                    </div>
                </div>
            `);

            $item.on('click', () => openSiteDetail(site.site_id));
            $list.append($item);
        });

        renderDrawerPagination();
    }

    function renderDrawerPagination() {
        const { page, lastPage } = drawerState;
        const $pag = $('#drawer-pagination').empty();

        if (lastPage <= 1) {
            $pag.append(`<span class="small text-body-secondary">Halaman ${page} dari ${lastPage}</span><span></span>`);
            return;
        }

        const currentSearch = $searchInput.val().trim();
        $pag.append(`
            <button class="btn btn-outline-secondary btn-sm" id="drawer-prev" ${page <= 1 ? 'disabled' : ''}>‹ Sebelumnya</button>
            <span class="small text-body-secondary">Halaman ${page} / ${lastPage}</span>
            <button class="btn btn-outline-secondary btn-sm" id="drawer-next" ${page >= lastPage ? 'disabled' : ''}>Berikutnya ›</button>
        `);
        $('#drawer-prev').on('click', () => openDrawer(drawerState.status, page - 1, currentSearch));
        $('#drawer-next').on('click', () => openDrawer(drawerState.status, page + 1, currentSearch));
    }

    // ======================= Level 3: modal detail site =======================

    async function openSiteDetail(siteId) {
        $('#site-modal-title').text(`Memuat ${siteId}...`);
        $('#site-modal-region').text('');
        $('#site-totals').empty();
        $('#site-history-body').html('<tr><td colspan="5" class="text-center text-body-secondary">Memuat...</td></tr>');
        $('#month-strip').empty();
        siteModal.show();

        try {
            const { data } = await fetchJson(`/api/dashboard/site/${encodeURIComponent(siteId)}/detail`);
            renderSiteDetail(data);
        } catch (err) {
            $('#site-modal-title').text(err.message);
        }
    }

    function renderSiteDetail(site) {
        $('#site-modal-title').text(`${site.site_id} — ${site.site_name}`);
        $('#site-modal-region').text(site.region ? `Region ${site.region.kode}${site.region.nama ? ' · ' + site.region.nama : ''}` : '');

        $('#site-totals').html(`
            <div class="col-3"><div class="stat-card"><div class="stat-label">Total Revenue</div><div class="stat-number">${money(site.total_revenue)}</div></div></div>
            <div class="col-3"><div class="stat-card"><div class="stat-label">Total Cost</div><div class="stat-number">${money(site.total_cost)}</div></div></div>
            <div class="col-3"><div class="stat-card"><div class="stat-label">Total PnL</div><div class="stat-number stat-accent">${money(site.total_profit_loss)}</div></div></div>
            <div class="col-3"><div class="stat-card"><div class="stat-label">Bulan dgn Data</div><div class="stat-number">${site.months_with_data}${site.missing_months_count ? ` (+${site.missing_months_count} kosong)` : ''}</div></div></div>
        `);

        renderTrendChart(site.history);
        renderMonthStrip(site);
        renderHistoryTable(site.history);
    }

    function renderTrendChart(history) {
        const seriesData = history.map((h) => ({
            x: h.periode,
            y: h.profit_loss,
            // Warna per bulan: biru = Profit, oranye = Loss.
            fillColor: h.status === 'Profit' ? COLORS.profit : COLORS.loss,
        }));

        const options = {
            chart: { type: 'bar', height: 260, fontFamily: 'Inter, sans-serif', toolbar: { show: false } },
            series: [{ name: 'Profit/Loss', data: seriesData }],
            plotOptions: { bar: { borderRadius: 2, columnWidth: '60%' } },
            dataLabels: { enabled: false },
            xaxis: { labels: { rotate: -45, style: { fontSize: '15px', fontWeight: 400 } } },
            yaxis: { labels: { formatter: (v) => fmt.format(v) } },
            tooltip: {
                y: {
                    formatter: (value, options) => {
                        const percentage = window.SimasterChartMetrics.format(
                            window.SimasterChartMetrics.apex(value, options)
                        );

                        return `${fmt.format(value)} (${percentage} dari total nilai)`;
                    },
                },
            },
        };

        if (!trendChart) {
            trendChart = new ApexCharts(document.querySelector('#site-trend-chart'), options);
            trendChart.render();
        } else {
            trendChart.updateOptions(options, true, true);
        }
    }

    function renderMonthStrip(site) {
        // Gabungkan bulan punya data + bulan kosong, urut kronologis,
        // lalu render chip berwarna sesuai status per bulan.
        const chips = [
            ...site.history.map((h) => ({
                bulan: h.bulan, tahun: h.tahun, label: h.periode,
                kind: h.status === 'Profit' ? 'profit' : 'loss',
                anomaly: h.is_anomaly,
            })),
            ...site.missing_months.map((m) => ({
                bulan: m.bulan, tahun: m.tahun, label: m.periode,
                kind: 'inactive', anomaly: false,
            })),
        ].sort((a, b) => a.tahun - b.tahun || a.bulan - b.bulan);

        const $strip = $('#month-strip').empty();

        chips.forEach((c) => {
            const title = c.kind === 'inactive'
                ? `${c.label}: Tidak Aktif (tanpa data)`
                : `${c.label}: ${c.kind === 'profit' ? 'Profit' : 'Loss'}${c.anomaly ? ' — ANOMALI, revenue tidak dapat dipercaya' : ''}`;

            const $chip = $(`<span class="month-chip chip-${c.kind}${c.anomaly ? ' chip-anomaly' : ''}" title="${esc(title)}">${esc(c.label)}${c.anomaly ? ' ⚠' : ''}</span>`);
            $strip.append($chip);
        });
    }

    function renderHistoryTable(history) {
        const $body = $('#site-history-body').empty();

        history.forEach((h) => {
            const statusBadge = h.status === 'Profit'
                ? '<span class="badge status-badge badge-profit">Profit</span>'
                : '<span class="badge status-badge badge-loss">Loss</span>';
            const anomalyBadge = h.is_anomaly
                ? ' <span class="badge status-badge badge-anomaly" title="Revenue bulan ini = INT32 max (diduga overflow di sumber data)">⚠ Anomali</span>'
                : '';
            const createDetailButton = (title, details, total) => {
                const $button = $('<button>', {
                    type: 'button',
                    class: 'btn btn-link btn-sm p-0 detail-value',
                    title: `Lihat detail ${title}`,
                }).text(`${money(total)} +`);

                $button.data({
                    title: `${title} Details — ${h.periode}`,
                    details,
                });

                return $button;
            };

            const $row = $(`
                <tr>
                    <td>${esc(h.periode)}</td>
                    <td class="text-end revenue-detail-cell"></td>
                    <td class="text-end cost-detail-cell"></td>
                    <td class="text-end fw-semibold">${money(h.profit_loss)}</td>
                    <td class="text-center">${statusBadge}${anomalyBadge}</td>
                </tr>
            `);

            $row.find('.revenue-detail-cell').append(createDetailButton('Revenue', h.revenue_details, h.revenue));
            $row.find('.cost-detail-cell').append(createDetailButton('Cost', h.cost_details, h.cost));
            $body.append($row);
        });
    }

    $(document).on('click', '.detail-value', function () {
        const details = $(this).data('details') || {};
        const rows = Object.entries(details).map(([label, value]) =>
            `<tr><th>${esc(label)}</th><td class="text-end">${value === null ? '—' : `Rp ${money(value)}`}</td></tr>`
        ).join('');
        $('#detail-modal-title').text($(this).data('title'));
        $('#detail-modal-body').html(rows || '<tr><td class="text-center">—</td></tr>');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('metricDetailModal')).show();
    });

    // ======================= Bootstrap awal =======================
    // Prefetch jalan paralel dengan load awal agar cache klien cepat hangat.
    initPeriodSelect().then(() => {
        loadSummary();
        prefetchPeriods();
    });
});
</script>
@endpush
