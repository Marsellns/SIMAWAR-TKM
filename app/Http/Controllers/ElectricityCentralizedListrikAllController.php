<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Exports\ListrikAllExport;
use App\Imports\Electricity\CentralizedListrikAllImport;
use App\Models\ListrikAll;
use App\Models\Read\PaymentPlnMasterMonthly;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Yajra\DataTables\Facades\DataTables;

class ElectricityCentralizedListrikAllController extends Controller
{
    public function showUpload(): View
    {
        return view('electricity.centralized.upload-excel', [
            'type' => 'listrik-all',
            'title' => 'Listrik All',
            'fileField' => 'listrik_all_file',
            'uploadRoute' => route('electricity.centralized.listrik-all.upload'),
            'templateRoute' => route('electricity.centralized.listrik-all.template'),
            'headers' => self::listrikAllHeaders(),
            'months' => [],
        ]);
    }

    public function template()
    {
        $headers = self::listrikAllHeaders();
        return Excel::download(new class($headers) implements FromArray, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headers) {}
            public function array(): array { return [['Listrik All - Template'], $this->headers]; }
        }, 'template-listrik-all-centralized.xlsx');
    }

    private static function listrikAllHeaders(): array
    {
        $headers = ['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Gol Tarif', 'Unit PLN'];
        foreach (['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'] as $month) {
            $headers[] = 'Flagging '.$month.' '.now()->year;
        }

        return $headers;
    }
    public function index(Request $request): View
    {
        $years = ListrikAll::query()
            ->distinct()
            ->pluck('tahun')
            ->map(fn ($year) => (int) $year)
            ->merge(
                PaymentPlnMasterMonthly::query()
                    ->distinct()
                    ->pluck('tahun')
                    ->map(fn ($year) => (int) $year)
            )
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($years)) {
            $years = [(int) now()->year];
        }

        return view('electricity.centralized.listrik-all.index', [
            'years' => $years,
            'selectedYear' => in_array((int) $request->query('tahun'), $years, true) ? (int) $request->query('tahun') : (int) $years[0],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $tahun = $request->filled('tahun')
            ? (int) $request->tahun
            : (int) max(
                (int) ListrikAll::query()->max('tahun'),
                (int) PaymentPlnMasterMonthly::query()->max('tahun')
            );

        if ($tahun === 0) {
            $tahun = (int) now()->year;
        }

        $query = \App\Support\ListrikAllQuery::forYear($tahun);

        $fmt = fn ($val) => $val > 0 ? 'Rp '.number_format($val, 0, ',', '.') : '-';

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('jan', fn ($l) => $fmt($l->jan))
            ->editColumn('feb', fn ($l) => $fmt($l->feb))
            ->editColumn('mar', fn ($l) => $fmt($l->mar))
            ->editColumn('apr', fn ($l) => $fmt($l->apr))
            ->editColumn('mei', fn ($l) => $fmt($l->mei))
            ->editColumn('jun', fn ($l) => $fmt($l->jun))
            ->editColumn('jul', fn ($l) => $fmt($l->jul))
            ->editColumn('ags', fn ($l) => $fmt($l->ags))
            ->editColumn('sep', fn ($l) => $fmt($l->sep))
            ->editColumn('okt', fn ($l) => $fmt($l->okt))
            ->editColumn('nov', fn ($l) => $fmt($l->nov))
            ->editColumn('des', fn ($l) => $fmt($l->des))
            ->toJson();
    }

    public function exportExcel(Request $request)
    {
        $tahun = $request->filled('tahun')
            ? (int) $request->tahun
            : (int) max(
                (int) ListrikAll::query()->max('tahun'),
                (int) PaymentPlnMasterMonthly::query()->max('tahun')
            );

        if ($tahun === 0) {
            $tahun = (int) now()->year;
        }

        $filename = 'listrik_all_'.$tahun.'_'.now()->format('Ymd_His').'.xlsx';

        return (new ListrikAllExport($tahun))->download($filename);
    }

    public function upload(Request $request)
    {
        $request->validate(['listrik_all_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200']]);

        try {
            DB::transaction(function () use ($request): void {
                Excel::import(new CentralizedListrikAllImport(), $request->file('listrik_all_file'));
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['listrik_all_file' => 'File Listrik All gagal diproses. Periksa format file atau hubungi administrator.']);
        }

        return back()->with('success', 'Upload Listrik All berhasil dan data website telah diperbarui.');
    }
}
