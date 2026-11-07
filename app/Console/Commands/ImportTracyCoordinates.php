<?php

namespace App\Console\Commands;

use App\Support\ReportDataVersion;
use App\Support\SiteCoordinates;
use App\Support\TracyCoordinateReader;
use App\Support\AddressPlusCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ImportTracyCoordinates extends Command
{
    protected $signature = 'sites:import-tracy-coordinates {file : CSV sumber koordinat} {--source=tracy_csv : tracy_csv, rru_inventory, atau site_cost} {--dry-run : Periksa kecocokan tanpa menyimpan}';
    protected $description = 'Lengkapi lokasi site dari koordinat CSV Tracy, tanpa mengubah master modul';

    public function handle(TracyCoordinateReader $reader): int
    {
        if (!Schema::hasTable('site_locations')) { $this->error('Jalankan migrasi lokasi terlebih dahulu.'); return self::FAILURE; }
        $lock = Cache::lock('site-locations.resolve', 86400);
        if (!$lock->get()) { $this->error('Proses lokasi lain masih berjalan.'); return self::FAILURE; }
        try {
            $sourceKind = (string) $this->option('source');
            if (!in_array($sourceKind, ['tracy_csv', 'rru_inventory', 'site_cost'], true)) throw new \InvalidArgumentException('Sumber koordinat tidak dikenal.');
            $data = $reader->read($this->argument('file'));
            $boundaries = collect(json_decode(file_get_contents(resource_path('data/site-map-localities.geojson')), true)['features'])
                ->keyBy(fn ($feature) => SiteCoordinates::key($feature['properties']['city']));
            $source = $data['sites'];
            $owners = DB::table('site_owners')->get(['site_code', 'site_name', 'alamat', 'city'])->keyBy(fn ($row) => SiteCoordinates::key($row->site_code));
            $known = $owners->mapWithKeys(fn ($row, $key) => [$key => true])->all();
            foreach ([
                'sites' => 'site_id', 'listrik_pln' => 'site_id', 'listrik_inbuilding' => 'site_id',
                'equipment_relocation_inventory' => 'site_id', 'recurring_ipas' => 'site_code',
                'recurring_tagihan_ipas' => 'site_code', 'sewa_lahan_renewals' => 'site_code', 'combat_sites' => 'site_code',
            ] as $table => $column) {
                foreach (DB::table($table)->distinct()->pluck($column) as $code) {
                    $key = SiteCoordinates::key($code);
                    if ($key !== '') $known[$key] = true;
                }
            }
            $stored = DB::table('site_locations')->get()->keyBy('site_code');
            $matched = 0; $saved = 0; $preserved = 0; $aliases = 0; $enriched = 0; $regionConflicts = 0;
            $locationWrites = []; $administrativeWrites = [];
            foreach ($known as $code => $_) {
                $owner = $owners->get($code);
                $record = $source[$code] ?? null;
                $alias = false;
                // Explicit current/former identity in legacy infrastructure rows.
                if (!$record && preg_match('/^([A-Z]{3}\d{3})\s+EX\s+[A-Z]{3}\d{3}$/', $code, $identity)) {
                    $record = $source[$identity[1]] ?? null;
                    $alias = $record !== null;
                }
                // Some EPG records explicitly start with their physical Site ID.
                // Require that exact ID and the same municipality; never fuzzy-match names.
                if (!$record && $owner && preg_match('/^([A-Z]{3}\d{3})(?:\s|[-_])/i', trim((string) $owner->site_name), $match)) {
                    $candidate = $source[strtoupper($match[1])] ?? null;
                    if ($candidate && $this->cityKey($candidate['city']) === $this->cityKey((string) $owner->city)) { $record = $candidate; $alias = true; }
                }
                if (!$record) continue;
                $addressHash = $owner ? SiteCoordinates::addressHash((string) $owner->alamat, (string) $owner->city) : null;
                if ($sourceKind === 'rru_inventory' && $owner) {
                    $boundary = $boundaries->get($this->cityKey((string) $owner->city));
                    if ($boundary && !AddressPlusCode::contains($boundary['geometry'], $record['point'])) { $regionConflicts++; continue; }
                }
                $matched++;
                $existing = $stored->get($code);
                if ($existing?->status === 'resolved') {
                    // Preserve earlier dataset/Plus Code/manual results. A newer Tracy
                    // period may update an earlier Tracy import for the same source ID.
                    if (!preg_match('/^([a-z_]+):(\d{6}):([A-Z0-9_-]+)$/', $existing->source, $previous)
                        || $previous[1] !== $sourceKind || $previous[3] !== $record['site_code'] || $previous[2] >= $record['period']) {
                        if ((blank($existing->city ?? null) && $record['city'] !== '') || (blank($existing->district ?? null) && $record['district'] !== '')) {
                            $metadata = (array) $existing;
                            unset($metadata['id']);
                            $metadata['city'] = filled($existing->city ?? null) ? $existing->city : ($record['city'] ?: null);
                            $metadata['district'] = filled($existing->district ?? null) ? $existing->district : ($record['district'] ?: null);
                            $administrativeWrites[] = $metadata;
                            $enriched++;
                        }
                        $preserved++; continue;
                    }
                }
                if (!$this->option('dry-run')) {
                    $locationWrites[] = [
                        'site_code' => $code,
                        'address_hash' => $addressHash,
                        'latitude' => $record['point']['lat'], 'longitude' => $record['point']['lng'],
                        'source' => $sourceKind.':'.$record['period'].':'.$record['site_code'], 'precision' => 'site', 'status' => 'resolved',
                        'city' => $record['city'] ?: null, 'district' => $record['district'] ?: null,
                        'resolved_at' => now(), 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
                    ];
                }
                $saved++;
                if ($alias) $aliases++;
            }
            if (!$this->option('dry-run')) {
                foreach (array_chunk($locationWrites, 500) as $chunk) DB::table('site_locations')->upsert($chunk, ['site_code'], ['address_hash', 'latitude', 'longitude', 'source', 'precision', 'status', 'city', 'district', 'resolved_at', 'updated_at']);
                foreach (array_chunk($administrativeWrites, 500) as $chunk) DB::table('site_locations')->upsert($chunk, ['site_code'], ['city', 'district']);
                if ($saved || $enriched) ReportDataVersion::changed();
            }
            $this->info(($this->option('dry-run') ? 'DRY RUN · ' : '')."Baris CSV: {$data['rows']}; site sumber valid: ".count($source)."; cocok di aplikasi: {$matched}; lokasi baru/diperbarui: {$saved}; wilayah dilengkapi: {$enriched}; alias ID eksplisit: {$aliases}; hasil lama dipertahankan: {$preserved}; konflik sumber dilewati: {$data['ambiguous_sites']}; beda wilayah: {$regionConflicts}.");
            return self::SUCCESS;
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        } finally { $lock->release(); }
    }

    private function cityKey(string $city): string
    {
        return SiteCoordinates::key(preg_replace('/^(KABUPATEN|KAB\.?)\s+/i', '', trim($city)));
    }
}
