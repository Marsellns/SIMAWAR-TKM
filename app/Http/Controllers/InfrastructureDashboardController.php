<?php

namespace App\Http\Controllers;

use App\Models\CombatSite;
use App\Models\SewaLahanRenewal;
use App\Models\SiteOwner;
use App\Support\CombatJarvisFilter;
use App\Support\CombatSourceDetails;
use App\Support\InfrastructureCanonicalSites;
use App\Support\InfrastructureMetrics;
use App\Support\InfrastructureOwnership;
use App\Support\LeaseStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class InfrastructureDashboardController extends Controller
{
    public function index(): View
    {
        $tahunList = SewaLahanRenewal::query()->whereNotNull('tahun_renewal')->distinct()->pluck('tahun_renewal')
            ->merge(CombatSite::query()->whereNotNull('tahun_justi_dirnet')->distinct()->pluck('tahun_justi_dirnet'))
            ->unique()->sortDesc()->values();

        return view('infrastruktur.index', compact('tahunList'));
    }

    public function data(\Illuminate\Http\Request $request): JsonResponse
    {
        $year = $this->selectedYear($request);
        $sewa = SewaLahanRenewal::query()->when($year, fn ($query) => $query->where('tahun_renewal', $year))->get();
        $combatQuery = CombatSite::query()->when($year, fn ($query) => $query->where('tahun_justi_dirnet', $year));
        if ($request->input('scope') === 'combat') {
            CombatJarvisFilter::apply($combatQuery, $request->query('available_on_jarvis'));
        }
        $combat = $combatQuery->get();
        $scope = $request->string('scope')->toString();
        $ownershipScope = $this->ownershipScope($request->input('ownership_scope'));
        $ownerRows = SiteOwner::query()->get();
        $ownersBySite = $ownerRows->keyBy(fn ($owner) => strtoupper(trim((string) $owner->site_code)));

        // Site Telkomsel dan Site TP adalah portfolio Sewa Lahan.  Terapkan
        // scope sebelum seluruh kartu, chart, peringatan, dan prioritas
        // dihitung agar angka yang terlihat selalu konsisten dengan tabelnya.
        if ($scope === 'sewa' && $ownershipScope !== null) {
            $sewa = $sewa
                ->filter(fn ($row): bool => $this->ownerBucket($row, $ownersBySite) === $ownershipScope)
                ->values();
        }

        $all = $scope === 'sewa' ? $sewa : ($scope === 'combat' ? $combat : $sewa->concat($combat));
        $uniqueBySite = static fn (Collection $rows): Collection => InfrastructureCanonicalSites::fromRows($rows);
        $sewaSites = $uniqueBySite($sewa);
        $combatSites = $uniqueBySite($combat);
        $allSites = $uniqueBySite($all);

        $isOperational = static fn ($row): bool => InfrastructureMetrics::operationalBucket($row) === InfrastructureMetrics::ACTIVE;
        $isOffAir = static fn ($row): bool => InfrastructureMetrics::operationalBucket($row) === InfrastructureMetrics::OFF_AIR;
        $needsAttention = static function ($row): bool {
            $status = strtolower(trim(($row->status_dokumen ?? '').' '.($row->status_perpanjangan ?? '')));
            return (bool) preg_match('/nego|pending|belum|proses|legal|perpanjang|finalisasi/', $status);
        };
        $withoutPks = static fn ($row): bool => blank($row->no_pks_baru) && blank($row->no_pks_lama);
        $riskRows = $allSites->filter(fn ($row) => $needsAttention($row) || $withoutPks($row));
        $riskValue = (float) $riskRows->sum(fn ($row) => (float) ($row->total_harga_baru ?: $row->harga_baru ?: 0));
        // Use the same normalized site lookup as the detail endpoint.  A few
        // source files contain leading/trailing spaces or different casing in
        // Site ID; filtering SiteOwner with upper-cased values would
        // make the owner chart and its drill-down disagree.
        $ownerCounts = collect(['TP' => 0, 'Telkomsel' => 0, 'Lainnya / Tidak Terpetakan' => 0]);
        foreach ($allSites as $row) {
            $bucket = $this->ownerBucket($row, $ownersBySite);
            $ownerCounts->put($bucket, $ownerCounts->get($bucket, 0) + 1);
        }

        $statusBreakdown = $allSites->groupBy(fn ($row) => $this->dimensionKey('status_dokumen', $row->status_dokumen))
            ->map(function ($rows): array {
                $name = $this->dimensionLabel('status_dokumen', $rows->first()?->status_dokumen);
                return ['name' => $name, 'y' => $rows->unique('site_code')->count(), 'filter_field' => 'status_dokumen', 'filter_value' => $name];
            })
            ->sortByDesc('y')->values()->take(10)->values();
        // The main dashboard labels this chart as Renewal (Sewa Lahan), so
        // do not mix Combat's Justi years into it.  On the module pages the
        // same chart remains scoped to that module and includes its own year
        // field (Renewal or Justi Dirnet).
        // The overview always describes Sewa Lahan renewal years.  Requests
        // without an explicit scope are the normal overview request as well.
        $renewalRows = $scope === 'combat' ? $combatSites : $sewaSites;
        $renewalYears = $renewalRows->groupBy(function ($row): string {
            $year = $row->tahun_renewal ?? $row->tahun_justi_dirnet;
            return $this->dimensionKey('tahun', $year);
        })
            ->map(function ($rows): array {
                $name = $this->dimensionLabel('tahun', $rows->first()?->tahun_renewal ?? $rows->first()?->tahun_justi_dirnet);
                return ['name' => $name, 'y' => $rows->unique('site_code')->count(), 'filter_field' => 'tahun', 'filter_value' => $name];
            })
            ->sortKeys()->values();
        $contractBySource = $scope === 'sewa'
            ? [['name' => 'Sewa Lahan', 'y' => (float) $sewaSites->sum(fn ($row) => (float) ($row->total_harga_baru ?: $row->harga_baru ?: 0)), 'filter_field' => 'source', 'filter_value' => 'Sewa Lahan']]
            : ($scope === 'combat'
                ? [['name' => 'Combat', 'y' => (float) $combatSites->sum(fn ($row) => (float) ($row->total_harga_baru ?: $row->harga_baru ?: 0)), 'filter_field' => 'source', 'filter_value' => 'Combat']]
                : [
                    ['name' => 'Sewa Lahan', 'y' => (float) $sewaSites->sum(fn ($row) => (float) ($row->total_harga_baru ?: $row->harga_baru ?: 0)), 'filter_field' => 'source', 'filter_value' => 'Sewa Lahan'],
                    ['name' => 'Combat', 'y' => (float) $combatSites->sum(fn ($row) => (float) ($row->total_harga_baru ?: $row->harga_baru ?: 0)), 'filter_field' => 'source', 'filter_value' => 'Combat'],
                ]);
        $pksStatus = $allSites->groupBy(fn ($row) => $this->dimensionKey('pks_status', $this->pksStatusValue($row)))
            ->map(function ($items): array {
                $name = $this->pksStatusValue($items->first());
                return ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'pks_status', 'filter_value' => $name];
            })
            ->sortByDesc('y')->values();
        $leaseStatus = $allSites->groupBy(fn ($row) => LeaseStatus::fromEndDate($row->end_date_baru ?? $row->end_date_lama))
            ->map(fn ($items, $name) => ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'status_masa_sewa', 'filter_value' => $name])
            ->sortBy(fn ($item) => array_search($item['name'], LeaseStatus::labels(), true))
            ->values();

        // The charts count unique Site ID values across both datasets.  The
        // overview alerts follow that same definition; module alerts remain
        // scoped to their own selected rows.
        $buildLeaseAlerts = static function ($rows, ?string $source): array {
            $alerts = [
                'expired' => [],
                'within_90' => [],
                'within_180' => [],
                'unknown' => [],
            ];

            foreach ($rows as $row) {
                $endDate = $row->end_date_baru ?? $row->end_date_lama;
                $status = LeaseStatus::fromEndDate($endDate);
                $key = match ($status) {
                    LeaseStatus::EXPIRED => 'expired',
                    LeaseStatus::WITHIN_90_DAYS => 'within_90',
                    LeaseStatus::WITHIN_180_DAYS => 'within_180',
                    LeaseStatus::NO_END_DATE => 'unknown',
                    default => null,
                };

                if ($key === null) {
                    continue;
                }

                $daysRemaining = $endDate === null
                    ? null
                    : today()->diffInDays($endDate->copy()->startOfDay(), false);
                $item = [
                    'site_code' => (string) $row->site_code,
                    'site_name' => (string) ($row->site_name ?? ''),
                    'source' => $source ?? ($row instanceof CombatSite ? 'Combat' : 'Sewa Lahan'),
                    'end_date' => $endDate?->format('Y-m-d'),
                    'days_remaining' => $daysRemaining,
                    'status' => $status,
                ];

                $alerts[$key][] = $item;
                if ($key === 'within_90') {
                    // The "≤180 hari" notification is cumulative, while the
                    // chart category remains split into 90 and 180 days.
                    $alerts['within_180'][] = $item;
                }
            }

            foreach ($alerts as &$items) {
                usort($items, static function (array $a, array $b): int {
                    return (($a['days_remaining'] ?? PHP_INT_MAX) <=> ($b['days_remaining'] ?? PHP_INT_MAX));
                });
            }

            return $alerts;
        };
        $alertRows = [
            'expired' => [],
            'within_90' => [],
            'within_180' => [],
            'unknown' => [],
        ];
        $alertSources = $scope === 'sewa'
            ? [['rows' => $sewaSites, 'source' => 'Sewa Lahan']]
            : ($scope === 'combat'
                ? [['rows' => $combatSites, 'source' => 'Combat']]
                : [['rows' => $allSites, 'source' => null]]);
        foreach ($alertSources as $alertSource) {
            $sourceAlerts = $buildLeaseAlerts($alertSource['rows'], $alertSource['source']);
            foreach ($sourceAlerts as $key => $items) {
                $alertRows[$key] = array_merge($alertRows[$key], $items);
            }
        }
        $renewalStatus = $allSites->groupBy(fn ($row) => $this->dimensionKey('status_perpanjangan', $row->status_perpanjangan))
            ->map(function ($items): array {
                $name = $this->dimensionLabel('status_perpanjangan', $items->first()?->status_perpanjangan);
                return ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'status_perpanjangan', 'filter_value' => $name];
            })
            ->sortByDesc('y')->values();
        $airStatus = $all->groupBy(fn ($row) => $this->dimensionKey('status', $this->airStatusValue($row)))
            ->map(function ($items): array {
                $name = $this->airStatusValue($items->first());
                return ['name' => $name, 'y' => $items->count(), 'filter_field' => 'status', 'filter_value' => $name];
            })
            ->sortByDesc('y')->values();
        $nop = $allSites->groupBy(fn ($row) => $this->nopValue($row))
            ->map(fn ($items, $name) => ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'nop', 'filter_value' => $name])
            ->sortByDesc('y')->values();
        $vendor = $allSites->groupBy(fn ($row) => $this->dimensionKey('vendor', $this->vendorValue($row)))
            ->map(function ($items): array {
                $name = $this->vendorValue($items->first());
                return ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'vendor', 'filter_value' => $name];
            })
            ->sortByDesc('y')->values();
        $health = $allSites->groupBy(fn ($row) => LeaseStatus::fromEndDate($row->end_date_baru ?? $row->end_date_lama))
            ->map(fn ($items, $name) => ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'health', 'filter_value' => $name])
            ->sortBy(fn ($item) => array_search($item['name'], LeaseStatus::labels(), true))->values();
        $pipeline = $allSites->groupBy(fn ($row) => $this->pipelineBucket($row))
            ->map(fn ($items, $name) => ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'pipeline', 'filter_value' => $name])
            ->sortByDesc('y')->values();
        $aging = $allSites->groupBy(fn ($row) => $this->pipelineBucket($row))
            ->map(function ($items, $name): array {
                $values = $items->map(fn ($row): ?int => InfrastructureMetrics::processAgingDays($row))
                    ->filter(fn ($value) => $value !== null);
                return [
                    'name' => $name,
                    // Missing process dates are intentionally null, not zero.
                    'y' => $values->isNotEmpty() ? (int) round($values->avg()) : null,
                    'count' => $items->unique('site_code')->count(),
                    'dated_count' => $values->count(),
                    'filter_field' => 'pipeline',
                    'filter_value' => $name,
                ];
            })->filter(fn ($item) => $item['count'] > 0)->sortByDesc(fn ($item) => $item['y'] ?? -1)->values();
        $geography = $allSites->groupBy(fn ($row) => $this->dimensionKey('geography', $this->geographyBucket($row)))
            ->map(function ($items): array {
                $name = $this->geographyBucket($items->first());
                return ['name' => $name, 'y' => $items->unique('site_code')->count(), 'filter_field' => 'geography', 'filter_value' => $name];
            })
            ->reject(fn ($item) => $this->dimensionKey('geography', $item['name']) === $this->dimensionKey('geography', 'Tidak Diisi'))
            ->sortByDesc('y')->values()->take(12)->values();
        $priorityRows = $allSites->filter(fn ($row) => $needsAttention($row) || $withoutPks($row) || LeaseStatus::fromEndDate($row->end_date_baru ?? $row->end_date_lama) !== LeaseStatus::SAFE);
        $priorityCount = $priorityRows->count();
        $priority = $priorityRows
            ->map(function ($row): array {
                $endDate = $row->end_date_baru ?? $row->end_date_lama;
                $status = LeaseStatus::fromEndDate($endDate);
                return [
                    'site_code' => (string) $row->site_code,
                    'site_name' => (string) ($row->site_name ?? ''),
                    'status' => $status,
                    'pipeline' => $this->pipelineBucket($row),
                    'filter_field' => 'site_code',
                    'filter_value' => (string) $row->site_code,
                ];
            })->take(12)->values();

        // Keep source composition aligned with the active module.  Showing
        // Combat inside the Sewa Lahan page (and vice versa) made a scoped
        // dashboard look as though it still represented the full portfolio.
        $sourceSeries = match ($scope) {
            'sewa' => collect([
                ['name' => 'Sewa Lahan', 'y' => $sewaSites->count(), 'filter_field' => 'source', 'filter_value' => 'Sewa Lahan'],
            ]),
            'combat' => collect([
                ['name' => 'Combat', 'y' => $combatSites->count(), 'filter_field' => 'source', 'filter_value' => 'Combat'],
            ]),
            default => collect([
                ['name' => 'Sewa Lahan', 'y' => $sewaSites->count(), 'filter_field' => 'source', 'filter_value' => 'Sewa Lahan'],
                ['name' => 'Combat', 'y' => $combatSites->count(), 'filter_field' => 'source', 'filter_value' => 'Combat'],
            ]),
        };

        return response()->json([
            'cards' => [
                // Summary cards and the status chart count database rows,
                // including repeated Site IDs in either source table.
                'total' => $all->count(),
                'unique_sites' => $allSites->count(),
                'active' => $all->filter($isOperational)->count(),
                'contract' => $allSites->filter($needsAttention)->count(),
                'without_pks' => $all->filter($withoutPks)->count(),
                'off_air' => $all->filter($isOffAir)->count(),
                'unknown_status' => $all->reject($isOperational)->reject($isOffAir)->count(),
                'risk_value' => $riskValue,
            ],
            // A pie composition must be mutually exclusive.  Contract and
            // PKS risks remain KPI cards and no longer inflate this series.
            'status' => $airStatus,
            'sources' => $sourceSeries,
            'owners' => $ownerCounts->map(fn ($count, $name) => ['name' => $name, 'y' => $count, 'filter_field' => 'ownership', 'filter_value' => $name])->values(),
            'status_breakdown' => $statusBreakdown,
            'renewal_years' => $renewalYears,
            'contract_by_source' => $contractBySource,
            'pks_status' => $pksStatus,
            'lease_status' => $leaseStatus,
            'renewal_status' => $renewalStatus,
            'air_status' => $airStatus,
            'nop' => $nop,
            'vendor' => $vendor,
            'health' => $health,
            'pipeline' => $pipeline,
            'aging' => $aging,
            'geography' => $geography,
            'priority' => $priority,
            'priority_count' => $priorityCount,
            'alerts' => $alertRows,
            'dataset_rows' => [
                'sewa_lahan' => $sewa->count(),
                'combat' => $combat->count(),
                'sewa_sites' => $sewaSites->count(),
                'combat_sites' => $combatSites->count(),
                'total_records' => $all->count(),
                'total_sites' => $allSites->count(),
            ],
        ]);
    }

    /**
     * Row-level drill-down used by chart and notification pop-ups. Summary
     * cards and status charts show all matching database rows; other charts
     * retain their canonical Site ID aggregation.
     */
    public function details(Request $request): JsonResponse
    {
        $year = $this->selectedYear($request);
        $sewa = SewaLahanRenewal::query()->when($year, fn ($query) => $query->where('tahun_renewal', $year))->get();
        $combatQuery = CombatSite::query()->when($year, fn ($query) => $query->where('tahun_justi_dirnet', $year));
        if ($request->input('scope') === 'combat') {
            CombatJarvisFilter::apply($combatQuery, $request->query('available_on_jarvis'));
        }
        $combat = $combatQuery->get();
        $scope = $request->string('scope')->toString();
        $records = collect();

        if ($scope !== 'combat') {
            foreach ($sewa as $row) {
                $records->push(['source' => 'Sewa Lahan', 'row' => $row]);
            }
        }
        if ($scope !== 'sewa') {
            foreach ($combat as $row) {
                $records->push(['source' => 'Combat', 'row' => $row]);
            }
        }

        $ownersBySite = SiteOwner::query()
            ->get()
            ->keyBy(fn ($owner) => strtoupper(trim((string) $owner->site_code)));
        $filterField = (string) $request->input('filter_field');
        $filterValue = (string) $request->input('filter_value');
        $ownershipScope = $this->ownershipScope($request->input('ownership_scope'));

        if ($ownershipScope !== null) {
            $records = $records->filter(
                fn (array $record): bool => $this->ownerBucket($record['row'], $ownersBySite) === $ownershipScope
            )->values();
        }

        // Match the row counts shown by summary cards and status charts.
        $rowLevelFilter = $filterField === 'all_records'
            || $filterField === 'status'
            || ($filterField === 'summary_status' && in_array($filterValue, ['active', 'off_air', 'without_pks'], true));
        if (! $rowLevelFilter) {
            $records = $this->uniqueDetailRecords($records, $filterField !== 'source');
        }

        if ($filterField === 'source') {
            $records = $records->filter(fn (array $record): bool => $record['source'] === $filterValue);
        }

        $records = $records->filter(function (array $record) use ($filterField, $filterValue, $ownersBySite): bool {
            $row = $record['row'];
            $statusText = strtolower(trim(($row->status_dokumen ?? '') . ' ' . ($row->status_perpanjangan ?? '')));
            $endDate = $row->end_date_baru ?? $row->end_date_lama;
            $statusDokumen = $this->dimensionLabel('status_dokumen', $row->status_dokumen);
            $statusPerpanjangan = $this->dimensionLabel('status_perpanjangan', $row->status_perpanjangan);
            $year = $row->tahun_renewal ?? $row->tahun_justi_dirnet;
            $airStatus = $this->airStatusValue($row);

            if ($filterField === '' || $filterField === 'source' || $filterField === 'all_records') {
                return true;
            }

            return match ($filterField) {
                'site_code' => strtoupper(trim((string) $row->site_code)) === strtoupper(trim($filterValue)),
                'tahun' => $this->sameDimension('tahun', $year, $filterValue),
                'status_dokumen' => $this->sameDimension('status_dokumen', $statusDokumen, $filterValue),
                'status_perpanjangan' => $this->sameDimension('status_perpanjangan', $statusPerpanjangan, $filterValue),
                'status' => $this->sameDimension('status', $airStatus, $filterValue),
                'pks_status' => $this->sameDimension('pks_status', $this->pksStatusValue($row), $filterValue),
                'status_masa_sewa' => LeaseStatus::fromEndDate($endDate) === $filterValue,
                'health' => LeaseStatus::fromEndDate($endDate) === $filterValue,
                'lease_window' => $endDate !== null
                    && today()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false) >= 0
                    && today()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false) <= 180,
                'summary_status' => match ($filterValue) {
                    'active' => $airStatus === InfrastructureMetrics::ACTIVE,
                    'contract' => (bool) preg_match('/nego|pending|belum|proses|legal|perpanjang|finalisasi/', $statusText),
                    'risk' => (bool) preg_match('/nego|pending|belum|proses|legal|perpanjang|finalisasi/', $statusText)
                        || (blank($row->no_pks_baru) && blank($row->no_pks_lama)),
                    'off_air' => $airStatus === InfrastructureMetrics::OFF_AIR,
                    'without_pks' => blank($row->no_pks_baru) && blank($row->no_pks_lama),
                    default => false,
                },
                // source_details is intentionally sparse: revenue-only rows
                // do not have every master column.  Always read optional
                // dimensions through the same fallback used by the charts.
                'nop' => $this->sameDimension('nop', $this->nopValue($row), $filterValue),
                'vendor' => $this->sameDimension('vendor', $this->vendorValue($row), $filterValue),
                'pipeline' => $this->pipelineBucket($row) === $filterValue,
                'geography' => $this->sameDimension('geography', $this->geographyBucket($row), $filterValue),
                'ownership' => $this->ownerBucket($row, $ownersBySite) === $filterValue,
                'priority' => $this->isPriority($row),
                default => true,
            };
        })->values();

        $data = $records->map(function (array $record) use ($ownersBySite): array {
            $row = $record['row'];
            $details = $this->rowDetails($row);
            $endDate = $row->end_date_baru ?? $row->end_date_lama;
            $daysRemaining = $endDate === null
                ? null
                : today()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false);

            return [
                'id' => $row->id,
                'source' => $record['source'],
                'site_code' => $row->site_code,
                'site_name' => $row->site_name,
                'tahun' => $row->tahun_renewal ?? $row->tahun_justi_dirnet,
                'status_site' => $this->dimensionLabel('status', $details['status'] ?? null),
                'status_dokumen' => $row->status_dokumen,
                'status_perpanjangan' => $row->status_perpanjangan,
                'status_masa_sewa' => LeaseStatus::fromEndDate($endDate),
                'end_date' => $endDate?->format('Y-m-d'),
                'days_remaining' => $daysRemaining,
                'owner' => $this->ownerBucket($row, $ownersBySite),
                'nop' => $this->nopValue($row),
                'vendor' => $this->vendorValue($row),
                'no_pks_baru' => $row->no_pks_baru,
                'total_harga_baru' => $row->total_harga_baru,
                'process_started_at' => InfrastructureMetrics::processStartDate($row)?->toDateString(),
                'process_aging_days' => InfrastructureMetrics::processAgingDays($row),
                'performance' => InfrastructureMetrics::sitePerformance($row),
                // source_details is an import snapshot.  Keep useful
                // supplementary values, but do not expose Excel formulas or
                // raw serial-date cells in the detail modal.
                'source_details' => $this->displaySourceDetails($details),
            ];
        })->values();

        return response()->json([
            'count' => $data->count(),
            'source_counts' => $data->groupBy('source')->map->count()->all(),
            'data' => $data,
        ]);
    }

    private function selectedYear(Request $request): ?int
    {
        $value = $request->input('tahun');
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        abort_unless(is_scalar($value) && preg_match('/^(19|20|21)\d{2}$/', (string) $value), 422, 'Tahun tidak valid.');

        return (int) $value;
    }

    /**
     * Return a stable display value for dimensions coming from Excel.
     * Dataset rows are not guaranteed to contain every source column, and
     * Excel cells may contain inconsistent whitespace/casing.
     */
    private function dimensionLabel(string $field, mixed $value, string $fallback = 'Tidak Diisi'): string
    {
        $label = trim(preg_replace('/\s+/u', ' ', (string) ($value ?? '')) ?? '');
        if ($label === '' || $label === '-') {
            return $fallback;
        }

        if ($field !== 'nop') {
            return $label;
        }

        // Treat "BOGOR", "NOP BOGOR" and "NOP-BOGOR" as one NOP.  The
        // chart shows the canonical form and the detail endpoint accepts all
        // source variants.
        $nop = preg_replace('/^NOP[\s-]*/i', '', $label) ?? $label;
        $nop = strtoupper(trim($nop));

        return $nop === '' || $nop === '-' || $nop === strtoupper($fallback)
            ? $fallback
            : 'NOP '.$nop;
    }

    private function dimensionKey(string $field, mixed $value): string
    {
        return mb_strtolower($this->dimensionLabel($field, $value), 'UTF-8');
    }

    private function sameDimension(string $field, mixed $left, mixed $right): bool
    {
        return $this->dimensionKey($field, $left) === $this->dimensionKey($field, $right);
    }

    private function rowDetails($row): array
    {
        return CombatSourceDetails::flattened($row->source_details);
    }

    private function nopValue($row): string
    {
        $details = $this->rowDetails($row);

        return $this->dimensionLabel('nop', $details['nop'] ?? null);
    }

    private function vendorValue($row): string
    {
        $details = $this->rowDetails($row);
        $value = filled($details['vendor'] ?? null)
            ? $details['vendor']
            : ($details['tp'] ?? null);

        return $this->dimensionLabel('vendor', $value);
    }

    private function pksStatusValue($row): string
    {
        // The imported workbook uses formulas in `pks_status` (for example,
        // `=IF(LEN(TRIM(...))`).  A formula is source metadata, not a status.
        // Derive the status from the mapped PKS columns so the chart, filter,
        // and detail modal all return a human-readable, stable value.
        return InfrastructureMetrics::pksStatusLabel($row);
    }

    private function airStatusValue($row): string
    {
        return InfrastructureMetrics::operationalBucket($row);
    }

    /**
     * Hide raw import artefacts from the UI detail view.  The normalized model
     * columns above are the canonical values for dates, PKS, status, NOP, and
     * price; the remaining fields are supplementary information only.
     */
    private function displaySourceDetails(array $details): array
    {
        $hiddenFields = [
            'site_id', 'site_name', 'status', 'status_dokumen', 'status_perpanjangan',
            'pks_status', 'status_masa_sewa', 'status_masa_sewa2',
            'nop', 'vendor', 'tp', 'ownership',
            'nomor_pks_baru', 'nomor_pks_existing',
            'total_harga_baru', 'total_harga_existing',
            'periode_awal_baru', 'periode_akhir_baru',
            'periode_awal_existing', 'periode_akhir_existing',
        ];

        return collect($details)
            ->reject(function (mixed $value, string $key) use ($hiddenFields): bool {
                if (in_array($key, $hiddenFields, true) || $value === null || $value === '') {
                    return true;
                }

                // Formulas are currently stored literally by the source
                // workbook.  Showing them is confusing and can make a valid
                // notification look like an application error.
                if (is_string($value) && str_starts_with(ltrim($value), '=')) {
                    return true;
                }

                // Revenue/cost/PnL is already summarized in its own chart.
                // Hiding raw monthly metrics keeps alert drill-downs focused
                // on operational site information.
                if (preg_match('/^(rev|cost|pnl|revenue|margin|profit|tracy|payload)|_202[0-9]|-(25|26)|(jan|feb|mar|apr|mei|may|jun|jul|aug|agu|sep|okt|oct|nov|des|dec)/i', $key)) {
                    return true;
                }

                // The import already parses these into the canonical date
                // fields.  Suppress the raw Excel serial values in the modal.
                return (bool) preg_match('/(^|_)(tgl|tanggal|date|start|end|periode_(awal|akhir))(_|$)/i', $key);
            })
            ->all();
    }

    private function uniqueDetailRecords(Collection $records, bool $global = false): Collection
    {
        return $records
            ->sort(static fn (array $left, array $right): int =>
                (InfrastructureCanonicalSites::score($right['row']) <=> InfrastructureCanonicalSites::score($left['row']))
                ?: ((int) $right['row']->id <=> (int) $left['row']->id))
            ->unique(fn (array $record): string => ($global ? '' : $record['source'].':') . strtoupper(trim((string) $record['row']->site_code)))
            ->values();
    }

    private function ownerBucket($row, Collection $ownersBySite): string
    {
        return InfrastructureOwnership::bucketForRow($row, $ownersBySite);
    }

    /**
     * Only the two supported portfolio scopes may narrow dashboard data.
     * The explicit whitelist prevents arbitrary request values from changing
     * the interpretation of the ownership classification.
     */
    private function ownershipScope(mixed $value): ?string
    {
        return match (mb_strtolower(trim((string) $value), 'UTF-8')) {
            'telkomsel' => 'Telkomsel',
            'tp' => 'TP',
            default => null,
        };
    }

    private function pipelineBucket($row): string
    {
        return InfrastructureMetrics::pipelineBucket($row);
    }

    private function geographyBucket($row): string
    {
        return InfrastructureMetrics::geographyBucket($row);
    }

    private function isPriority($row): bool
    {
        $status = mb_strtolower(trim(($row->status_dokumen ?? '').' '.($row->status_perpanjangan ?? '')), 'UTF-8');
        $needsAttention = (bool) preg_match('/nego|pending|belum|proses|legal|perpanjang|finalisasi/u', $status);
        $withoutPks = blank($row->no_pks_baru) && blank($row->no_pks_lama);
        $leaseNeedsAttention = LeaseStatus::fromEndDate($row->end_date_baru ?? $row->end_date_lama) !== LeaseStatus::SAFE;

        return $needsAttention || $withoutPks || $leaseNeedsAttention;
    }

}
