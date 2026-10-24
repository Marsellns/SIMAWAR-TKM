<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\Site;
use App\Services\PnlAnalyticsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class PnlExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function __construct(
        private readonly ?int $bulan,
        private readonly int $tahun,
        private readonly ?string $status = null,
        private readonly ?string $nop = null,
        private readonly ?string $city = null,
        private readonly ?string $siteCode = null,
    ) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function query(): Builder
    {
        $query = $this->baseQuery();
        $query->addSelect('so.city as city');
        PnlAnalyticsService::applyDrilldownFilters($query, $this->city, $this->siteCode);

        return $query;
    }

    private function baseQuery(): Builder
    {
        $bulan = $this->bulan;
        $tahun = $this->tahun;

        if ($this->status === 'TidakAktif' || $this->status === 'Tidak Aktif') {
            return Site::query()
                ->inactiveIn($bulan ?? 0, $tahun)
                ->leftJoin('site_owners as so', function ($join) {
                    $join->on(DB::raw('UPPER(TRIM(so.site_code))'), '=', DB::raw('UPPER(TRIM(sites.site_id))'));
                })
                ->when($this->nop !== null, fn ($query) => $this->nop === PnlAnalyticsService::UNMAPPED ? $query->whereRaw("COALESCE(TRIM(so.nop), '') = ''") : $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($this->nop)]))
                ->select('sites.*', 'so.nop as nop')
                ->orderBy('sites.site_id');
        }

        if ($this->status === 'Profit') {
            if ($bulan === null) {
                $metricsBySite = DB::table('site_monthly_metrics')
                    ->where('tahun', $tahun)
                    ->where('is_anomaly', 0)
                    ->selectRaw('site_id, SUM(revenue) as revenue, SUM(cost) as cost, SUM(profit_loss) as profit_loss')
                    ->groupBy('site_id');

                return Site::query()
                    ->joinSub($metricsBySite, 'm', 'm.site_id', '=', 'sites.id')
                    ->where('m.profit_loss', '>', 0)
                    ->leftJoin('site_owners as so', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(so.site_code))'), '=', DB::raw('UPPER(TRIM(sites.site_id))'));
                    })
                    ->when($this->nop !== null, fn ($query) => $this->nop === PnlAnalyticsService::UNMAPPED ? $query->whereRaw("COALESCE(TRIM(so.nop), '') = ''") : $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($this->nop)]))
                    ->select(
                        'sites.id',
                        'sites.site_id',
                        'sites.site_name',
                        'sites.region_id',
                        'so.nop as nop',
                        'm.revenue',
                        'm.cost',
                        'm.profit_loss',
                        DB::raw('NULL as bulan')
                    )
                    ->orderBy('sites.site_id');
            }

            return Site::query()
                ->join('site_monthly_metrics as m', function ($join) use ($bulan, $tahun) {
                    $join->on('m.site_id', '=', 'sites.id')
                        ->when($bulan !== null, fn ($j) => $j->where('m.bulan', $bulan))
                        ->where('m.tahun', $tahun);
                })
                ->where('m.profit_loss', '>', 0)
                ->leftJoin('site_owners as so', function ($join) {
                    $join->on(DB::raw('UPPER(TRIM(so.site_code))'), '=', DB::raw('UPPER(TRIM(sites.site_id))'));
                })
                ->when($this->nop !== null, fn ($query) => $this->nop === PnlAnalyticsService::UNMAPPED ? $query->whereRaw("COALESCE(TRIM(so.nop), '') = ''") : $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($this->nop)]))
                ->select(
                    'sites.id',
                    'sites.site_id',
                    'sites.site_name',
                    'sites.region_id',
                    'so.nop as nop',
                    'm.revenue',
                    'm.cost',
                    'm.profit_loss',
                    'm.bulan'
                )
                ->orderBy('sites.site_id');
        }

        if ($this->status === 'Loss') {
            if ($bulan === null) {
                $metricsBySite = DB::table('site_monthly_metrics')
                    ->where('tahun', $tahun)
                    ->where('is_anomaly', 0)
                    ->selectRaw('site_id, SUM(revenue) as revenue, SUM(cost) as cost, SUM(profit_loss) as profit_loss')
                    ->groupBy('site_id');

                return Site::query()
                    ->leftJoinSub($metricsBySite, 'm', 'm.site_id', '=', 'sites.id')
                    ->where('m.profit_loss', '<=', 0)
                    ->leftJoin('site_owners as so', function ($join) {
                        $join->on(DB::raw('UPPER(TRIM(so.site_code))'), '=', DB::raw('UPPER(TRIM(sites.site_id))'));
                    })
                    ->when($this->nop !== null, fn ($query) => $this->nop === PnlAnalyticsService::UNMAPPED ? $query->whereRaw("COALESCE(TRIM(so.nop), '') = ''") : $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($this->nop)]))
                    ->select(
                        'sites.id',
                        'sites.site_id',
                        'sites.site_name',
                        'sites.region_id',
                        'so.nop as nop',
                        'm.revenue',
                        'm.cost',
                        'm.profit_loss',
                        DB::raw('NULL as bulan')
                    )
                    ->orderBy('sites.site_id');
            }

            return Site::query()
                ->join('site_monthly_metrics as m', function ($join) use ($bulan, $tahun) {
                    $join->on('m.site_id', '=', 'sites.id')
                        ->when($bulan !== null, fn ($j) => $j->where('m.bulan', $bulan))
                        ->where('m.tahun', $tahun);
                })
                ->where('m.profit_loss', '<=', 0)
                ->leftJoin('site_owners as so', function ($join) {
                    $join->on(DB::raw('UPPER(TRIM(so.site_code))'), '=', DB::raw('UPPER(TRIM(sites.site_id))'));
                })
                ->when($this->nop !== null, fn ($query) => $this->nop === PnlAnalyticsService::UNMAPPED ? $query->whereRaw("COALESCE(TRIM(so.nop), '') = ''") : $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($this->nop)]))
                ->select(
                    'sites.id',
                    'sites.site_id',
                    'sites.site_name',
                    'sites.region_id',
                    'so.nop as nop',
                    'm.revenue',
                    'm.cost',
                    'm.profit_loss',
                    'm.bulan'
                )
                ->orderBy('sites.site_id');
        }

        // "Semua Status": tampilkan seluruh site (master Dapot), satu baris per site
        $query = Site::query();
        if ($bulan === null) {
            $metricsBySite = DB::table('site_monthly_metrics')
                ->where('tahun', $tahun)
                ->where('is_anomaly', 0)
                ->selectRaw('site_id, SUM(revenue) as revenue, SUM(cost) as cost, SUM(profit_loss) as profit_loss')
                ->groupBy('site_id');

            $query->leftJoinSub($metricsBySite, 'm', 'm.site_id', '=', 'sites.id');
        } else {
            $query->leftJoin('site_monthly_metrics as m', function ($join) use ($bulan, $tahun) {
                $join->on('m.site_id', '=', 'sites.id')
                    ->where('m.bulan', $bulan)
                    ->where('m.tahun', $tahun);
            });
        }

        return $query
            ->leftJoin('site_owners as so', function ($join) {
                $join->on(DB::raw('UPPER(TRIM(so.site_code))'), '=', DB::raw('UPPER(TRIM(sites.site_id))'));
            })
            ->when($this->nop !== null, fn ($query) => $this->nop === PnlAnalyticsService::UNMAPPED ? $query->whereRaw("COALESCE(TRIM(so.nop), '') = ''") : $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($this->nop)]))
            ->select(
                'sites.id',
                'sites.site_id',
                'sites.site_name',
                'sites.region_id',
                'so.nop as nop',
                'm.revenue',
                'm.cost',
                'm.profit_loss',
                DB::raw('NULL as bulan')
            )
            ->orderBy('sites.site_id');
    }

    public function headings(): array
    {
        return [
            'No',
            'Site ID',
            'Site Name',
            'NOP',
            'Kota',
            'Revenue (Rp)',
            'Cost (Rp)',
            'Profit/Loss (Rp)',
            'Status',
            'Bulan',
            'Tahun',
        ];
    }

    /** @param Site $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        $pnl = $row->profit_loss !== null ? (float) $row->profit_loss : null;
        $status = 'Tidak Aktif';
        if ($pnl !== null) {
            $status = $pnl > 0 ? 'Profit' : 'Loss';
        }

        return [
            $no,
            $row->site_id,
            $row->site_name ?? '-',
            $row->nop ?? '-',
            $row->city ?? '-',
            $row->revenue !== null ? (float) $row->revenue : 0,
            $row->cost !== null ? (float) $row->cost : 0,
            $pnl ?? 0,
            $status,
            $row->bulan ?? $this->bulan ?? 'Semua',
            $this->tahun,
        ];
    }
}
