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
        $query = RecurringTagihanIpas::query()->orderBy('site_code');

        // Filter khusus TP
        if ($request->filled('tp') && $request->tp !== 'all') {
            $query->whereHas('terms', fn ($terms) => $terms->where('tp', $request->tp));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->toJson();
    }

    public function analytics(Request $request): JsonResponse
    {
        // Analitik dihitung dari rincian asli agar TP campuran pada satu site
        // tidak mengubah nominal atau jumlah termin milik TP yang dipilih.
        $tp = $request->query('tp', '');
        $tp = is_string($tp) ? $tp : '';
        $terms = DB::table('recurring_tagihan_ipas_terms')
            ->when(trim($tp) !== '' && $tp !== 'all', fn ($query) => $query->where('tp', $tp));

        $summary = (clone $terms)->selectRaw('COUNT(DISTINCT site_id) AS sites, COUNT(*) AS details, COALESCE(SUM(amount), 0) AS amount')->first();
        $sites = (int) $summary->sites;
        $tpLabel = "COALESCE(NULLIF(TRIM(tp), ''), 'Tanpa TP')";
        $contractLabel = "COALESCE(NULLIF(TRIM(contract_type), ''), 'Tanpa jenis kontrak')";
        $yearLabel = "COALESCE(NULLIF(SUBSTR(termin_start, 1, 4), ''), 'Tanpa tanggal')";

        $providers = (clone $terms)->selectRaw("$tpLabel AS label, COUNT(*) AS total")
            ->groupByRaw($tpLabel)->orderByDesc('total')->get();
        $contracts = (clone $terms)->selectRaw("$contractLabel AS label, COALESCE(SUM(amount), 0) AS total")
            ->groupByRaw($contractLabel)->orderByDesc('total')->get();
        $years = (clone $terms)->selectRaw("$yearLabel AS label, COUNT(*) AS total")
            ->groupByRaw($yearLabel)->orderBy('label')->get();

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

    public function show(RecurringTagihanIpas $site): JsonResponse
    {
        return response()->json(['data' => $site->terms()->orderBy('termin_start')->orderBy('id')->get()]);
    }
}
