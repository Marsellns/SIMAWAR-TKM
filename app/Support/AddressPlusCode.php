<?php

namespace App\Support;

/** Decodes OLC per Google's public specification; never guesses missing locality. */
final class AddressPlusCode
{
    private const ALPHABET = '23456789CFGHJMPQRVWX';

    public static function fromAddress(string $address, ?array $boundary): ?array
    {
        if (!preg_match('/\b([23456789CFGHJMPQRVWX]{2,8}\+[23456789CFGHJMPQRVWX]{2,7})\b/i', $address, $match)) return null;
        $code = strtoupper($match[1]);
        $position = strpos($code, '+');
        if ($position === 8) return self::decode($code);
        if (!in_array($position, [2, 4, 6], true) || !$boundary) return null;
        $bounds = self::bounds($boundary);
        $missing = 8 - $position;
        $step = 20 ** (2 - $missing / 2);
        $candidates = [];
        for ($lat = floor(($bounds[1] + 90) / $step) * $step - 90; $lat <= $bounds[3]; $lat += $step) {
            for ($lng = floor(($bounds[0] + 180) / $step) * $step - 180; $lng <= $bounds[2]; $lng += $step) {
                $prefix = self::prefix($lat + $step / 2, $lng + $step / 2, $missing);
                $point = self::decode($prefix.$code);
                if ($point && self::contains($boundary, $point)) $candidates[] = $point;
            }
        }
        return count($candidates) === 1 ? $candidates[0] : null;
    }

    private static function prefix(float $lat, float $lng, int $length): string
    {
        $lat += 90; $lng += 180; $code = '';
        foreach ([20, 1, .05, .0025, .000125] as $step) {
            $a = (int) floor(round($lat / $step, 8)); $b = (int) floor(round($lng / $step, 8));
            $code .= self::ALPHABET[$a].self::ALPHABET[$b];
            $lat -= $a * $step; $lng -= $b * $step;
            if (strlen($code) >= $length) return substr($code, 0, $length);
        }
        return $code;
    }

    public static function decode(string $code): ?array
    {
        if (!preg_match('/^[23456789CFGHJMPQRVWX]{8}\+[23456789CFGHJMPQRVWX]{2,7}$/', $code)) return null;
        $digits = str_replace('+', '', $code); $lat = -90; $lng = -180; $latSize = .000125; $lngSize = .000125;
        foreach ([20, 1, .05, .0025, .000125] as $index => $step) {
            $lat += strpos(self::ALPHABET, $digits[$index * 2]) * $step;
            $lng += strpos(self::ALPHABET, $digits[$index * 2 + 1]) * $step;
        }
        for ($index = 10; $index < strlen($digits); $index++) {
            $digit = strpos(self::ALPHABET, $digits[$index]); $latSize /= 5; $lngSize /= 4;
            $lat += intdiv($digit, 4) * $latSize; $lng += ($digit % 4) * $lngSize;
        }
        return SiteCoordinates::point(round($lat + $latSize / 2, 7), round($lng + $lngSize / 2, 7));
    }

    public static function bounds(array $geometry): array
    {
        $points = [];
        $walk = function ($coordinates) use (&$walk, &$points) {
            if (is_numeric($coordinates[0] ?? null)) $points[] = $coordinates;
            else foreach ($coordinates as $child) $walk($child);
        };
        $walk($geometry['coordinates']);
        return [min(array_column($points, 0)), min(array_column($points, 1)), max(array_column($points, 0)), max(array_column($points, 1))];
    }

    public static function contains(array $geometry, array $point): bool
    {
        $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
        foreach ($polygons as $rings) {
            if (!self::inRing($rings[0], $point)) continue;
            foreach (array_slice($rings, 1) as $hole) if (self::inRing($hole, $point)) continue 2;
            return true;
        }
        return false;
    }

    private static function inRing(array $ring, array $point): bool
    {
        $inside = false; $x = $point['lng']; $y = $point['lat'];
        for ($i = 0, $j = count($ring) - 1; $i < count($ring); $j = $i++) {
            [$xi, $yi] = $ring[$i]; [$xj, $yj] = $ring[$j];
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) $inside = !$inside;
        }
        return $inside;
    }
}
