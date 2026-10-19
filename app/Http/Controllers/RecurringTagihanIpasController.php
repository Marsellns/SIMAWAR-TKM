<?php

namespace App\Http\Controllers;

use App\Models\RecurringTagihanIpas;
use App\Models\RecurringTagihanIpasTerm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class RecurringTagihanIpasController extends Controller
{
    private const TP_LABEL = "COALESCE(NULLIF(TRIM(tp), ''), 'Tanpa TP')";

    private const CONTRACT_LABEL = "COALESCE(NULLIF(TRIM(contract_type), ''), 'Tanpa jenis kontrak')";

    private const YEAR_LABEL = "COALESCE(NULLIF(SUBSTR(termin_start, 1, 4), ''), 'Tanpa tanggal')";

    public function index(): View
    {
        // Daftar TP unik untuk dropdown filter
        $tpList = RecurringTagihanIpasTerm::query()
            ->whereNotNull('tp')
            ->where('tp', '!=', '')
            ->distinct()
            ->orderBy('tp')
            ->pluck('tp');

        return view('infrastruktur.recurring-tagihan-ipas.index', compact('tpList'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = RecurringTagihanIpas::query();

        // Filter khusus TP
        if ($tp = $this->selectedTp($request)) {
            $query->whereHas('terms', fn ($terms) => $terms->where('tp', $tp));
        }
        $query->orderBy('site_code');

        return DataTables::of($query)
            ->addIndexColumn()
            ->toJson();
    }

    public function analytics(Request $request): JsonResponse
    {
        // Analitik dihitung dari rincian asli agar TP campuran pada satu site
        // tidak mengubah nominal atau jumlah termin milik TP yang dipilih.
        $terms = DB::table('recurring_tagihan_ipas_terms')
            ->when($this->selectedTp($request), fn ($query, $tp) => $query->where('tp', $tp));

        $summary = (clone $terms)->selectRaw('COUNT(DISTINCT site_id) AS sites, COUNT(*) AS details, COALESCE(SUM(amount), 0) AS amount')->first();
        $sites = (int) $summary->sites;
        // MariaDB with ONLY_FULL_GROUP_BY requires grouping the projected label
        // as a column, rather than repeating its expression in GROUP BY.
        $providers = DB::query()->fromSub((clone $terms)->selectRaw(self::TP_LABEL.' AS label'), 'labeled_terms')
            ->selectRaw('label, COUNT(*) AS total')->groupBy('label')->orderByDesc('total')->get();
        $contracts = DB::query()->fromSub((clone $terms)->selectRaw(self::CONTRACT_LABEL.' AS label, amount'), 'labeled_terms')
            ->selectRaw('label, COALESCE(SUM(amount), 0) AS total')->groupBy('label')->orderByDesc('total')->get();
        $years = DB::query()->fromSub((clone $terms)->selectRaw(self::YEAR_LABEL.' AS label'), 'labeled_terms')
            ->selectRaw('label, COUNT(*) AS total')->groupBy('label')->orderBy('label')->get();

        return response()->json([
            'metrics' => [
                'sites' => $sites,
                'details' => (int) $summary->details,
                'amount' => (string) $summary->amount,
                'details_per_site' => $sites ? round((int) $summary->details / $sites, 1) : 0,
            ],
            'providers' => $providers,
            'contracts' => $contracts,
            'years' => $years,
        ]);
    }

    public function details(Request $request): JsonResponse
    {
        $query = DB::table('recurring_tagihan_ipas_terms')->select([
            'id', 'site_id', 'site_code', 'site_name', 'tp', 'contract_type',
            'termin', 'periode_ke', 'termin_start', 'termin_end', 'amount', 'batch_name',
        ]);
        if ($tp = $this->selectedTp($request)) {
            $query->where('tp', $tp);
        }

        $dimension = $request->query('dimension', 'all');
        $value = $request->query('value');
        $value = is_string($value) ? $value : '';
        $except = $request->query('except', []);
        $except = is_array($except) ? array_slice(array_values(array_filter($except, 'is_string')), 0, 8) : [];

        switch ($dimension) {
            case 'all':
                break;
            case 'tp':
                $query->whereRaw(self::TP_LABEL.' = ?', [$value]);
                break;
            case 'tp_other':
                $query->whereNotIn(DB::raw(self::TP_LABEL), $except);
                break;
            case 'contract':
                $query->whereRaw(self::CONTRACT_LABEL.' = ?', [$value]);
                break;
            case 'contract_other':
                $query->whereNotIn(DB::raw(self::CONTRACT_LABEL), $except);
                break;
            case 'year':
                abort_unless(preg_match('/^\d{4}$/', $value), 422);
                $query->where('termin_start', '>=', $value.'-01-01')
                    ->where('termin_start', '<', ((int) $value + 1).'-01-01');
                break;
            case 'before_year':
                abort_unless(preg_match('/^\d{4}$/', $value), 422);
                $query->where('termin_start', '<', $value.'-01-01');
                break;
            case 'missing_year':
                $query->whereNull('termin_start');
                break;
            default:
                abort(422);
        }

        // Keep the chart's default order, while allowing the table headers to sort on demand.
        if (! $request->query('order')) {
            $request->query('sort') === 'amount_desc'
                ? $query->orderByDesc('amount')->orderByDesc('id')
                : $query->orderByDesc('id');
        }

        return DataTables::of($query)->addIndexColumn()->toJson();
    }

    private function selectedTp(Request $request): ?string
    {
        $tp = $request->query('tp');

        return is_string($tp) && trim($tp) !== '' && $tp !== 'all' ? $tp : null;
    }

    public function show(RecurringTagihanIpas $site): JsonResponse
    {
        return response()->json(['data' => $site->terms()->orderBy('termin_start')->orderBy('id')->get()]);
    }
}
