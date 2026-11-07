<?php

namespace App\Support;

use InvalidArgumentException;
use RuntimeException;

/** Streams the source CSV; retains only the latest unambiguous location per Site ID. */
final class TracyCoordinateReader
{
    public function read(string $filename): array
    {
        if (!is_file($filename) || !is_readable($filename)) throw new InvalidArgumentException('File CSV tidak dapat dibaca.');
        $handle = fopen($filename, 'rb');
        if ($handle === false) throw new RuntimeException('File CSV tidak dapat dibuka.');
        try {
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$header) throw new InvalidArgumentException('CSV kosong.');
            $header = array_map(fn ($value) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $value))), $header);
            $columns = array_flip($header);
            foreach (['siteid', 'latitude', 'longitude', 'yearmonth'] as $required) {
                if (!array_key_exists($required, $columns)) throw new InvalidArgumentException('Kolom wajib tidak ditemukan: '.$required);
            }
            $sites = []; $rows = 0; $invalid = 0;
            while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                if ($row === [null]) continue;
                $rows++;
                $value = fn ($key) => isset($columns[$key]) ? trim((string) ($row[$columns[$key]] ?? '')) : '';
                $code = SiteCoordinates::key($value('siteid'));
                $period = $value('yearmonth');
                $point = SiteCoordinates::point($value('latitude'), $value('longitude'));
                if (!preg_match('/^[A-Z0-9_-]{3,30}$/', $code) || !preg_match('/^20\d{2}(0[1-9]|1[0-2])$/', $period) || !$point) { $invalid++; continue; }
                $previous = $sites[$code] ?? null;
                if ($previous && $previous['period'] > $period) continue;
                if ($previous && $previous['period'] === $period) {
                    if (abs($previous['point']['lat'] - $point['lat']) > 0.0000001 || abs($previous['point']['lng'] - $point['lng']) > 0.0000001) $sites[$code]['ambiguous'] = true;
                    continue;
                }
                $sites[$code] = [
                    'site_code' => $code, 'point' => $point, 'period' => $period,
                    'city' => $value('kabupaten'), 'district' => $value('kecamatan'), 'name' => $value('sitelabel'), 'ambiguous' => false,
                ];
            }
            $ambiguous = count(array_filter($sites, fn ($site) => $site['ambiguous']));
            return ['rows' => $rows, 'invalid_rows' => $invalid, 'ambiguous_sites' => $ambiguous, 'sites' => array_filter($sites, fn ($site) => !$site['ambiguous'])];
        } finally { fclose($handle); }
    }
}
