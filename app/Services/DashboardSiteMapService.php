<?php

namespace App\Services;

use App\Models\CombatSite;
use App\Models\RecurringIpas;
use App\Models\RecurringTagihanIpas;
use App\Models\SewaLahanRenewal;
use App\Models\SiteOwner;
use App\Support\CombatJarvisFilter;
use App\Support\CombatSourceDetails;
use App\Support\InfrastructureCanonicalSites;
use App\Support\InfrastructureOwnership;
use App\Support\SiteCoordinates as Coordinates;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardSiteMapService
{
    public function __construct(private readonly SiteAddressCatalog $addressCatalog) {}

    public function data(string $module, array $filters): array
    {
        $owners = SiteOwner::query()->orderBy('id')->get(['site_code', 'site_name', 'alamat', 'city', 'nop', 'site_class', 'site_owner'])->keyBy(fn ($row) => Coordinates::key($row->site_code));
        $sourcePoints = $this->sourcePoints();
        $addresses = $this->addressCatalog->rows();
        $stored = Schema::hasTable('site_locations') ? DB::table('site_locations')->where('status', 'resolved')->get()->keyBy('site_code') : collect();
        $sourceRecords = null;
        $records = match ($module) {
            'pnl' => $this->financialSites($filters),
            'feasibility' => $owners->values(),
            'infrastructure' => $this->infrastructureSites($filters, $owners, $sourceRecords),
            'electricity' => $this->electricitySites($filters),
            'recurring' => $this->recurringSites($filters),
            'equipment' => $this->equipmentSites($filters),
        };

        $sites = $records->map(function ($row) use ($owners, $stored, $sourcePoints, $addresses, $module): array {
            $code = Coordinates::key($row->site_code ?? $row->site_id ?? '');
            $owner = $owners->get($code);
            $location = $addresses->get($code);
            $details = CombatSourceDetails::flattened($row->source_details ?? null);
            $address = $this->value($row->alamat ?? null, $details['address'] ?? $details['alamat'] ?? null, $owner?->alamat, $location?->alamat);
            $city = $this->value(!SiteAddressCatalog::usable($owner?->alamat) ? $location?->city : null, $row->city ?? null, $details['kabupaten'] ?? null, $owner?->city, $location?->city);
            $saved = $stored->get($code);
            $point = Coordinates::fromDetails($row->source_details ?? null) ?? $sourcePoints->get($code);
            $source = $point ? 'Koordinat dataset' : null;
            $ownerHash = $location ? Coordinates::addressHash((string) $location->alamat, (string) $location->city) : null;
            $savedMatches = $saved && (Coordinates::isMasterSource($saved->source) || $saved->address_hash === Coordinates::addressHash($address, $city) || ($ownerHash && $saved->address_hash === $ownerHash));
            if (!$point && $saved && $saved->source !== 'dataset' && $savedMatches) {
                $point = Coordinates::point($saved->latitude, $saved->longitude);
                $source = match ($saved->source) {
                    'plus_code' => 'Plus Code alamat', 'master' => 'Master koordinat',
                    'address_coordinates' => 'Koordinat pada alamat', default => 'Geocoding alamat · '.$saved->precision,
                };
                if ($saved->source === 'photon') $source = $saved->precision === 'building' ? 'Pencocokan alamat · bangunan' : 'Perkiraan alamat · ruas jalan';
                if (Coordinates::isMasterSource($saved->source) && $saved->source !== 'master') {
                    [$kind, $period, $sourceCode] = explode(':', $saved->source);
                    $label = ['tracy_csv' => 'Tracy', 'rru_inventory' => 'RRU', 'site_cost' => 'Site Full Cost'][$kind];
                    $source = 'Koordinat dataset '.$label.' · '.substr($period, 0, 4).'-'.substr($period, 4, 2).' · '.$sourceCode;
                }
            }
            $extra = match ($module) {
                'pnl' => [
                    ['label' => 'Revenue', 'value' => $this->money($row->revenue ?? null)],
                    ['label' => 'Cost', 'value' => $this->money($row->cost ?? null)],
                    ['label' => 'Profit / Loss', 'value' => $this->money($row->profit_loss ?? null)],
                ],
                'infrastructure' => [
                    ['label' => 'Status', 'value' => $this->value($details['status'] ?? null, $row->status_dokumen ?? null)],
                    ['label' => 'Perpanjangan', 'value' => $this->value($row->status_perpanjangan ?? null)],
                ],
                'electricity' => [
                    ['label' => 'Jenis', 'value' => $row->map_source],
                    ['label' => 'Status', 'value' => $this->value($row->status_aktif_site ?? $row->status ?? null)],
                    ['label' => 'Daya', 'value' => isset($row->daya_va) || isset($row->daya) ? number_format((float) ($row->daya_va ?? $row->daya), 0, ',', '.').' VA' : '—'],
                ],
                'equipment' => [['label' => 'Equipment', 'value' => (string) $row->equipment_count]],
                'recurring' => [
                    ['label' => 'TP', 'value' => $this->value($row->tp ?? $row->site_owner ?? null)],
                    ['label' => 'Kontrak', 'value' => $this->value($row->contract_type ?? $row->contract_description ?? null)],
                    ['label' => 'Tagihan', 'value' => $this->money($row->amount ?? $row->year_amount ?? null)],
                ],
                default => [
                    ['label' => 'Site Class', 'value' => $this->value($owner?->site_class)],
                    ['label' => 'Site Owner', 'value' => $this->value($owner?->site_owner)],
                ],
            };

            return [
                'key' => $module.'|'.($row->map_source ?? '').'|'.$code,
                'id' => $code,
                'name' => $this->value($row->site_name ?? null, $owner?->site_name),
                'nop' => $this->normalizeNop($this->value($row->nop ?? null, $details['nop'] ?? null, $owner?->nop)),
                'city' => $this->value($city, $point && $savedMatches ? ($saved->city ?? null) : null),
                'district' => $this->value($details['kecamatan'] ?? null, $point && $savedMatches ? ($saved->district ?? null) : null),
                'address' => $address, 'point' => $point, 'coordinate_source' => $source,
                'fields' => $extra,
            ];
        })->filter(fn ($site) => $site['id'] !== '')->values();

        // P&L applies its own exact NOP/City rules, including the unmapped bucket.
        if ($module !== 'pnl' && filled($filters['nop'] ?? null) && strtolower($filters['nop']) !== 'all') {
            $nop = $this->normalizeNop($filters['nop']);
            $sites = $sites->where('nop', $nop)->values();
        }
        return [
            'sites' => $sites,
            'summary' => ['total' => $sites->count(), 'mapped' => $sites->whereNotNull('point')->count(), 'missing' => $sites->whereNull('point')->count()],
            'population_note' => $module === 'infrastructure' ? number_format($sites->count(), 0, ',', '.').' site unik dari '.number_format($sourceRecords, 0, ',', '.').' baris sumber. Site ID yang berulang dirangkum menjadi satu titik; kartu modul tetap mengikuti hitungan baris sumber.' : '',
            'legend' => $module === 'pnl' ? 'Warna titik mengikuti NOP · nilai finansial mengikuti periode aktif' : 'Warna titik mengikuti NOP · sorot titik untuk detail site',
        ];
    }

    public function sourcePoints(): Collection
    {
        $points = collect();
        // Latest valid row wins; archived/deleted records never supply locations.
        foreach ([CombatSite::class, SewaLahanRenewal::class] as $model) {
            foreach ($model::query()->orderBy('id')->get(['site_code', 'source_details']) as $row) {
                if ($point = Coordinates::fromDetails($row->source_details)) $points->put(Coordinates::key($row->site_code), $point);
            }
        }
        return $points;
    }

    private function financialSites(array $filters): Collection
    {
        $year = (int) ($filters['tahun'] ?? DB::table('site_monthly_metrics')->max('tahun') ?? now()->year);
        $month = ($filters['bulan'] ?? 'all') === 'all' ? null : (int) $filters['bulan'];
        $metrics = DB::table('site_monthly_metrics')->where('tahun', $year)
            ->when($month, fn ($q) => $q->where('bulan', $month))
            ->when(!$month, fn ($q) => $q->where('is_anomaly', 0))
            ->selectRaw('site_id, SUM(revenue) as revenue, SUM(cost) as cost, SUM(profit_loss) as profit_loss, COUNT(*) as metrics_count')->groupBy('site_id');
        $owners = DB::table('site_owners')->selectRaw("UPPER(TRIM(site_code)) as code, MIN(NULLIF(UPPER(TRIM(nop)), '')) as nop, MIN(NULLIF(UPPER(TRIM(city)), '')) as city")->groupByRaw('UPPER(TRIM(site_code))');
        $query = DB::table('sites as s')->leftJoinSub($metrics, 'm', 'm.site_id', '=', 's.id')
            ->leftJoinSub($owners, 'so', 'so.code', '=', DB::raw('UPPER(TRIM(s.site_id))'))
            ->select('s.site_id', 's.site_name', 'so.nop', 'so.city', 'm.revenue', 'm.cost', 'm.profit_loss', 'm.metrics_count');
        foreach (['nop', 'city'] as $field) {
            $value = trim($filters[$field] ?? '');
            if ($value !== '' && strtolower($value) !== 'all') {
                $value === PnlAnalyticsService::UNMAPPED ? $query->whereNull('so.'.$field) : $query->where('so.'.$field, Coordinates::key($value));
            }
        }
        match ($filters['status'] ?? '') {
            'Profit' => $query->where('m.profit_loss', '>', 0),
            'Loss' => $query->where('m.profit_loss', '<=', 0),
            'TidakAktif', 'Tidak Aktif' => $query->whereNull('m.metrics_count'),
            default => null,
        };
        return $query->orderBy('s.site_id')->get();
    }

    private function infrastructureSites(array $filters, Collection $owners, ?int &$sourceRecords): Collection
    {
        $scope = $filters['scope'] ?? 'all';
        $year = ($filters['tahun'] ?? 'all') === 'all' ? null : (int) $filters['tahun'];
        $sewa = SewaLahanRenewal::query()->when($year, fn ($q) => $q->where('tahun_renewal', $year))->get();
        $combat = CombatSite::query()->when($year, fn ($q) => $q->where('tahun_justi_dirnet', $year));
        if ($scope === 'combat') CombatJarvisFilter::apply($combat, $filters['available_on_jarvis'] ?? null);
        if ($scope === 'sewa' && filled($filters['ownership_scope'] ?? null)) {
            $sewa = $sewa->filter(fn ($row) => InfrastructureOwnership::bucketForRow($row, $owners) === $filters['ownership_scope']);
        }
        $rows = match ($scope) {
            'sewa' => $sewa, 'combat' => $combat->get(), default => $sewa->concat($combat->get()),
        };
        $sourceRecords = $rows->count();
        return InfrastructureCanonicalSites::fromRows($rows);
    }

    private function electricitySites(array $filters): Collection
    {
        $scope = $filters['scope'] ?? 'all';
        if ($scope !== 'inbuilding' && filled($filters['nop'] ?? null) && strtolower($filters['nop']) !== 'all') $scope = 'centralized';
        $rows = collect();
        if ($scope !== 'inbuilding') $rows = $rows->concat(DB::table('listrik_pln')->orderBy('id')->get(['site_id', 'site_name', 'alamat', 'nop', 'status_aktif_site', 'daya_va'])->unique('site_id')->map(function ($row) { $row->map_source = 'Centralized'; return $row; }));
        if ($scope !== 'centralized') $rows = $rows->concat(DB::table('listrik_inbuilding')->orderBy('id')->get(['site_id', 'site_name', 'alamat', 'status', 'daya'])->unique('site_id')->map(function ($row) { $row->map_source = 'Inbuilding'; return $row; }));
        // Preserve Centralized/Inbuilding membership, as in Electricity's total.
        return $rows;
    }

    private function recurringSites(array $filters): Collection
    {
        if (($filters['scope'] ?? '') !== 'tagihan') return RecurringIpas::query()->orderBy('id')->get(['site_code', 'site_name', 'alamat', 'site_owner', 'contract_description', 'year_amount'])->unique('site_code')->values();
        $tp = $filters['tp'] ?? 'all';
        return RecurringTagihanIpas::query()->whereHas('terms', fn ($q) => $q->when($tp !== 'all', fn ($q) => $q->where('tp', $tp)))->orderBy('site_code')->get(['site_code', 'site_name', 'tp', 'contract_type', 'amount']);
    }

    private function equipmentSites(array $filters): Collection
    {
        $query = DB::table('equipment_relocation_inventory');
        // UI sends the exact set after its existing safe/progress/PIC rules.
        if (isset($filters['sites'])) $query->whereIn('site_id', $filters['sites']);
        return $query->selectRaw('site_id, MIN(nop) as nop, COUNT(*) as equipment_count')->groupBy('site_id')->orderBy('site_id')->get();
    }

    private function normalizeNop(string $value): string
    {
        $value = preg_replace('/^NOP[\s-]*/i', '', Coordinates::key($value));
        return $value === '' || $value === '—' ? 'Tanpa NOP' : 'NOP '.$value;
    }

    private function value(mixed ...$values): string
    {
        foreach ($values as $value) if (is_scalar($value) && !in_array(mb_strtolower(trim((string) $value)), ['', '-', '—', 'n/a', '#n/a', 'tbd', 'ogc', 'unknown', 'null'], true)) return trim((string) $value);
        return '';
    }

    private function money(mixed $value): string
    {
        return $value === null ? '—' : 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
