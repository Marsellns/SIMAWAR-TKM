<?php

namespace App\Support;

/** Accepts an address-level match; never substitutes a municipality center. */
final class SiteAddressMatch
{
    public static function select(string $address, array $features, array $boundary): ?array
    {
        $matches = [];
        foreach ($features as $feature) {
            $match = self::candidate($address, $feature, $boundary);
            if ($match) $matches[round($match['lat'], 6).','.round($match['lng'], 6)] = $match;
        }
        // Several distinct locations with the same name require review.
        return count($matches) === 1 ? array_values($matches)[0] : null;
    }

    public static function candidate(string $address, array $feature, array $boundary): ?array
    {
        $properties = $feature['properties'] ?? [];
        if (strtoupper($properties['countrycode'] ?? '') !== 'ID') return null;
        if (in_array($properties['osm_key'] ?? '', ['place', 'boundary'], true)) return null;
        $coordinates = $feature['geometry']['coordinates'] ?? [];
        $point = SiteCoordinates::point($coordinates[1] ?? null, $coordinates[0] ?? null);
        if (!$point || !AddressPlusCode::contains($boundary, $point)) return null;

        $streets = self::streets($address);
        if ($streets === []) return null;
        $street = $properties['street'] ?? (($properties['osm_key'] ?? '') === 'highway' ? ($properties['name'] ?? '') : '');
        $streetKey = self::name($street);
        if ($streetKey === '' || !in_array($streetKey, $streets, true)) return null;

        $district = self::district($address);
        $candidateDistrict = self::text($properties['district'] ?? $properties['city_district'] ?? $properties['subdistrict'] ?? '');
        if ($district !== '' && ($candidateDistrict === '' || $candidateDistrict !== $district)) return null;

        $house = self::house($address);
        $candidateHouse = str_replace(' ', '', self::text($properties['housenumber'] ?? ''));
        if ($house !== '' && $candidateHouse !== '' && $house !== $candidateHouse) return null;
        $houseMatches = $house !== '' && $candidateHouse === $house;
        $isStreet = ($properties['type'] ?? '') === 'street' || (($properties['osm_key'] ?? '') === 'highway' && ($properties['osm_type'] ?? 'W') !== 'N');
        // An unrelated building somewhere on the same road is not the site.
        if (!$houseMatches && !$isStreet) return null;
        if (!$houseMatches) {
            $extent = $properties['extent'] ?? [];
            if (count($extent) !== 4 || count(array_filter($extent, 'is_numeric')) !== 4) return null;
            $width = abs($extent[2] - $extent[0]) * 111320 * cos(deg2rad($point['lat']));
            $height = abs($extent[3] - $extent[1]) * 110540;
            if (hypot($width, $height) > 750) return null;
        }
        return $point + [
            'precision' => $houseMatches ? 'building' : 'street',
            'district' => $district !== '' ? ($properties['district'] ?? $properties['city_district'] ?? $properties['subdistrict']) : null,
        ];
    }

    public static function streets(string $address): array
    {
        return array_values(array_unique(array_map(self::name(...), self::streetLabels($address))));
    }

    public static function streetQuery(string $address, string $city): ?string
    {
        $street = self::streetLabels($address)[0] ?? null;
        if (!$street) return null;
        $house = self::house($address);
        return $street.($house !== '' ? ' No. '.$house : '').', '.$city.', Indonesia';
    }

    private static function streetLabels(string $address): array
    {
        $address = preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $address);
        preg_match_all('/\b(?:jalan|jln|jl|gang|gg)[\s.,]+([^,;]+?)(?=,|;|\b(?:nomor|no|rt|rw)\.?\s*\d|\b(?:desa|ds|kelurahan|kel|kecamatan|kec|kabupaten|kab|kota|provinsi|indonesia)\b|$)/iu', $address, $matches);
        $streets = [];
        foreach ($matches[1] as $index => $street) {
            if (preg_match('/^\s*(?:rt|rw|kp|kampung|desa|kel|kec)\b/i', $street)) continue;
            $key = self::name($street);
            if (strlen($key) >= 6) $streets[] = trim(preg_replace(['/^(?:jalan|jl|jln)[\s.,]+/iu', '/^(?:gang|gg)[\s.,]+/iu'], ['Jalan ', 'Gang '], $matches[0][$index]));
        }
        return array_values(array_unique($streets));
    }

    public static function district(string $address): string
    {
        if (!preg_match('/\b(?:kecamatan|kec)[\s.]+(.+?)(?=,|;|\b(?:kabupaten|kab|kota|provinsi|jawa|indonesia)\b|$)/iu', $address, $match)) return '';
        return self::text($match[1]);
    }

    private static function house(string $address): string
    {
        return preg_match('/\b(?:nomor|no)[\s.]*([0-9]+\s*[a-z]?)\b/iu', $address, $match) ? str_replace(' ', '', self::text($match[1])) : '';
    }

    private static function name(string $value): string
    {
        $value = preg_replace('/^(jalan|jl|jln|gang|gg)[\s.]+/iu', '', trim($value));
        return str_replace(' ', '', self::text($value));
    }

    private static function text(string $value): string
    {
        $value = trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($value)));
        $value = preg_replace('/\b(bar|sel|ut|tim)\b/u', '__$1__', $value);
        $value = strtr($value, ['__bar__'=>'barat', '__sel__'=>'selatan', '__ut__'=>'utara', '__tim__'=>'timur']);
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
