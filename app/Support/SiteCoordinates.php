<?php

namespace App\Support;

final class SiteCoordinates
{
    public static function key(mixed $value): string
    {
        return mb_strtoupper(trim((string) $value));
    }

    public static function point(mixed $latitude, mixed $longitude): ?array
    {
        if (!is_scalar($latitude) || !is_scalar($longitude)) return null;
        $lat = str_replace(',', '.', trim((string) $latitude));
        $lng = str_replace(',', '.', trim((string) $longitude));
        if (!is_numeric($lat) || !is_numeric($lng)) return null;
        $lat = (float) $lat; $lng = (float) $lng;
        // This application manages Indonesian sites. Reject zero/swap/sentinel data.
        return is_finite($lat) && is_finite($lng) && $lat >= -11.1 && $lat <= 6.2 && $lng >= 94.9 && $lng <= 141.1
            ? ['lat' => $lat, 'lng' => $lng] : null;
    }

    public static function fromDetails(mixed $source): ?array
    {
        $details = CombatSourceDetails::flattened($source);
        return self::point($details['latitude'] ?? $details['lat'] ?? null,
            $details['longitude'] ?? $details['longtitude'] ?? $details['lng'] ?? $details['lon'] ?? null);
    }

    public static function addressHash(string $address, string $city = ''): string
    {
        return hash('sha256', self::key(preg_replace('/\s+/', ' ', $address)).'|'.self::key($city));
    }

    public static function isMasterSource(string $source): bool
    {
        return $source === 'master' || preg_match('/^(tracy_csv|rru_inventory|site_cost):20\d{2}(0[1-9]|1[0-2]):[A-Z0-9_-]{3,30}$/', $source) === 1;
    }
}
