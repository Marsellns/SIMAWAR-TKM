(function (global) {
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        })[character]);
    }

    function validPoint(value) {
        const lat = Number(value?.lat ?? value?.latitude);
        const lon = Number(value?.lon ?? value?.longitude);
        return Number.isFinite(lat) && Number.isFinite(lon)
            && Math.abs(lat) <= 90 && Math.abs(lon) <= 180 && (lat !== 0 || lon !== 0)
            ? { lat, lon } : null;
    }

    function destination(point, address, city) {
        const coordinates = validPoint(point);
        if (coordinates) return `${coordinates.lat},${coordinates.lon}`;
        const street = String(address ?? '').trim();
        if (!street || /^[-–—]+$/.test(street)) return '';
        return [street, city, 'Indonesia'].filter(Boolean).join(', ');
    }

    function directionsUrl(place, origin = null) {
        if (!place) return '';
        const url = new URL('https://www.google.com/maps/dir/');
        url.searchParams.set('api', '1');
        url.searchParams.set('destination', place);
        const coordinates = validPoint(origin);
        if (coordinates) url.searchParams.set('origin', `${coordinates.lat},${coordinates.lon}`);
        return url.toString();
    }

    function locationUrl(place) {
        const url = new URL('https://maps.google.com/maps');
        url.searchParams.set('q', place);
        url.searchParams.set('z', '14');
        url.searchParams.set('output', 'embed');
        return url.toString();
    }

    function previewUrl(place, origin = null) {
        const url = new URL('https://maps.google.com/maps');
        const coordinates = validPoint(origin);
        url.searchParams.set('f', 'd');
        url.searchParams.set('saddr', coordinates ? `${coordinates.lat},${coordinates.lon}` : 'Current Location');
        url.searchParams.set('daddr', place);
        url.searchParams.set('dirflg', 'd');
        url.searchParams.set('output', 'embed');
        return url.toString();
    }

    function render(point, address, city) {
        const place = destination(point, address, city);
        if (!place) {
            return '<div class="infrastructure-site-map is-empty" role="img" aria-label="Lokasi site belum tersedia"><span>Lokasi site belum tersedia</span></div>';
        }
        const arrow = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12h16M13 5l7 7-7 7"/></svg>';
        return `<div class="infrastructure-site-map" data-infrastructure-site-map data-destination="${escapeHtml(place)}">
            <iframe src="${escapeHtml(locationUrl(place))}" title="Peta lokasi site" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>
            <a class="infrastructure-site-map-direction" data-infrastructure-site-map-link href="${escapeHtml(directionsUrl(place))}" target="_blank" rel="noopener noreferrer" aria-label="Tampilkan rute dari posisi saya di peta">${arrow}</a>
        </div>`;
    }

    function showPreview(map, link, origin) {
        if (map.isConnected === false) return;
        const place = map.dataset.destination;
        const frame = map.querySelector('iframe');
        if (!frame || !place) return;
        frame.src = previewUrl(place, origin);
        frame.title = 'Peta rute dari posisi saya ke lokasi site';
        link.href = directionsUrl(place, origin);
        link.setAttribute('aria-label', 'Buka rute di Google Maps');
        map.dataset.phase = 'preview';
        map.removeAttribute('aria-busy');
    }

    function handleClick(event) {
        const link = event.target?.closest?.('[data-infrastructure-site-map-link]');
        if (!link) return;
        const map = link.closest('[data-infrastructure-site-map]');
        if (!map || map.dataset.phase === 'preview') return;
        event.preventDefault();
        if (map.dataset.phase === 'loading') return;
        map.dataset.phase = 'loading';
        map.setAttribute('aria-busy', 'true');

        const finish = position => showPreview(map, link, position?.coords ?? null);
        const geolocation = global.navigator?.geolocation;
        if (!geolocation?.getCurrentPosition) {
            finish(null);
            return;
        }
        try {
            geolocation.getCurrentPosition(finish, () => finish(null), {
                enableHighAccuracy: false, maximumAge: 60000, timeout: 8000,
            });
        } catch (_) {
            finish(null);
        }
    }

    if (typeof document !== 'undefined') {
        document.addEventListener('click', handleClick);
    }
    global.infrastructureSiteMap = { destination, directionsUrl, locationUrl, previewUrl, render, handleClick };
})(globalThis);
