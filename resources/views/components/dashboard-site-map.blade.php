@php($siteMapConfig = config('site-map.pages', [])[request()->route()?->getName()] ?? null)
@if ($siteMapConfig)
    <link rel="stylesheet" href="{{ asset('vendor/simaster/leaflet-1.9.4.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard-site-map.css') }}?v={{ filemtime(public_path('css/dashboard-site-map.css')) }}">
    <section class="site-overview card my-4" data-dashboard-site-map
             data-url="{{ route('reports.site-map', $siteMapConfig['module']) }}"
             data-module="{{ $siteMapConfig['module'] }}" data-scope="{{ $siteMapConfig['scope'] ?? 'all' }}"
             aria-labelledby="site-overview-title">
        <div class="card-body">
            <header class="site-overview-header">
                <div><h2 id="site-overview-title">Peta sebaran site · {{ $siteMapConfig['title'] }}</h2><p data-map-legend>Warna titik mengikuti NOP · sorot titik untuk detail site</p></div>
                <div class="site-overview-actions">
                    <button type="button" data-map-areas aria-pressed="true">Area NOP</button>
                    <button type="button" data-map-fit>Tampilkan semua titik</button>
                </div>
            </header>
            <div class="site-overview-summary" aria-live="polite">
                <div><strong data-map-total>—</strong><span>{{ $siteMapConfig['module'] === 'infrastructure' ? 'Site unik' : 'Total site' }}</span></div>
                <div><strong data-map-mapped>—</strong><span>Terpetakan</span></div>
                <button type="button" data-map-missing-toggle aria-expanded="false" aria-controls="site-map-list"><strong data-map-missing>—</strong><span>Belum terpetakan · lihat daftar</span></button>
            </div>
            <div class="site-overview-nops" data-map-nops aria-label="Filter NOP pada peta"></div>
            <p class="site-overview-status" data-map-status role="status">Memuat lokasi site…</p>
            <div class="site-overview-map" data-map-canvas role="region" aria-label="Peta seluruh site pada modul terpilih" tabindex="0"></div>
            <p class="site-overview-note" data-map-note>Sorot atau ketuk titik untuk informasi · Ctrl + scroll untuk zoom · geser untuk memindahkan peta.</p>
            <p class="site-overview-area-note" data-map-population></p>
            <p class="site-overview-area-note">Area berwarna merangkum sebaran titik setiap NOP, berupa perkiraan cakupan titik, bukan batas administratif resmi.</p>
            <details id="site-map-list" class="site-overview-list" data-map-list>
                <summary>Daftar site dan alamat</summary>
                <div class="site-overview-list-controls">
                    <input type="search" data-map-search class="form-control form-control-sm" placeholder="Cari Site ID, nama, atau alamat" aria-label="Cari site pada peta">
                    <label><input type="checkbox" data-map-only-missing> Hanya belum terpetakan</label>
                </div>
                <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Site</th><th>NOP</th><th>Alamat</th><th>Lokasi</th></tr></thead><tbody data-map-rows></tbody></table></div>
                <div class="site-overview-pagination"><button type="button" data-map-prev>Sebelumnya</button><span data-map-page role="status"></span><button type="button" data-map-next>Berikutnya</button></div>
            </details>
            <noscript>Aktifkan JavaScript untuk menampilkan peta interaktif.</noscript>
        </div>
    </section>
    <script src="{{ asset('vendor/simaster/leaflet-1.9.4.js') }}"></script>
    <script src="{{ asset('js/dashboard-site-map.js') }}?v={{ filemtime(public_path('js/dashboard-site-map.js')) }}"></script>
@endif
