<?php

namespace App\Http\Controllers;

use App\Models\Read\AnomaliTagihanInbuilding;
use App\Models\Read\AnomaliTagihanPln;
use App\Models\Bapss;
use App\Models\CombatSite;
use App\Models\JaknetContract;
use App\Models\ListrikAll;
use App\Models\ListrikInbuilding;
use App\Models\ListrikPln;
use App\Models\PoHq;
use App\Models\Read\PaymentPlnMasterMonthly;
use App\Models\Read\PaymentPln;
use App\Models\RecurringIpas;
use App\Models\SewaLahanRenewal;
use App\Models\Site;
use App\Models\SiteMonthlyMetric;
use App\Models\StatusPembayaran;
use App\Models\UploadFile;
use App\Services\SiteStatusSummaryService;
use App\Services\ElectricityPaymentComparisonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardMasterController extends Controller
{
    public function __construct(
        private readonly SiteStatusSummaryService $summaryService,
        private readonly ElectricityPaymentComparisonService $paymentComparison
    ) {}

    /**
     * Tampilan Master Executive Dashboard.
     */
    public function index(): View
    {
        // 1. KPI Global Counts
        $siteCount = Site::count();
        $poCount = PoHq::count();
        $listrikCount = ListrikPln::count();

        // Match Total Site in the workbook: include every active source row.
        $infraTotal = SewaLahanRenewal::count() + CombatSite::count();

        // 2. Module Health & Counts
        $moduleDataCounts = [
            'pnl' => SiteMonthlyMetric::count(),
            'electricity' => $listrikCount + ListrikInbuilding::count(),
            'infrastructure' => $infraTotal,
            'po' => $poCount,
        ];
        $moduleDataTotal = array_sum($moduleDataCounts);
        $moduleDataComposition = collect($moduleDataCounts)->map(
            static fn (int $count): array => [
                'count' => $count,
                'percentage' => $moduleDataTotal > 0 ? round(($count / $moduleDataTotal) * 100) : 0,
            ]
        )->all();

        return view('dashboard.master', compact(
            'siteCount',
            'poCount',
            'listrikCount',
            'infraTotal',
            'moduleDataComposition'
        ));
    }

    /**
     * API Data untuk seluruh Diagram di Master Dashboard.
     */
    public function chartData(Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', 0);
        $bulan = (string) $request->query('bulan', 'all');
        $nop = strtoupper(trim((string) $request->query('nop', 'all')));
        $sourceVersion = implode('|', [
            SiteMonthlyMetric::query()->max('updated_at') ?? 'empty',
            DB::table('payment_pln_master_monthly')->max('updated_at') ?? 'empty',
            DB::table('payment_pln')->max('updated_at') ?? 'empty',
            ListrikAll::query()->max('updated_at') ?? 'empty',
            ListrikPln::query()->max('updated_at') ?? 'empty',
            ListrikInbuilding::query()->max('updated_at') ?? 'empty',
            DB::table('payment_ibc')->max('updated_at') ?? 'empty',
            DB::table('input_upload_tagihan_ibc')->max('updated_at') ?? 'empty',
            DB::table('status_pembayaran')->max('updated_at') ?? 'empty',
            DB::table('anomali_tagihan_pln')->max('updated_at') ?? 'empty',
            DB::table('anomali_tagihan_inbuilding')->max('updated_at') ?? 'empty',
        ]);
        $cacheKey = 'dashboard.chart-data.v9:'.hash('sha256', implode('|', [\App\Support\ReportDataVersion::current(), $sourceVersion, $tahun, $bulan, $nop]));

        return response()->json(Cache::remember(
            $cacheKey,
            now()->addMinutes(5),
            fn () => $this->buildChartData($request)->getData(true)
        ));
    }

    private function buildChartData(Request $request): JsonResponse
    {
        // Basis seluruh diagram Site Owner: master Dapot yang digunakan PnL.
        $siteCount = Site::count();
        $pnlPeriods = $this->summaryService->availablePeriods();
        $availablePeriods = $pnlPeriods;
        $electricityPeriods = DB::table('payment_pln_master_monthly_effective as payment_pln_master_monthly')
            ->select('tahun', 'bulan')
            ->distinct()
            ->get()
            ->merge(DB::table('payment_ibc_effective as payment_ibc')->select('tahun', 'bulan')->whereNotNull('tahun')->whereNotNull('bulan')->distinct()->get())
            ->merge(
                DB::table('status_pembayaran')
                    ->select('tahun', 'bulan')
                    ->distinct()
                    ->get()
            )
            ->merge(
                DB::table('payment_pln_effective as payment_pln')
                    ->select('tahun', 'bulan')
                    ->distinct()
                    ->get()
            )
            ->map(fn ($period) => [
                'tahun' => (int) $period->tahun,
                'bulan' => (int) $period->bulan,
            ]);
        $listrikAllPeriods = ListrikAll::query()
            ->select('tahun')
            ->whereNotNull('tahun')
            ->distinct()
            ->pluck('tahun')
            ->flatMap(fn ($year) => collect(range(1, 12))->map(fn (int $month) => [
                'tahun' => (int) $year,
                'bulan' => $month,
            ]));
        $availablePeriods = collect($availablePeriods)
            ->merge($electricityPeriods)
            ->merge($listrikAllPeriods)
            ->unique(fn (array $period) => $period['tahun'] . '-' . $period['bulan'])
            ->sortBy(fn (array $period) => [$period['tahun'], $period['bulan']])
            ->values()
            ->all();
        abort_if($availablePeriods === [], 404, 'Data dashboard belum tersedia.');

        $availableNops = DB::table('site_owners')
            ->whereNotNull('nop')
            ->whereRaw("TRIM(nop) <> ''")
            ->selectRaw('DISTINCT TRIM(nop) as nop')
            ->orderBy('nop')
            ->pluck('nop')
            ->all();

        $validated = $request->validate([
            'tahun' => ['nullable', 'integer', 'between:2000,2100'],
            'nop' => ['nullable', 'string', 'max:100'],
        ]);
        $nopInput = trim((string) ($validated['nop'] ?? ''));
        $nop = $nopInput === '' || strcasecmp($nopInput, 'all') === 0 ? null : $nopInput;
        if ($nop !== null) {
            $canonicalNop = collect($availableNops)
                ->first(fn (string $availableNop) => strcasecmp($availableNop, $nop) === 0);
            abort_if($canonicalNop === null, 422, 'NOP tidak valid.');
            $nop = $canonicalNop;
        }
        $electricityNop = $nop === null ? null : $this->normalizedElectricityNop($nop);
        $bulanInput = $request->query('bulan');
        $allMonths = $bulanInput === 'all' || $bulanInput === null;
        abort_unless(
            $bulanInput === null || $allMonths || filter_var($bulanInput, FILTER_VALIDATE_INT) !== false,
            422,
            'Bulan tidak valid.'
        );

        $availableYears = collect($availablePeriods)
            ->pluck('tahun')
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->values();
        $electricityYears = $electricityPeriods
            ->pluck('tahun')
            ->map(fn ($year) => (int) $year)
            ->unique()
            ->values();
        // Prioritaskan tahun yang punya data pembayaran PLN agar dashboard
        // tidak membuka grafik pembayaran kosong hanya karena sumber lain
        // (misalnya Listrik All) sudah memiliki data tahun berjalan.
        $defaultYear = $electricityYears->contains(now()->year)
            ? now()->year
            : (int) ($electricityYears->max() ?? $availableYears->max());
        $tahun = (int) ($validated['tahun'] ?? $defaultYear);
        $pnlPeriodsInYear = array_values(array_filter(
            $pnlPeriods,
            fn (array $period) => $period['tahun'] === $tahun
        ));
        $firstAvailablePeriod = collect($availablePeriods)
            ->first(fn (array $period) => $period['tahun'] === $tahun);
        $firstAvailableMonth = $firstAvailablePeriod['bulan'] ?? 1;
        $bulan = $allMonths ? null : (int) ($bulanInput ?? $firstAvailableMonth);
        if (!$allMonths) {
            abort_unless(
                collect($availablePeriods)->contains(
                    fn (array $period) => $period['tahun'] === $tahun && $period['bulan'] === $bulan
                ),
                422,
                'Bulan yang dipilih tidak memiliki data dashboard.'
            );
        }

        // $statusBulan dipakai hanya untuk mode bulan spesifik.
        // Saat semua bulan dipilih ($bulan === null), gunakan 0 agar service
        // menghitung status site berdasarkan SUM kumulatif per site di tahun tsb.
        $statusBulan = $bulan ?? 0;

        // --- 1. Distribusi Status Site PnL ---
        if ($nop === null && $pnlPeriodsInYear !== []) {
            $pnlStatusSummary = $this->summaryService->summary($statusBulan, $tahun);
        } elseif ($nop !== null && $pnlPeriodsInYear !== []) {
            $totalNopSites = DB::table('sites as nop_sites')
                ->join('site_owners as nop_owners', function ($join) {
                    $join->on(
                        DB::raw('UPPER(TRIM(nop_owners.site_code))'),
                        '=',
                        DB::raw('UPPER(TRIM(nop_sites.site_id))')
                    );
                })
                ->whereRaw('TRIM(nop_owners.nop) = ?', [$nop])
                ->distinct('nop_sites.id')
                ->count('nop_sites.id');

            if ($bulan !== null) {
                // Mode bulan spesifik + NOP: count per bulan itu saja
                $nopStatus = SiteMonthlyMetric::query()
                    ->join('sites as nop_sites', 'nop_sites.id', '=', 'site_monthly_metrics.site_id')
                    ->join('site_owners as nop_owners', function ($join) {
                        $join->on(
                            DB::raw('UPPER(TRIM(nop_owners.site_code))'),
                            '=',
                            DB::raw('UPPER(TRIM(nop_sites.site_id))')
                        );
                    })
                    ->where('site_monthly_metrics.bulan', $bulan)
                    ->where('site_monthly_metrics.tahun', $tahun)
                    ->whereRaw('TRIM(nop_owners.nop) = ?', [$nop])
                    ->selectRaw('COUNT(DISTINCT CASE WHEN site_monthly_metrics.profit_loss > 0 THEN site_monthly_metrics.site_id END) as profit')
                    ->selectRaw('COUNT(DISTINCT CASE WHEN site_monthly_metrics.profit_loss <= 0 THEN site_monthly_metrics.site_id END) as loss')
                    ->first();
                $profit = (int) ($nopStatus->profit ?? 0);
                $loss = (int) ($nopStatus->loss ?? 0);
            } else {
                // Mode semua bulan + NOP: SUM kumulatif per site, lalu count
                $nopSiteSums = SiteMonthlyMetric::query()
                    ->join('sites as nop_sites', 'nop_sites.id', '=', 'site_monthly_metrics.site_id')
                    ->join('site_owners as nop_owners', function ($join) {
                        $join->on(
                            DB::raw('UPPER(TRIM(nop_owners.site_code))'),
                            '=',
                            DB::raw('UPPER(TRIM(nop_sites.site_id))')
                        );
                    })
                    ->where('site_monthly_metrics.tahun', $tahun)
                    ->where('site_monthly_metrics.is_anomaly', 0)
                    ->whereRaw('TRIM(nop_owners.nop) = ?', [$nop])
                    ->selectRaw('site_monthly_metrics.site_id, SUM(site_monthly_metrics.profit_loss) as total_pnl')
                    ->groupBy('site_monthly_metrics.site_id')
                    ->get();
                $profit = $nopSiteSums->where('total_pnl', '>', 0)->count();
                $loss = $nopSiteSums->where('total_pnl', '<=', 0)->count();
            }

            $pnlStatusSummary = [
                'profit' => $profit,
                'loss' => $loss,
                'inactive' => max(0, $totalNopSites - $profit - $loss),
            ];
        } else {
            $pnlStatusSummary = ['profit' => 0, 'loss' => 0, 'inactive' => 0];
        }

        // --- 2. PnL periode terpilih (Revenue vs Cost vs PnL dalam Miliar Rupiah) ---
        $pnlMonthly = SiteMonthlyMetric::query()
            ->where('is_anomaly', 0)
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn ($query) => $query->where('bulan', $bulan))
            ->when($nop !== null, function ($query) use ($nop) {
                $query
                    ->join('sites as nop_sites', 'nop_sites.id', '=', 'site_monthly_metrics.site_id')
                    ->join('site_owners as nop_owners', function ($join) {
                        $join->on(
                            DB::raw('UPPER(TRIM(nop_owners.site_code))'),
                            '=',
                            DB::raw('UPPER(TRIM(nop_sites.site_id))')
                        );
                    })
                    ->whereRaw('TRIM(nop_owners.nop) = ?', [$nop]);
            })
            ->selectRaw('bulan, tahun, SUM(revenue) as rev, SUM(cost) as cst, SUM(profit_loss) as pnl')
            ->groupBy('tahun', 'bulan')
            ->orderBy('tahun')
            ->orderBy('bulan')
            ->get();

        $pnlLabels = [];
        $pnlRevenue = [];
        $pnlCost = [];
        $pnlProfitLoss = [];
        $pnlPeriods = [];

        $bulanNamaShort = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        foreach ($pnlMonthly as $m) {
            $label = ($bulanNamaShort[(int)$m->bulan] ?? $m->bulan) . ' ' . substr((string)$m->tahun, 2);
            $pnlLabels[] = $label;
            $pnlRevenue[] = round((float)$m->rev / 1000000000, 2);
            $pnlCost[] = round((float)$m->cst / 1000000000, 2);
            $pnlProfitLoss[] = round((float)$m->pnl / 1000000000, 2);
            $pnlPeriods[] = ['bulan' => (int) $m->bulan, 'tahun' => (int) $m->tahun];
        }

        $selectedSiteCount = ($nop !== null)
            ? DB::table('sites as s')
                ->join('site_owners as so', function ($join) {
                    $join->on(
                        DB::raw('UPPER(TRIM(so.site_code))'),
                        '=',
                        DB::raw('UPPER(TRIM(s.site_id))')
                    );
                })
                ->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($nop)])
                ->distinct('s.id')
                ->count('s.id')
            : $siteCount;

        // --- Hitung Persentase Dinamis untuk Badge KPI Total Sites ---
        $trendPct = null;
        $trendType = 'neutral'; // 'up' | 'down' | 'neutral'
        $trendLabel = '';

        $profitCount = (int) ($pnlStatusSummary['profit'] ?? 0);
        $lossCount   = (int) ($pnlStatusSummary['loss']   ?? 0);
        $totalActive = $profitCount + $lossCount;

        if ($bulan !== null) {
            // Mode bulan spesifik: hitung MoM Revenue Growth vs bulan sebelumnya
            $prevBulan = $bulan > 1 ? $bulan - 1 : 12;
            $prevTahun = $bulan > 1 ? $tahun : $tahun - 1;

            $prevRevQuery = SiteMonthlyMetric::query()
                ->where('is_anomaly', 0)
                ->where('tahun', $prevTahun)
                ->where('bulan', $prevBulan)
                ->when($nop !== null, function ($query) use ($nop) {
                    $query
                        ->join('sites as prev_sites', 'prev_sites.id', '=', 'site_monthly_metrics.site_id')
                        ->join('site_owners as prev_owners', function ($join) {
                            $join->on(
                                DB::raw('UPPER(TRIM(prev_owners.site_code))'),
                                '=',
                                DB::raw('UPPER(TRIM(prev_sites.site_id))')
                            );
                        })
                        ->whereRaw('UPPER(TRIM(prev_owners.nop)) = ?', [strtoupper($nop)]);
                })
                ->selectRaw('SUM(revenue) as rev');

            $prevRev = (float) ($prevRevQuery->first()?->rev ?? 0);
            $currRev = (float) $pnlMonthly->sum('rev');

            if ($prevRev > 0) {
                $growth = (($currRev - $prevRev) / $prevRev) * 100;
                $trendPct = round($growth, 1);
                $trendType = $growth >= 0 ? 'up' : 'down';
                $trendLabel = ($growth >= 0 ? '+' : '') . number_format($growth, 1) . '% MoM';
            } elseif ($currRev > 0) {
                $trendPct = 100.0;
                $trendType = 'up';
                $trendLabel = '+100% MoM';
            } else {
                $trendLabel = '0% MoM';
            }
        } else {
            // Mode semua bulan: hitung Profit Rate (% site profit dari total aktif)
            if ($totalActive > 0) {
                $rate = ($profitCount / $totalActive) * 100;
                $trendPct = round($rate, 1);
                $trendType = $rate >= 60 ? 'up' : ($rate >= 40 ? 'neutral' : 'down');
                $trendLabel = number_format($rate, 1) . '% Profit Rate';
            } else {
                $trendLabel = '— Profit Rate';
            }
        }

        $financialKpi = [
            'total_sites'   => $selectedSiteCount,
            'total_revenue' => (float) $pnlMonthly->sum('rev'),
            'total_cost'    => (float) $pnlMonthly->sum('cst'),
            'total_pnl'     => (float) $pnlMonthly->sum('pnl'),
            'month_count'   => $pnlMonthly->count(),
            'trend_pct'     => $trendPct,
            'trend_type'    => $trendType,
            'trend_label'   => $trendLabel,
        ];


        // --- 3. Komposisi Infrastruktur Management ---
        $infraBreakdown = [
            ['name' => 'Sewa Lahan Renewal', 'y' => SewaLahanRenewal::count()],
            ['name' => 'Combat Sites', 'y' => CombatSite::count()],
            ['name' => 'Recurring (ANT/Ipas)', 'y' => RecurringIpas::count()],
            ['name' => 'Sewa Lahan (Jaknet)', 'y' => JaknetContract::count()],
            ['name' => 'BAPSS', 'y' => Bapss::count()],
            ['name' => 'Upload File PDF', 'y' => UploadFile::count()],
        ];

        // --- 4. Tagihan listrik untuk periode terpilih (dalam Miliar Rupiah) ---
        // Gunakan tabel Payment PLN karena tabel ini memuat seluruh baris file
        // bulanan. status_pembayaran hanya memuat rekening yang berhasil
        // dipetakan ke master dan tidak boleh menjadi sumber total dashboard.
        $plnBaseQuery = PaymentPln::query()
            ->where('tahun', $tahun)
            ->when($bulan !== null, fn ($query) => $query->where('bulan', $bulan))
            ->when($electricityNop !== null, function ($query) use ($electricityNop) {
                $query->whereExists(function ($nopQuery) use ($electricityNop) {
                    $nopQuery->selectRaw('1')
                        ->from('listrik_pln as nop_listrik')
                        ->whereRaw('UPPER(TRIM(nop_listrik.site_id)) = UPPER(TRIM(payment_pln_effective.site_id))')
                        ->whereRaw(
                            "REPLACE(REPLACE(UPPER(TRIM(nop_listrik.nop)), 'NOP ', ''), 'NOP-', '') = ?",
                            [$electricityNop]
                        );
                });
            });

        $totalPlnTagihan = (float) (clone $plnBaseQuery)->sum('harga');

        $plnByMonth = (clone $plnBaseQuery)
            ->selectRaw('bulan, SUM(harga) as total, COUNT(*) as records')
            ->selectRaw('COUNT(DISTINCT UPPER(TRIM(site_id))) as site_count')
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        $plnPelangganCount = ListrikPln::query()
            ->when($electricityNop !== null, fn ($q) => $q->whereRaw(
                "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                [$electricityNop]
            ))
            ->count();

        $plnTotalDaya = (int) ListrikPln::query()
            ->when($electricityNop !== null, fn ($q) => $q->whereRaw(
                "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                [$electricityNop]
            ))
            ->sum('daya_va');

        $plnKpi = [
            'total_tagihan' => $totalPlnTagihan,
            'pelanggan_count' => $plnPelangganCount,
            'total_daya_va' => $plnTotalDaya,
        ];

        $plnMonthsLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $plnMonths = $bulan === null ? range(1, 12) : [$bulan];
        $plnLabels = [];
        $plnValues = [];
        $plnAmounts = [];
        $plnSiteCounts = [];
        $plnRecordCounts = [];
        $plnPeriods = [];
        foreach ($plnMonths as $month) {
            $monthSummary = $plnByMonth->get($month);
            $monthAmount = (float) ($monthSummary->total ?? 0);
            $plnLabels[] = ($plnMonthsLabels[$month - 1] ?? $month) . ' ' . $tahun;
            $plnValues[] = round($monthAmount / 1000000000, 2);
            $plnAmounts[] = round($monthAmount, 2);
            $plnSiteCounts[] = (int) ($monthSummary->site_count ?? 0);
            $plnRecordCounts[] = (int) ($monthSummary->records ?? 0);
            $plnPeriods[] = ['bulan' => $month, 'tahun' => $tahun];
        }

        $electricityPayment = $this->electricityPaymentSummary($tahun, $bulan, $nop);
        $electricityAll = $this->electricityAllSummary($tahun, $electricityNop);

        // --- 5. PO HQ Breakdown ---
        $poExpenseData = PoHq::query()
            ->selectRaw('expense_type, count(*) as total')
            ->groupBy('expense_type')
            ->get()
            ->map(fn($row) => ['name' => $row->expense_type ?: 'Lainnya', 'y' => (int)$row->total])
            ->toArray();

        // --- 6. Distribusi Site Owner sesuai NOP terpilih ---
        $siteOwnerBreakdown = DB::table('sites as s')
            ->leftJoin('site_owners as so', function ($join) {
                $join->on(
                    DB::raw('UPPER(TRIM(so.site_code))'),
                    '=',
                    DB::raw('UPPER(TRIM(s.site_id))')
                );
            })
            ->when($nop !== null, fn ($query) => $query->whereRaw('UPPER(TRIM(so.nop)) = ?', [strtoupper($nop)]))
            ->selectRaw("COALESCE(NULLIF(TRIM(so.site_owner), ''), 'Belum teridentifikasi') as owner_name, COUNT(DISTINCT s.id) as total")
            ->groupBy('owner_name')
            ->orderByDesc('total')
            ->get();

        $siteOwnerTotal = max(1, (int) $siteOwnerBreakdown->sum('total'));

        // --- 7. Anomali & Error Distribution ---
        $anomaliPlnCount = AnomaliTagihanPln::count();
        $anomaliIbcCount = AnomaliTagihanInbuilding::count();
        $anomaliPnlCount = SiteMonthlyMetric::where('is_anomaly', 1)->count();
        $totalAnomalies = $anomaliPlnCount + $anomaliIbcCount + $anomaliPnlCount;
        $normalPlnCount = max(0, ListrikPln::count() - $anomaliPlnCount);
        $normalIbcCount = max(0, ListrikInbuilding::count() - $anomaliIbcCount);
        $normalPnlCount = SiteMonthlyMetric::where('is_anomaly', 0)->count();

        $anomaliDistribution = [
            ['name' => 'Anomali Tagihan PLN', 'y' => $anomaliPlnCount, 'color' => '#EF4444'],
            ['name' => 'Anomali Inbuilding', 'y' => $anomaliIbcCount, 'color' => '#F59E0B'],
            ['name' => 'Anomali PnL Site', 'y' => $anomaliPnlCount, 'color' => '#3B82F6'],
            ['name' => 'Data Valid / Normal', 'y' => $normalPlnCount + $normalIbcCount + $normalPnlCount, 'color' => '#10B981'],
        ];

        $pnlTotal = max(1, (int) ($pnlStatusSummary['profit'] ?? 0) + (int) ($pnlStatusSummary['loss'] ?? 0) + (int) ($pnlStatusSummary['inactive'] ?? 0));
        return response()->json([
            'global_summary' => $this->index()->getData(),
            'pnl_status' => [
                ['name' => 'Profit Site', 'y' => $pnlStatusSummary['profit'] ?? 0, 'percentage' => round(($pnlStatusSummary['profit'] ?? 0) * 100 / $pnlTotal, 1), 'color' => '#10B981'],
                ['name' => 'Loss Site', 'y' => $pnlStatusSummary['loss'] ?? 0, 'percentage' => round(($pnlStatusSummary['loss'] ?? 0) * 100 / $pnlTotal, 1), 'color' => '#EF4444'],
                ['name' => 'Tidak Aktif', 'y' => $pnlStatusSummary['inactive'] ?? 0, 'percentage' => round(($pnlStatusSummary['inactive'] ?? 0) * 100 / $pnlTotal, 1), 'color' => '#94A3B8'],
            ],
            'pnl' => [
                'labels'     => $pnlLabels,
                'revenue'    => $pnlRevenue,
                'cost'       => $pnlCost,
                'profit_loss'=> $pnlProfitLoss,
                'periods'    => $pnlPeriods,
            ],
            'financial_kpi' => $financialKpi,
            'pln_kpi' => $plnKpi,
            'infra' => $infraBreakdown,
            'pln' => [
                'labels' => $plnLabels,
                'values' => $plnValues,
                'amounts' => $plnAmounts,
                'site_counts' => $plnSiteCounts,
                'record_counts' => $plnRecordCounts,
                'year'   => $tahun,
                'periods' => $plnPeriods,
            ],
            'electricity_payment' => $electricityPayment,
            'electricity_all' => $electricityAll,
            'electricity_comparison' => $this->paymentComparison->summary($tahun, $allMonths ? null : $bulan, $nop),
            'filters' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'all_months' => $allMonths,
                'status_bulan' => $statusBulan,
                'periods' => $availablePeriods,
                'nop' => $nop,
                'nops' => $availableNops,
            ],
            'po' => $poExpenseData,
            'site_owners' => [
            'total_sites' => (int) $siteOwnerBreakdown->sum('total'),
                'items' => $siteOwnerBreakdown->map(fn ($row) => [
                    'name' => $row->owner_name,
                    'total' => (int) $row->total,
                    'percentage' => round(((int) $row->total / $siteOwnerTotal) * 100, 2),
                ])->values()->all(),
            ],
            'anomalies' => [
                'total' => $totalAnomalies,
                'pln' => $anomaliPlnCount,
                'inbuilding' => $anomaliIbcCount,
                'pnl' => $anomaliPnlCount,
                'distribution' => $anomaliDistribution,
            ],
        ]);
    }

    public function electricityPaymentDetail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'nop' => ['nullable', 'string', 'max:100'],
        ]);

        $nopInput = trim((string) ($validated['nop'] ?? ''));
        $nop = $nopInput === '' || strcasecmp($nopInput, 'all') === 0 ? null : $nopInput;
        $electricityNop = $nop === null ? null : $this->normalizedElectricityNop($nop);
        // Aggregate this period once. Correlated UPPER/TRIM lookups otherwise
        // rescan the payment snapshot for every site on shared hosting.
        $snapshotSites = DB::table('payment_pln_master_monthly_effective as payment_pln_master_monthly')
            ->where('tahun', $validated['tahun'])
            ->where('bulan', $validated['bulan'])
            ->selectRaw('UPPER(TRIM(site_id)) as site_id, MAX(site_name) as site_name, MAX(id_pelanggan) as id_pelanggan, MAX(status_aktif_site) as status_aktif_site')
            ->selectRaw("MAX(CASE WHEN status_aktif_site IS NULL OR LOWER(TRIM(status_aktif_site)) <> 'tidak aktif' THEN 1 ELSE 0 END) as has_active")
            ->selectRaw('MAX(is_paid) as has_paid')
            ->groupByRaw('UPPER(TRIM(site_id))');
        $plnSites = DB::table('listrik_pln')
            ->when($electricityNop !== null, fn ($query) => $query->whereRaw(
                "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                [$electricityNop]
            ))
            ->selectRaw('UPPER(TRIM(site_id)) as site_id, MIN(nop) as nop')
            ->groupByRaw('UPPER(TRIM(site_id))');
        $activeSiteQuery = DB::query()
            ->fromSub($snapshotSites, 'snapshot')
            ->leftJoinSub($plnSites, 'pln', 'pln.site_id', '=', 'snapshot.site_id')
            ->where('snapshot.has_active', 1)
            ->where('snapshot.has_paid', 0)
            ->when($electricityNop !== null, fn ($query) => $query->whereNotNull('pln.site_id'));
        $missingCount = (clone $activeSiteQuery)->count();
        $activeSites = $activeSiteQuery
            ->select('snapshot.site_id', 'snapshot.site_name', 'snapshot.id_pelanggan', 'snapshot.status_aktif_site', 'pln.nop')
            ->orderBy('snapshot.site_id')
            ->limit(5000)
            ->get();

        return response()->json([
            'tahun' => (int) $validated['tahun'],
            'bulan' => (int) $validated['bulan'],
            'total' => $missingCount,
            'limited' => $missingCount > 5000,
            'sites' => $activeSites,
        ]);
    }

    public function electricityPaymentData(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'bulan' => ['nullable'],
            'nop' => ['nullable', 'string', 'max:100'],
        ]);
        $bulan = ($validated['bulan'] ?? 'all') === 'all' || ($validated['bulan'] ?? null) === null
            ? null
            : (int) $validated['bulan'];
        $nop = trim((string) ($validated['nop'] ?? ''));
        $nop = $nop === '' || strcasecmp($nop, 'all') === 0 ? null : $nop;

        return response()->json($this->electricityPaymentSummary((int) $validated['tahun'], $bulan, $nop));
    }

    private function electricityPaymentSummary(int $tahun, ?int $bulan, ?string $nop): array
    {
        $nop = $nop === null ? null : $this->normalizedElectricityNop($nop);
        $sourceVersion = implode('|', [
            DB::table('payment_pln_master_monthly')->max('updated_at') ?? 'empty',
            DB::table('payment_pln')->max('updated_at') ?? 'empty',
            DB::table('status_pembayaran')->max('updated_at') ?? 'empty',
            ListrikPln::query()->max('updated_at') ?? 'empty',
        ]);
        $cacheKey = 'dashboard.electricity-payment.v8:'.hash('sha256', implode('|', [\App\Support\ReportDataVersion::current(), $sourceVersion, $tahun, $bulan ?? 'all', $nop ?? 'all']));

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($tahun, $bulan, $nop): array {
            return $this->buildElectricityPaymentSummary($tahun, $bulan, $nop);
        });
    }

    private function buildElectricityPaymentSummary(int $tahun, ?int $bulan, ?string $nop): array
    {
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $months = $bulan === null ? range(1, 12) : [$bulan];
        $nopSiteIds = $nop === null
            ? null
            : ListrikPln::query()
                ->whereRaw(
                    "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                    [strtoupper($nop)]
                )
                ->pluck('site_id')
                ->map(fn ($siteId) => trim((string) $siteId))
                ->filter()
                ->unique()
                ->values()
                ->all();
        $periods = [];
        $activeCounts = [];
        $paidCounts = [];
        $percentages = [];
        $costs = [];
        $dataAvailable = [];
        $expectedActiveCount = ListrikPln::query()
            ->where(function ($query) {
                $query->whereNull('status_aktif_site')
                    ->orWhereRaw("LOWER(TRIM(status_aktif_site)) <> 'tidak aktif'");
            })
            ->when($nop !== null, fn ($query) => $query->whereRaw(
                "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                [strtoupper($nop)]
            ))
            ->count();
        $masterStats = PaymentPlnMasterMonthly::query()
            ->where('tahun', $tahun)
            ->when($nopSiteIds !== null, fn ($query) => $query->whereIn('site_id', $nopSiteIds))
            ->selectRaw('bulan, COUNT(DISTINCT site_id) as master_site_count')
            ->selectRaw("COUNT(DISTINCT CASE WHEN status_aktif_site IS NULL OR LOWER(TRIM(status_aktif_site)) <> 'tidak aktif' THEN site_id END) as active_count")
            ->selectRaw("COUNT(DISTINCT CASE WHEN is_paid = 1 AND (status_aktif_site IS NULL OR LOWER(TRIM(status_aktif_site)) <> 'tidak aktif') THEN site_id END) as paid_count")
            ->selectRaw("SUM(CASE WHEN is_paid = 1 AND (status_aktif_site IS NULL OR LOWER(TRIM(status_aktif_site)) <> 'tidak aktif') THEN COALESCE(amount, 0) ELSE 0 END) as paid_amount")
            ->groupBy('bulan')
            ->get()
            ->keyBy('bulan');

        foreach ($months as $month) {
            $masterStat = $masterStats->get($month);
            $masterSiteCount = (int) ($masterStat->master_site_count ?? 0);
            // A partial master import must not turn one paid row into a misleading 100%.
            $hasUsableMasterData = $masterStat !== null
                && ($expectedActiveCount === 0 || $masterSiteCount >= (int) ceil($expectedActiveCount * 0.9));

            if (!$hasUsableMasterData) {
                $paymentQuery = PaymentPln::query()
                    ->where('tahun', $tahun)
                    ->where('bulan', $month)
                    ->when($nopSiteIds !== null, fn ($query) => $query->whereIn('site_id', $nopSiteIds));
                $hasPaymentData = (clone $paymentQuery)->exists();
            } else {
                $hasPaymentData = false;
            }

            if ($hasPaymentData) {
                $activeCount = (clone $paymentQuery)->distinct('site_id')->count('site_id');
                $paidQuery = (clone $paymentQuery)->whereRaw("LOWER(TRIM(status)) = 'done'");
                $paid = (clone $paidQuery)->distinct('site_id')->count('site_id');
                $cost = (float) (clone $paidQuery)->sum('harga');

                $periods[] = ['tahun' => $tahun, 'bulan' => $month];
                $activeCounts[] = $activeCount;
                $paidCounts[] = $paid;
                $percentages[] = $activeCount > 0 ? round(min(100, ($paid / $activeCount) * 100), 2) : 0;
                $costs[] = $cost;
                $dataAvailable[] = $expectedActiveCount === 0
                    || $activeCount >= (int) ceil($expectedActiveCount * 0.9);
                continue;
            }

            if ($hasUsableMasterData) {
                $activeCount = (int) $masterStat->active_count;
                $paid = (int) $masterStat->paid_count;
                $cost = (float) $masterStat->paid_amount;
            } else {
                $activeQuery = ListrikPln::query()
                    ->where(function ($query) {
                        $query->whereNull('status_aktif_site')
                            ->orWhereRaw("LOWER(TRIM(status_aktif_site)) <> 'tidak aktif'");
                    })
                    ->when($nop !== null, fn ($query) => $query->whereRaw(
                        "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                        [strtoupper($nop)]
                    ));
                $paidQuery = StatusPembayaran::query()
                    ->where('tahun', $tahun)
                    ->where('bulan', $month)
                    ->whereHas('listrikPln', function ($query) use ($nop) {
                        $query->where(function ($statusQuery) {
                            $statusQuery->whereNull('status_aktif_site')
                                ->orWhereRaw("LOWER(TRIM(status_aktif_site)) <> 'tidak aktif'");
                        })->when($nop !== null, fn ($nopQuery) => $nopQuery->whereRaw(
                            "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                            [strtoupper($nop)]
                        ));
                    });
                $activeCount = (clone $activeQuery)->count();
                $paid = (clone $paidQuery)->distinct('listrik_pln_id')->count('listrik_pln_id');
                $cost = (float) (clone $paidQuery)->sum('harga');
            }

            $periods[] = ['tahun' => $tahun, 'bulan' => $month];
            $activeCounts[] = $activeCount;
            $paidCounts[] = $paid;
            $percentages[] = $activeCount > 0 ? round(min(100, ($paid / $activeCount) * 100), 2) : 0;
            $costs[] = $cost;
            $dataAvailable[] = $hasUsableMasterData
                || ($paid > 0 && ($expectedActiveCount === 0
                    || $activeCount >= (int) ceil($expectedActiveCount * 0.9)));
        }

        return [
            'labels' => $labels,
            'period_labels' => array_map(
                fn ($period) => ($labels[$period['bulan'] - 1] ?? $period['bulan']) . ' ' . $period['tahun'],
                $periods
            ),
            'periods' => $periods,
            'year' => $periods[0]['tahun'] ?? $tahun,
            'latest_year' => $periods[count($periods) - 1]['tahun'] ?? $tahun,
            'active_sites' => $activeCounts,
            'paid_counts' => $paidCounts,
            'percentages' => array_map(
                fn ($value, $available) => $available ? $value : null,
                $percentages,
                $dataAvailable
            ),
            'costs' => $costs,
            'data_available' => $dataAvailable,
        ];
    }

    private function electricityAllSummary(int $tahun, ?string $nop): array
    {
        $months = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'ags', 'sep', 'okt', 'nov', 'des'];
        $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $query = ListrikAll::query()
            ->where('tahun', $tahun)
            ->when($nop !== null, function ($query) use ($nop) {
                $query->whereIn(DB::raw('UPPER(TRIM(listrik_all.site_id))'), function ($siteQuery) use ($nop) {
                    $siteQuery->from('listrik_pln')
                        ->selectRaw('UPPER(TRIM(site_id))')
                        ->whereRaw(
                            "REPLACE(REPLACE(UPPER(TRIM(listrik_pln.nop)), 'NOP ', ''), 'NOP-', '') = ?",
                            [$nop]
                        );
                });
            });
        $query->selectRaw('COUNT(DISTINCT site_id) as site_count');
        foreach ($months as $month) {
            $query->selectRaw("SUM({$month}) as {$month}_cost, COUNT(DISTINCT CASE WHEN {$month} > 0 THEN site_id END) as {$month}_sites");
        }
        $totals = $query->first();

        return [
            'labels' => $labels,
            'year' => $tahun,
            'site_count' => (int) ($totals->site_count ?? 0),
            'site_counts' => array_map(fn ($month) => (int) ($totals->{$month.'_sites'} ?? 0), $months),
            'costs' => array_map(fn ($month) => (float) ($totals->{$month.'_cost'} ?? 0), $months),
        ];
    }

    private function normalizedElectricityNop(string $nop): string
    {
        return strtoupper(preg_replace('/^NOP[\s-]+/i', '', trim($nop)));
    }
}
