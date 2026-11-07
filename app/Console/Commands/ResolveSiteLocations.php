<?php

namespace App\Console\Commands;

use App\Services\DashboardSiteMapService;
use App\Support\AddressPlusCode;
use App\Support\ReportDataVersion;
use App\Support\SiteCoordinates;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ResolveSiteLocations extends Command
{
    protected $signature = 'sites:resolve-locations {--geocode : Cari sisa alamat melalui endpoint Photon yang dikonfigurasi} {--limit=0 : Batas alamat; 0 = semua lokal / 100 untuk geocoding} {--retry : Coba ulang alamat yang sebelumnya belum ditemukan}';
    protected $description = 'Baca koordinat dataset/Plus Code dan simpan lokasi site tanpa mengubah master operasional';

    public function handle(DashboardSiteMapService $maps): int
    {
        if (!Schema::hasTable('site_locations')) { $this->error('Jalankan php artisan migrate terlebih dahulu.'); return self::FAILURE; }
        if ($this->option('geocode') && !config('site-map.geocoding_url')) { $this->error('Isi SITE_GEOCODING_URL dengan endpoint Photon perusahaan/self-hosted untuk geocoding massal.'); return self::FAILURE; }
        $lock = Cache::lock('site-locations.resolve', 86400);
        if (!$lock->get()) { $this->error('Proses pencarian koordinat lain masih berjalan.'); return self::FAILURE; }
        try {
            $features = json_decode(file_get_contents(resource_path('data/site-map-localities.geojson')), true)['features'];
            $boundaries = collect($features)->keyBy(fn ($feature) => SiteCoordinates::key($feature['properties']['city']));
            $addresses = app(\App\Services\SiteAddressCatalog::class)->rows();
            $source = $maps->sourcePoints();
            // Include source-only Combat/Sewa sites absent from the owner/electricity master.
            foreach ($source as $code => $point) if (!$addresses->has($code)) $addresses->put($code, (object) ['site_code' => $code, 'alamat' => '', 'city' => '']);
            $stored = DB::table('site_locations')->get()->keyBy('site_code');
            $resolved = 0; $pending = 0; $checked = 0; $limit = max(0, (int) $this->option('limit'));
            if ($this->option('geocode') && !$limit) $limit = 100;
            foreach ($addresses as $code => $row) {
                if ($code === '') continue;
                $address = trim((string) $row->alamat); $city = trim((string) ($row->city ?? ''));
                $hash = SiteCoordinates::addressHash($address, $city); $existing = $stored->get($code);
                if ($existing && SiteCoordinates::isMasterSource($existing->source)) continue;
                $alreadyAttempted = !$this->option('geocode') || $existing?->source === 'photon';
                if ($existing && $existing->address_hash === $hash && ($existing->status === 'resolved' || (!$this->option('retry') && $alreadyAttempted))) continue;
                $boundary = $boundaries->get(SiteCoordinates::key($city))['geometry'] ?? null;
                $point = $source->get($code); $origin = 'dataset'; $precision = 'site';
                if (!$point && preg_match('/(-?\d{1,2}\.\d{4,})\s*,\s*(\d{3}\.\d{4,})/', $address, $match)) {
                    $point = SiteCoordinates::point($match[1], $match[2]); $origin = 'address_coordinates';
                }
                if (!$point) {
                    $point = AddressPlusCode::fromAddress($address, $boundary);
                    $origin = 'plus_code'; $precision = 'plus_code';
                }
                // Do not count an address without a known region as a provider
                // attempt; it would otherwise keep occupying every next batch.
                if (!$point && $this->option('geocode') && ($address === '' || !$boundary)) continue;
                if ($limit && $checked >= $limit) break;
                $checked++;
                if (!$point && $this->option('geocode') && $address !== '') {
                    try { $point = $this->geocode($address, $city, $boundary); }
                    catch (\Throwable $exception) { $this->error('Geocoder tidak dapat diakses; proses dihentikan. Hasil sebelumnya tersimpan.'); report($exception); return self::FAILURE; }
                    $origin = 'photon'; $precision = $point['precision'] ?? 'street';
                    usleep(max(1000, (int) config('site-map.geocoding_delay_ms', 1100)) * 1000);
                }
                DB::table('site_locations')->updateOrInsert(['site_code' => $code], [
                    'address_hash' => $hash, 'latitude' => $point['lat'] ?? null, 'longitude' => $point['lng'] ?? null,
                    'source' => $origin, 'precision' => $point ? $precision : null, 'status' => $point ? 'resolved' : 'unresolved',
                    'city' => $point ? ($city ?: ($existing?->city ?? null)) : ($existing?->city ?? null),
                    'district' => $point['district'] ?? ($existing?->district ?? null),
                    'resolved_at' => $point ? now() : null, 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
                ]);
                $point ? $resolved++ : $pending++;
            }
            ReportDataVersion::changed();
            $this->info("Diproses: {$checked}; koordinat ditemukan: {$resolved}; belum terpetakan: {$pending}.");
            return self::SUCCESS;
        } finally { $lock->release(); }
    }

    private function geocode(string $address, string $city, ?array $boundary): ?array
    {
        // Reject locality-only matches: a city center is not a site coordinate.
        if (!$boundary) return null;
        $http = Http::acceptJson()->timeout(20);
        if (config('site-map.geocoding_token')) $http = $http->withToken(config('site-map.geocoding_token'));
        $parameters = [
            'q' => $address.', '.$city.', Indonesia', 'limit' => 5, 'countrycode' => 'ID',
            'bbox' => implode(',', AddressPlusCode::bounds($boundary)),
        ];
        $url = rtrim(config('site-map.geocoding_url'), '/').'/';
        $features = $http->get($url, $parameters)->throw()->json('features', []);
        $match = \App\Support\SiteAddressMatch::select($address, $features, $boundary);
        if ($match) return $match;
        $streetQuery = \App\Support\SiteAddressMatch::streetQuery($address, $city);
        if ($streetQuery && $streetQuery !== $parameters['q']) {
            usleep(max(1000, (int) config('site-map.geocoding_delay_ms', 1100)) * 1000);
            $parameters['q'] = $streetQuery;
            if (!preg_match('/\bNo\.\s*\d/', $streetQuery)) $parameters['layer'] = 'street';
            $features = array_merge($features, $http->get($url, $parameters)->throw()->json('features', []));
        }
        return \App\Support\SiteAddressMatch::select($address, $features, $boundary);
    }
}
