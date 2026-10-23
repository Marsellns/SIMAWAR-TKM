(function () {
    const dayMs = 24 * 60 * 60 * 1000;

    function calendarDate(year, month, day) {
        const date = new Date(Date.UTC(year, month - 1, day));
        return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day
            ? date : null;
    }

    function parseDate(value) {
        if (value instanceof Date) {
            return Number.isNaN(value.getTime()) ? null
                : calendarDate(value.getUTCFullYear(), value.getUTCMonth() + 1, value.getUTCDate());
        }
        if (typeof value === 'number' && Number.isFinite(value) && value >= 20000 && value <= 80000) {
            return new Date(Date.UTC(1899, 11, 30) + Math.floor(value) * dayMs);
        }
        const text = String(value ?? '').trim();
        if (!text || text.startsWith('=')) return null;
        if (/^\d+(?:\.\d+)?$/.test(text)) {
            const serial = Number(text);
            if (serial >= 20000 && serial <= 80000) {
                return new Date(Date.UTC(1899, 11, 30) + Math.floor(serial) * dayMs);
            }
        }
        const yearFirst = text.match(/^(\d{4})[-/](\d{1,2})[-/](\d{1,2})(?:$|[T\s])/);
        if (yearFirst) return calendarDate(Number(yearFirst[1]), Number(yearFirst[2]), Number(yearFirst[3]));
        const dayFirst = text.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{4})$/);
        if (dayFirst) return calendarDate(Number(dayFirst[3]), Number(dayFirst[2]), Number(dayFirst[1]));
        return null;
    }

    function todayInJakarta() {
        const parts = new Intl.DateTimeFormat('en-US', {
            timeZone: 'Asia/Jakarta', year: 'numeric', month: 'numeric', day: 'numeric',
        }).formatToParts(new Date());
        const values = Object.fromEntries(parts.map(part => [part.type, Number(part.value)]));
        return calendarDate(values.year, values.month, values.day);
    }

    function formatDate(value) {
        const date = parseDate(value);
        return date ? date.toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric', timeZone: 'UTC',
        }) : null;
    }

    function formatSourceDate(key, value) {
        const normalizedKey = String(key).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
        const isDate = /^(?:tgl_|tanggal(?:_|$)|date(?:_|$)|start_date_|end_date_|periode_(?:awal|akhir)(?:_|$)|new_period_(?:awal|akhir)$|(?:start|end)_sewa$|termin_(?:start|end)$|proses_.*_at$)/.test(normalizedKey)
            || /_date$/.test(normalizedKey);
        if (!isDate) return value;
        const formatted = formatDate(value);
        return formatted || (/^\d+(?:\.\d+)?$/.test(String(value).trim()) ? 'Tanggal tidak valid' : value);
    }

    function forStage(stages, index, { asOf, terminal = false } = {}) {
        if (terminal) return null;
        const start = parseDate(stages[index]?.[1]);
        if (!start) return null;
        const next = stages.slice(index + 1).map(([, value]) => parseDate(value)).find(Boolean);
        const end = next || (asOf === undefined ? todayInJakarta() : parseDate(asOf));
        if (!end || end < start) return null;

        let months = (end.getUTCFullYear() - start.getUTCFullYear()) * 12
            + end.getUTCMonth() - start.getUTCMonth();
        const lastDay = new Date(Date.UTC(end.getUTCFullYear(), end.getUTCMonth() + 1, 0)).getUTCDate();
        const anniversary = calendarDate(end.getUTCFullYear(), end.getUTCMonth() + 1,
            Math.min(start.getUTCDate(), lastDay));
        if (anniversary > end) months--;
        const days = Math.round((end - start) / dayMs);
        return {
            label: months > 0 ? `${months} bulan` : `${days} hari`,
            endDate: end,
            ongoing: !next,
        };
    }

    globalThis.infrastructureStageDuration = { forStage, formatDate, formatSourceDate };
})();
