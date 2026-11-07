(function (global) {
    'use strict';
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const colors = { 'NOP KARAWANG': '#0caaa0', 'NOP BOGOR': '#e79812', 'NOP BEKASI': '#7c65c9', 'Tanpa NOP': '#64748b' };
    const color = name => colors[name] || '#3976b8';
    const number = value => Number(value || 0).toLocaleString('id-ID');
    function tooltip(site) {
        const fields = [{ label: 'NOP', value: site.nop }, { label: 'Kabupaten / Kota', value: site.city || '—' }, { label: 'Kecamatan', value: site.district || '—' }, ...(site.fields || [])];
        return `<div class="site-point-title">${escapeHtml(site.id)} · ${escapeHtml(site.name)}</div>${fields.map(field => `<div class="site-point-field"><span>${escapeHtml(field.label)}</span><b>${escapeHtml(field.value || '—')}</b></div>`).join('')}<div class="site-point-source">${escapeHtml(site.coordinate_source)}</div>`;
    }
    function hull(points) {
        const sorted = [...new Map(points.map(p => [`${p.lng},${p.lat}`, [p.lng, p.lat]])).values()].sort((a, b) => a[0] - b[0] || a[1] - b[1]);
        if (sorted.length < 3) return [];
        const cross = (o, a, b) => (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
        const chain = list => { const out = []; for (const p of list) { while (out.length > 1 && cross(out[out.length - 2], out[out.length - 1], p) <= 0) out.pop(); out.push(p); } return out.slice(0, -1); };
        return [...chain(sorted), ...chain([...sorted].reverse())].map(([lng, lat]) => [lat, lng]);
    }
    function labelOffsets(points) {
        const placed = [];
        return points.map(point => {
            for (let step = 0; step <= points.length * 2; step++) {
                const offset = Math.ceil(step / 2) * 24 * (step % 2 ? -1 : 1);
                const candidate = { x: point.x, y: point.y + offset };
                if (placed.some(other => Math.abs(other.x - candidate.x) < 180 && Math.abs(other.y - candidate.y) < 24)) continue;
                placed.push(candidate); return offset;
            }
            return 0;
        });
    }
    function create(root) {
        if (root.__siteMap) return root.__siteMap;
        const find = key => root.querySelector(`[data-map-${key}]`);
        const canvas = find('canvas');
        const L = global.L;
        if (!L) { find('status').textContent = 'Komponen peta gagal dimuat. Muat ulang halaman untuk mencoba kembali.'; return null; }
        const map = L.map(canvas, { preferCanvas: true, scrollWheelZoom: false, zoomControl: false }).setView([-6.65, 107.05], 9);
        L.control.zoom({ position: 'topright' }).addTo(map);
        const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener noreferrer">OpenStreetMap contributors</a>' }).addTo(map);
        let tileFailures = 0;
        tiles.on('tileerror', () => { if (++tileFailures === 5) find('status').textContent = 'Latar peta belum dapat dimuat. Titik dan daftar site tetap tersedia; periksa koneksi internet.'; });
        const markers = L.layerGroup().addTo(map), areas = L.layerGroup().addTo(map), labels = L.layerGroup().addTo(map);
        map.createPane('nopAreas'); map.getPane('nopAreas').style.zIndex = 350; map.getPane('nopAreas').style.pointerEvents = 'none';
        let sites = [], selectedNop = '', showAreas = true, page = 0, request, filters = {}, lastKey = '', loadedKey = '', timer, loadedData, equipmentSites;
        const markerBySite = new Map();
        let nopMarkers = [];
        function layoutLabels() {
            const visible = nopMarkers.filter(marker => marker.getElement?.());
            const offsets = labelOffsets(visible.map(marker => map.latLngToContainerPoint(marker.getLatLng())));
            visible.forEach((marker, index) => { marker.getElement().querySelector('span').style.transform = `translateY(${offsets[index]}px)`; });
        }
        map.on('zoomend', layoutLabels);
        function visibleSites() { return sites.filter(site => !selectedNop || site.nop === selectedNop); }
        function fit() {
            const points = visibleSites().filter(site => site.point).map(site => [site.point.lat, site.point.lng]);
            if (points.length) map.fitBounds(L.latLngBounds(points), { padding: [20, 20], maxZoom: 14 });
        }
        function renderPoints() {
            markers.clearLayers(); areas.clearLayers(); labels.clearLayers(); markerBySite.clear();
            nopMarkers = [];
            const visible = visibleSites(), located = visible.filter(site => site.point);
            const groups = new Map();
            for (const site of located) {
                const marker = L.circleMarker([site.point.lat, site.point.lng], { radius: 3.2, color: '#fff', weight: 1, fillColor: color(site.nop), fillOpacity: .9 }).addTo(markers);
                marker.bindTooltip(() => tooltip(site), { className: 'site-point-tooltip', direction: 'auto', sticky: true, offset: [9, -8], opacity: 1 });
                marker.on('click', () => marker.openTooltip());
                markerBySite.set(site.key || site.id, marker);
                if (!groups.has(site.nop)) groups.set(site.nop, []);
                groups.get(site.nop).push(site.point);
            }
            for (const [nop, points] of groups) {
                const outline = hull(points);
                if (outline.length > 2) L.polygon(outline, { pane: 'nopAreas', color: color(nop), weight: 1, fillOpacity: .08, interactive: false }).addTo(areas);
                const latitudes = points.map(p => p.lat).sort((a, b) => a - b), longitudes = points.map(p => p.lng).sort((a, b) => a - b);
                const center = [latitudes[Math.floor(points.length / 2)], longitudes[Math.floor(points.length / 2)]];
                nopMarkers.push(L.marker(center, { interactive: false, keyboard: false, icon: L.divIcon({ className: 'site-nop-label', html: `<span style="--nop-color:${color(nop)}">${escapeHtml(nop)}</span>`, iconSize: [180, 20], iconAnchor: [90, 10] }) }).addTo(labels));
            }
            if (!showAreas) { map.removeLayer(areas); map.removeLayer(labels); }
            layoutLabels();
            find('note').textContent = `${number(located.length)} titik ditampilkan dari ${number(visible.length)} site${selectedNop ? ' · ' + selectedNop : ''}. ${number(visible.length - located.length)} belum memiliki koordinat. Ctrl + scroll untuk zoom · geser peta · ketuk titik untuk detail.`;
            find('status').textContent = !sites.length ? 'Tidak ada site pada filter aktif.' : !located.length ? 'Belum ada koordinat terverifikasi untuk pilihan ini. Alamat tersedia dalam daftar site.' : '';
            find('nops').querySelectorAll('button').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.nop === selectedNop)));
            renderList();
        }
        function renderList() {
            const search = find('search').value.trim().toLocaleLowerCase('id');
            const rows = visibleSites().filter(site => (!find('only-missing').checked || !site.point) && (!search || [site.id, site.name, site.address, site.city].join(' ').toLocaleLowerCase('id').includes(search)));
            const pages = Math.max(1, Math.ceil(rows.length / 25)); page = Math.min(page, pages - 1);
            find('rows').innerHTML = rows.slice(page * 25, page * 25 + 25).map(site => `<tr><td>${escapeHtml(site.id)}<br>${escapeHtml(site.name)}</td><td>${escapeHtml(site.nop)}</td><td>${escapeHtml(site.address || site.city || 'Alamat belum tersedia')}</td><td>${site.point ? `<button type="button" data-map-site="${escapeHtml(site.key || site.id)}">Lihat titik</button><br>${escapeHtml(site.coordinate_source)}` : 'Belum terpetakan'}</td></tr>`).join('') || '<tr><td colspan="4">Tidak ada site yang sesuai.</td></tr>';
            find('page').textContent = `${page + 1} / ${pages} · ${number(rows.length)} site`;
            find('prev').disabled = page <= 0; find('next').disabled = page >= pages - 1;
        }
        function render(data) {
            sites = (data.sites || []).filter(site => !equipmentSites || equipmentSites.has(site.id)); selectedNop = ''; page = 0;
            const mapped = sites.filter(site => site.point).length;
            const summary = { total: sites.length, mapped, missing: sites.length - mapped };
            for (const key of ['total', 'mapped', 'missing']) find(key).textContent = number(summary[key]);
            find('legend').textContent = data.legend;
            find('population').textContent = data.population_note || '';
            const nops = [...new Set(sites.map(site => site.nop))].sort();
            find('nops').innerHTML = `<button type="button" data-nop="" aria-pressed="true">Semua NOP · ${number(sites.length)}</button>` + nops.map(nop => `<button type="button" data-nop="${escapeHtml(nop)}" aria-pressed="false"><i style="--nop-color:${color(nop)}" aria-hidden="true"></i>${escapeHtml(nop)} · ${number(sites.filter(site => site.nop === nop).length)}</button>`).join('');
            renderPoints(); fit();
        }
        async function refresh(nextFilters = {}, force = false) {
            filters = { ...nextFilters };
            if (root.dataset.module === 'equipment' && Array.isArray(filters.sites)) {
                equipmentSites = new Set(filters.sites.map(id => String(id).trim().toUpperCase()));
            }
            if (root.dataset.scope !== 'all') filters.scope = root.dataset.scope;
            const url = new URL(root.dataset.url, global.location.origin);
            for (const [key, value] of Object.entries(filters)) if (key !== 'sites' && value !== '' && value != null) {
                if (Array.isArray(value)) value.forEach(item => url.searchParams.append(`${key}[]`, item)); else url.searchParams.set(key, value);
            }
            const key = url.toString(); if (!force && key === lastKey) { if (loadedData && loadedKey === key) render(loadedData); return; }
            lastKey = key; request?.abort(); const active = new AbortController(); request = active;
            canvas.classList.add('is-loading'); canvas.setAttribute('aria-busy', 'true');
            find('status').classList.remove('is-error'); find('status').textContent = 'Memuat lokasi site…';
            const timeout = setTimeout(() => active.abort(), 30000);
            try {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: active.signal });
                if (!response.ok) throw new Error();
                const data = await response.json(); if (request === active) { loadedData = data; loadedKey = key; render(data); }
            } catch (error) {
                if (request === active) { lastKey = ''; find('status').classList.add('is-error'); find('status').textContent = 'Lokasi site gagal dimuat. Klik “Tampilkan semua titik” untuk mencoba lagi.'; markers.clearLayers(); areas.clearLayers(); labels.clearLayers(); sites = []; for (const key of ['total', 'mapped', 'missing']) find(key).textContent = '—'; find('population').textContent = ''; find('note').textContent = ''; find('nops').replaceChildren(); renderList(); }
            } finally { clearTimeout(timeout); if (request === active) { canvas.classList.remove('is-loading'); canvas.removeAttribute('aria-busy'); } }
        }
        find('fit').addEventListener('click', () => lastKey ? fit() : refresh(filters, true));
        find('areas').addEventListener('click', event => { showAreas = !showAreas; event.currentTarget.setAttribute('aria-pressed', String(showAreas)); [areas, labels].forEach(layer => showAreas ? layer.addTo(map) : map.removeLayer(layer)); layoutLabels(); });
        find('nops').addEventListener('click', event => { const button = event.target.closest('button[data-nop]'); if (!button) return; selectedNop = button.dataset.nop; page = 0; renderPoints(); fit(); });
        find('missing-toggle').addEventListener('click', () => { const list = find('list'); list.open = !list.open; find('only-missing').checked = true; page = 0; renderList(); });
        find('list').addEventListener('toggle', () => find('missing-toggle').setAttribute('aria-expanded', String(find('list').open)));
        find('search').addEventListener('input', () => { page = 0; renderList(); }); find('only-missing').addEventListener('change', () => { page = 0; renderList(); });
        find('prev').addEventListener('click', () => { page--; renderList(); }); find('next').addEventListener('click', () => { page++; renderList(); });
        find('rows').addEventListener('click', event => { const button = event.target.closest('[data-map-site]'); const marker = button && markerBySite.get(button.dataset.mapSite); if (marker) { map.setView(marker.getLatLng(), 15); marker.openTooltip(); canvas.focus({ preventScroll: true }); } });
        canvas.addEventListener('wheel', event => { if (event.ctrlKey || event.metaKey) { event.preventDefault(); clearTimeout(timer); timer = setTimeout(() => map.setZoom(map.getZoom() + (event.deltaY < 0 ? 1 : -1)), 40); } }, { passive: false });
        const observer = new ResizeObserver(() => map.invalidateSize({ pan: false })); observer.observe(canvas);
        global.addEventListener('simaster:data-changed', () => refresh(filters, true));
        root.__siteMap = { refresh, map, render }; return root.__siteMap;
    }
    function refresh(filters = {}, force = false) {
        const root = document.querySelector('[data-dashboard-site-map]');
        if (root) return create(root)?.refresh(filters, force);
    }
    function initialFilters(root) {
        const input = new URLSearchParams(global.location.search), out = {};
        for (const name of ['tahun', 'bulan', 'nop', 'city', 'status', 'ownership_scope', 'available_on_jarvis', 'tp']) if (input.has(name)) out[name] = input.get(name);
        if (root.dataset.module === 'recurring' && root.dataset.scope === 'tagihan') out.tp = document.getElementById('filter-tp')?.value || 'all';
        return out;
    }
    global.SimasterSiteMap = { refresh, tooltip, hull, labelOffsets };
    if (typeof document !== 'undefined') {
        const init = () => { const root = document.querySelector('[data-dashboard-site-map]'); if (!root) return; refresh(initialFilters(root));
            if (root.dataset.module === 'pnl' && document.getElementById('filter-status')) {
                global.jQuery?.('#filter-year, #filter-month, #filter-nop, #filter-city, #filter-status').on('change.siteMap', () => setTimeout(() => refresh({ tahun: document.getElementById('filter-year').value, bulan: document.getElementById('filter-month').value || 'all', nop: document.getElementById('filter-nop').value, city: document.getElementById('filter-city').value, status: document.getElementById('filter-status').value }), 0));
            }
            if (root.dataset.module === 'recurring') global.jQuery?.('#filter-tp').on('change.siteMap', () => refresh({ tp: document.getElementById('filter-tp').value }));
            if (root.dataset.module === 'electricity' && root.dataset.scope !== 'all') global.jQuery?.('#filter-nop').on('change.siteMap', () => refresh({ nop: document.getElementById('filter-nop').value }));
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true }); else init();
    }
})(globalThis);
