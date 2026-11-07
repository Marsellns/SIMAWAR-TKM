<?php

namespace App\Services;

use App\Support\SiteCoordinates;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Read-only address fallback across the existing module masters. */
class SiteAddressCatalog
{
    private ?array $localities = null;

    public static function usable(mixed $address): bool
    {
        $value = trim((string) $address);
        if (preg_match('/^[23456789CFGHJMPQRVWX]{4,8}\+[23456789CFGHJMPQRVWX]{2,7}$/i', $value)) return true;
        return mb_strlen($value) >= 8 && !in_array(mb_strtolower($value), ['unknown', 'belum ada', 'belum tersedia'], true);
    }

    public function rows(): Collection
    {
        $rows = collect();
        foreach ([
            'site_owners' => ['site_code', true], 'listrik_pln' => ['site_id', false],
            'listrik_inbuilding' => ['site_id', false], 'recurring_ipas' => ['site_code', false],
        ] as $table => [$column, $hasCity]) {
            $query = DB::table($table)->select([$column.' as site_code', 'alamat', ...($hasCity ? ['city'] : [])])->orderBy('id');
            foreach ($query->cursor() as $row) {
                $code = SiteCoordinates::key($row->site_code);
                if ($code === '') continue;
                $existing = $rows->get($code);
                $address = trim((string) $row->alamat);
                $city = trim((string) ($row->city ?? $existing?->city ?? ''));
                if ($city === '') $city = $this->cityFromAddress($address);
                if (!$existing || (!self::usable($existing->alamat) && self::usable($address))) {
                    if ($existing && !self::usable($existing->alamat)) $city = $this->cityFromAddress($address) ?: $city;
                    $rows->put($code, (object) ['site_code' => $code, 'alamat' => $address, 'city' => $city]);
                } elseif ($existing->city === '' && $city !== '') {
                    $existing->city = $city;
                }
            }
        }
        return $rows;
    }

    private function cityFromAddress(string $address): string
    {
        $address = preg_replace('/[.,]+/', ' ', mb_strtoupper($address));
        $address = str_replace('KOTA BKS', 'KOTA BEKASI', $address);
        if ($this->localities === null) {
            $this->localities = json_decode(file_get_contents(resource_path('data/site-map-localities.geojson')), true)['features'];
            usort($this->localities, fn ($a, $b) => strlen($b['properties']['city']) <=> strlen($a['properties']['city']));
        }
        // Explicit administrative names avoid guessing a regency from a village.
        foreach ($this->localities as $feature) {
            $label = $feature['properties']['city'];
            $name = preg_quote(mb_strtoupper(preg_replace('/^Kota /', '', $label)), '/');
            $prefix = str_starts_with($label, 'Kota ') ? 'KOTA(?:MADYA)?' : 'KAB(?:UPATEN)?';
            if (preg_match('/'.$prefix.'\s*'.$name.'\b/u', $address)) return $label;
        }
        foreach (['BARAT', 'PUSAT', 'SELATAN', 'TIMUR', 'UTARA'] as $direction) {
            if (preg_match('/\b(?:JAKARTA|JKT)\s+'.$direction.'\b/u', $address)) return 'Kota Jakarta '.mb_convert_case($direction, MB_CASE_TITLE);
        }
        foreach (['KARAWANG' => 'Karawang', 'PURWAKARTA' => 'Purwakarta', 'DEPOK' => 'Kota Depok'] as $name => $label) {
            if (preg_match('/\b'.$name.'\b/', $address)) return $label;
        }
        if (preg_match('/\b(?:JAKARTA|JKT)\b/u', $address)) return 'Jakarta';
        return '';
    }
}
