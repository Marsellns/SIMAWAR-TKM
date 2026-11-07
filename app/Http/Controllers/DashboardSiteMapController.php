<?php

namespace App\Http\Controllers;

use App\Services\DashboardSiteMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardSiteMapController extends Controller
{
    public function __invoke(Request $request, string $module, DashboardSiteMapService $maps): JsonResponse
    {
        abort_unless(in_array($module, ['pnl', 'feasibility', 'infrastructure', 'electricity', 'recurring', 'equipment'], true), 404);
        $scopes = match ($module) {
            'infrastructure' => ['all', 'sewa', 'combat'], 'electricity' => ['all', 'centralized', 'inbuilding'],
            'recurring' => ['ant', 'tagihan'], default => ['all'],
        };
        $filters = $request->validate([
            'scope' => ['nullable', Rule::in($scopes)],
            'tahun' => ['nullable', 'regex:/^(all|20\d{2}|2100)$/'],
            'bulan' => ['nullable', 'regex:/^(all|[1-9]|1[0-2])$/'],
            'nop' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['Profit', 'Loss', 'TidakAktif', 'Tidak Aktif'])],
            'ownership_scope' => ['nullable', Rule::in(['TP', 'Telkomsel'])],
            'available_on_jarvis' => ['nullable', 'string', 'max:40'], 'tp' => ['nullable', 'string', 'max:255'],
            'sites' => ['sometimes', 'array', 'max:10000'], 'sites.*' => ['string', 'max:100'],
        ]);
        return response()->json($maps->data($module, $filters))->header('Cache-Control', 'private, no-store');
    }
}
