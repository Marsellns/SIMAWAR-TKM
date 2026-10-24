<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Exports\AnomaliTagihanPlnExport;
use App\Imports\Electricity\CentralizedAnomaliImport;
use App\Models\Read\AnomaliTagihanPln;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;

class ElectricityCentralizedAnomaliController extends Controller
{
    public function showUpload(): View
    {
        return view('electricity.centralized.upload-excel', [
            'type' => 'anomali',
            'title' => 'Anomali Tagihan',
            'fileField' => 'anomali_file',
            'uploadRoute' => route('electricity.centralized.anomali.upload'),
            'templateRoute' => route('electricity.centralized.anomali.template'),
            'headers' => ['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Bulan', 'Tahun', 'Harga Sebelumnya', 'Harga Sekarang', 'Kenaikan (%)'],
            'months' => [],
        ]);
    }

    public function template()
    {
        $headers = ['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Bulan', 'Tahun', 'Harga Sebelumnya', 'Harga Sekarang', 'Kenaikan (%)'];
        return Excel::download(new class($headers) implements FromArray, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headers) {}
            public function array(): array { return [$this->headers]; }
        }, 'template-anomali-tagihan-centralized.xlsx');
    }
    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(): View
    {
        $years = AnomaliTagihanPln::query()
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->toArray();

        if (empty($years)) {
            $years = [(int)date('Y'), (int)date('Y') - 1];
        }

        return view('electricity.centralized.anomali.index', compact('years'));
    }

    public function data(Request $request): JsonResponse
    {
        // THRESHOLD ANOMALI: Kenaikan (%) > 50%
        $query = AnomaliTagihanPln::query()
            ->where('kenaikan_persen', '>', 50);

        if ($request->filled('bulan') && is_numeric($request->bulan)) {
            $query->where('bulan', (int)$request->bulan);
        }

        if ($request->filled('tahun') && is_numeric($request->tahun)) {
            $query->where('tahun', (int)$request->tahun);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('bulan', fn (AnomaliTagihanPln $a) => self::BULAN_NAMA[(int)$a->bulan] ?? (string)$a->bulan)
            ->editColumn('tagihan_sebelumnya', fn (AnomaliTagihanPln $a) => 'Rp ' . number_format($a->tagihan_sebelumnya, 0, ',', '.'))
            ->editColumn('tagihan_saat_ini', fn (AnomaliTagihanPln $a) => 'Rp ' . number_format($a->tagihan_saat_ini, 0, ',', '.'))
            ->editColumn('selisih', fn (AnomaliTagihanPln $a) => 'Rp ' . number_format($a->selisih, 0, ',', '.'))
            ->editColumn('kenaikan_persen', fn (AnomaliTagihanPln $a) => '<span class="badge bg-danger-subtle text-danger fw-bold">+' . number_format($a->kenaikan_persen, 1) . '%</span>')
            ->rawColumns(['kenaikan_persen'])
            ->toJson();
    }

    public function exportExcel(Request $request)
    {
        $bulan = $request->filled('bulan') ? (int) $request->bulan : null;
        $tahun = $request->filled('tahun') ? (int) $request->tahun : null;

        $filename = 'anomali_tagihan_pln_' . now()->format('Ymd_His') . '.xlsx';
        return (new AnomaliTagihanPlnExport($bulan, $tahun))->download($filename);
    }

    public function upload(Request $request)
    {
        $request->validate(['anomali_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200']]);

        try {
            DB::transaction(function () use ($request): void {
                Excel::import(new CentralizedAnomaliImport(), $request->file('anomali_file'));
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['anomali_file' => 'File Anomali Tagihan gagal diproses. Periksa format file atau hubungi administrator.']);
        }

        return back()->with('success', 'Upload Anomali Tagihan berhasil dan data website telah diperbarui.');
    }
}
