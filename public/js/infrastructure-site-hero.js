(function (global) {
    const palettes = [
        { sky: ['#d9ebff', '#86b9e4'], mountain: '#9bb9ce', foothill: '#6d98a4', ground: '#487f70', city: '#68889a' },
        { sky: ['#e1f3ef', '#91c9cb'], mountain: '#9bbdc2', foothill: '#6e9b9a', ground: '#4c8178', city: '#6c9194' },
        { sky: ['#ece9ff', '#a9bce9'], mountain: '#abb2d0', foothill: '#7f96b7', ground: '#657f92', city: '#788ba8' },
        { sky: ['#fff0dd', '#e7b5a9'], mountain: '#bcb3bf', foothill: '#918fa7', ground: '#688b8a', city: '#8b91a0' },
        { sky: ['#e0f0fa', '#94c5de'], mountain: '#a5bfd0', foothill: '#779eaf', ground: '#50877f', city: '#7793a3' },
    ];
    const cache = new Map();
    const photoCache = new Map();
    const photoVersions = new Map();

    function seedFor(value) {
        let hash = 2166136261;
        for (const character of String(value || 'site')) {
            hash = Math.imul(hash ^ character.codePointAt(0), 16777619);
        }
        return hash >>> 0;
    }

    function landscape(value) {
        let state = seedFor(value);
        const random = () => {
            state = (state + 0x6D2B79F5) >>> 0;
            let next = Math.imul(state ^ state >>> 15, 1 | state);
            next ^= next + Math.imul(next ^ next >>> 7, 61 | next);
            return ((next ^ next >>> 14) >>> 0) / 4294967296;
        };
        const palette = palettes[state % palettes.length];
        const ridge = (base, relief, color) => {
            const points = [];
            for (let x = 0; x <= 1200; x += 60) {
                points.push(`${x},${Math.round(base - random() * relief)}`);
            }
            return `<polygon points="0,210 ${points.join(' ')} 1200,210" fill="${color}"/>`;
        };
        const clouds = Array.from({ length: 5 }, () => {
            const x = Math.round(350 + random() * 820);
            const y = Math.round(17 + random() * 48);
            const width = Math.round(32 + random() * 45);
            return `<ellipse cx="${x}" cy="${y}" rx="${width}" ry="${Math.round(width / 5)}" fill="#fff" opacity=".35"/>`;
        }).join('');
        let buildings = '';
        for (let x = 300; x < 1200;) {
            const width = Math.round(7 + random() * 19);
            const height = Math.round(5 + random() * 20);
            buildings += `<rect x="${x}" y="${188 - height}" width="${width}" height="${height}" fill="${palette.city}" opacity="${(.3 + random() * .32).toFixed(2)}"/>`;
            x += width + Math.round(2 + random() * 8);
        }
        const towerX = Math.round(865 + random() * 225);
        const top = Math.round(4 + random() * 24);
        const towerHeight = 200 - top;
        let braces = '';
        for (let y = top + 17; y < 196; y += 22) {
            const nextY = Math.min(y + 22, 198);
            const left = 5 + (y - top) / towerHeight * 23;
            const nextLeft = 5 + (nextY - top) / towerHeight * 23;
            braces += `<path d="M${-left} ${y}H${left} M${-left} ${y}L${nextLeft} ${nextY} M${left} ${y}L${-nextLeft} ${nextY}"/>`;
        }
        const dishes = [top + 55, top + 93, top + 133].map((y, index) => {
            const side = index % 2 ? -1 : 1;
            const offset = 9 + (y - top) / towerHeight * 23;
            return `<path d="M${side * offset} ${y}h${side * 17}" stroke="#526a7c" stroke-width="3"/><ellipse cx="${side * (offset + 18)}" cy="${y}" rx="5" ry="11" fill="#dce8ef" stroke="#526a7c" stroke-width="2"/>`;
        }).join('');

        return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 200" preserveAspectRatio="xMidYMid slice">
            <defs><linearGradient id="sky" x2="0" y2="1"><stop stop-color="${palette.sky[0]}"/><stop offset="1" stop-color="${palette.sky[1]}"/></linearGradient></defs>
            <rect width="1200" height="200" fill="url(#sky)"/>
            <circle cx="${Math.round(700 + random() * 350)}" cy="35" r="55" fill="#fff" opacity=".17"/>
            ${clouds}
            ${ridge(151, 47, palette.mountain)}
            ${ridge(171, 31, palette.foothill)}
            <path d="M0 178 Q300 168 580 184 T1200 175 V200 H0Z" fill="${palette.ground}" opacity=".55"/>
            ${buildings}
            <path d="M0 195 Q430 186 780 196 T1200 190 V200 H0Z" fill="${palette.ground}" opacity=".8"/>
            <g transform="translate(${towerX} 0)" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path d="M-29 200L-5 ${top}H5L29 200 M0 ${top}V0" stroke="#a9434a" stroke-width="4"/>
                <g stroke="#bd5960" stroke-width="2.4">${braces}</g>
                <path d="M-33 200H33" stroke="#435d69" stroke-width="3"/>
                ${dishes}
            </g>
        </svg>`;
    }

    function backgroundFor(siteKey) {
        const key = String(siteKey || 'site');
        if (!cache.has(key)) {
            cache.set(key, `url("data:image/svg+xml,${encodeURIComponent(landscape(key))}")`);
        }
        return cache.get(key);
    }

    function decodeKey(value) {
        try {
            return decodeURIComponent(value || '');
        } catch (_) {
            return value || '';
        }
    }

    function photoUrl(key) {
        const url = new URL(global.infrastructureSiteHeroConfig.photoUrl, global.location.href);
        url.searchParams.set('site_code', key);
        if (photoVersions.has(key)) url.searchParams.set('v', String(photoVersions.get(key)));
        return url.toString();
    }

    function loadPhoto(key) {
        if (!key || !global.infrastructureSiteHeroConfig?.photoUrl || typeof global.Image !== 'function') {
            return Promise.resolve(null);
        }
        if (!photoCache.has(key)) {
            photoCache.set(key, new Promise(resolve => {
                const image = new global.Image();
                const url = photoUrl(key);
                image.onload = () => resolve(url);
                image.onerror = () => resolve(null);
                image.src = url;
            }));
        }
        return photoCache.get(key);
    }

    function refreshSite(key, hasPhoto) {
        photoCache.delete(key);
        if (hasPhoto) photoVersions.set(key, Date.now());
        if (!hasPhoto) photoCache.set(key, Promise.resolve(null));
        apply(document);
    }

    function uploadControls(hero, key) {
        const config = global.infrastructureSiteHeroConfig;
        if (!config?.canUpload || !key || hero.querySelector('.infrastructure-site-hero-controls')) return;

        const controls = document.createElement('div');
        controls.className = 'infrastructure-site-hero-controls';
        const upload = document.createElement('button');
        upload.type = 'button';
        upload.className = 'infrastructure-site-hero-button';
        upload.textContent = 'Tambah foto';
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'infrastructure-site-hero-button';
        remove.textContent = 'Hapus foto';
        remove.hidden = true;
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/jpeg,image/png,image/webp';
        input.hidden = true;
        const message = document.createElement('span');
        message.className = 'infrastructure-site-hero-message';
        message.setAttribute('role', 'status');
        controls.append(upload, remove, input, message);
        hero.appendChild(controls);

        upload.addEventListener('click', () => input.click());
        input.addEventListener('change', async () => {
            const file = input.files?.[0];
            if (!file) return;
            upload.disabled = true;
            message.textContent = 'Mengunggah...';
            try {
                const form = new FormData();
                form.append('site_code', key);
                form.append('photo', file);
                const response = await fetch(config.uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' },
                    body: form,
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Foto gagal diunggah.');
                message.textContent = '';
                refreshSite(key, true);
            } catch (error) {
                message.textContent = error.message || 'Foto gagal diunggah.';
            } finally {
                upload.disabled = false;
                input.value = '';
            }
        });
        remove.addEventListener('click', async () => {
            remove.disabled = true;
            message.textContent = 'Menghapus...';
            try {
                const url = new URL(config.photoUrl, global.location.href);
                url.searchParams.set('site_code', key);
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': config.csrfToken, 'Accept': 'application/json' },
                });
                if (!response.ok) throw new Error('Foto gagal dihapus.');
                message.textContent = '';
                refreshSite(key, false);
            } catch (error) {
                message.textContent = error.message || 'Foto gagal dihapus.';
            } finally {
                remove.disabled = false;
            }
        });
    }

    function apply(root) {
        const container = root || document;
        const heroes = container.querySelectorAll('[data-site-hero]');
        heroes.forEach(hero => {
            const heroKey = decodeKey(hero.dataset.siteHero);
            const key = decodeKey(hero.dataset.sitePhotoCode).trim().toUpperCase();
            hero.style.setProperty('--infrastructure-site-hero-image', backgroundFor(heroKey));
            hero.dataset.hasSitePhoto = '0';
            uploadControls(hero, key);
            const controls = hero.querySelector('.infrastructure-site-hero-controls');
            if (controls) {
                controls.querySelector('button').textContent = 'Tambah foto';
                controls.querySelectorAll('button')[1].hidden = true;
            }
            const request = loadPhoto(key);
            request.then(url => {
                if (!hero.isConnected || decodeKey(hero.dataset.sitePhotoCode).trim().toUpperCase() !== key
                    || photoCache.get(key) !== request || !url) return;
                hero.style.setProperty('--infrastructure-site-hero-image', `url("${url}")`);
                hero.dataset.hasSitePhoto = '1';
                if (controls) {
                    controls.querySelector('button').textContent = 'Ganti foto';
                    controls.querySelectorAll('button')[1].hidden = false;
                }
            });
        });
    }

    global.infrastructureSiteHero = { apply, backgroundFor };
})(globalThis);
