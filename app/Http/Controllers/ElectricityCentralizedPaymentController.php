<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Exports\PaymentPlnExport;
use App\Imports\Electricity\CentralizedPaymentImport;
use App\Models\ListrikPln;
use App\Models\Read\PaymentPln;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Yajra\DataTables\Facades\DataTables;

class ElectricityCentralizedPaymentController extends Controller
{
    public function showUpload(): View
    {
        return view('electricity.centralized.upload-excel', [
            'type' => 'payment',
            'title' => 'Payment Done & Pending',
            'fileField' => 'payment_file',
            'uploadRoute' => route('electricity.centralized.payment.upload'),
            'templateRoute' => route('electricity.centralized.payment.template'),
            'headers' => ['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Daya', 'Phasa', 'Gol Tarif', 'Unit PLN', 'Harga', 'Update By', 'Tanggal'],
            'templateTitle' => 'Tagihan_Bulan_Tahun',
            'months' => [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'],
        ]);
    }

    public function template()
    {
        $headers = ['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Daya', 'Phasa', 'Gol Tarif', 'Unit PLN', 'Harga', 'Update By', 'Tanggal'];
        return Excel::download(new class($headers) implements FromArray, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headers) {}
            public function array(): array { return [['Tagihan_Bulan_Tahun'], $this->headers]; }
        }, 'template-payment-centralized.xlsx');
    }
    /**
     * Tampilan utama halaman Payment (Centralized).
     */
    public function index(Request $request): View
    {
        $years = PaymentPln::query()
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->toArray();

        if (empty($years)) {
            $years = [(int)date('Y'), (int)date('Y') - 1];
        }

        $selectedBulan = $request->integer('bulan') ?: null;
        $selectedTahun = $request->integer('tahun') ?: null;
        $selectedNop = $this->normalizeNop($request->query('nop'));
        $nops = ListrikPln::query()
            ->whereNotNull('nop')
            ->whereRaw("TRIM(nop) <> ''")
            ->selectRaw("DISTINCT TRIM(REPLACE(REPLACE(UPPER(nop), 'NOP ', ''), 'NOP-', '')) as nop")
            ->orderBy('nop')
            ->pluck('nop')
            ->all();

        return view('electricity.centralized.payment.index', compact('years', 'nops', 'selectedBulan', 'selectedTahun', 'selectedNop'));
    }

    /**
     * DataTables server-side endpoint untuk Payment (Centralized).
     */
    public function data(Request $request): JsonResponse
    {
        $query = PaymentPln::query()->select('payment_pln_effective.*');

        // Filter Bulan
        if ($request->filled('bulan') && is_numeric($request->bulan)) {
            $query->where('bulan', (int) $request->bulan);
        }

        // Filter Tahun
        if ($request->filled('tahun') && is_numeric($request->tahun)) {
            $query->where('tahun', (int) $request->tahun);
        }

        $this->applyNopFilter($query, $this->normalizeNop($request->query('nop')));

        // Filter Status (Done / Pending)
        if ($request->filled('status') && in_array($request->status, ['Done', 'Pending'])) {
            $query->where('status', $request->status);
        }

        $dataTable = DataTables::of($query);

        return $dataTable
            ->addIndexColumn()
            ->editColumn('daya', fn (PaymentPln $p) => $p->daya ? number_format($p->daya) . ' VA' : '-')
            ->editColumn('harga', fn (PaymentPln $p) => 'Rp ' . number_format($p->harga, 0, ',', '.'))
            ->editColumn('tanggal_status', fn (PaymentPln $p) => $p->tanggal_status?->format('d-m-Y') ?? '-')
            ->editColumn('status', function (PaymentPln $p) {
                $isDone = strtolower($p->status) === 'done';
                $badgeClass = $isDone ? 'badge-aktif' : 'badge-pending';
                return '<span class="badge ' . $badgeClass . '">' . e($p->status) . '</span>';
            })
            ->rawColumns(['status'])
            ->toJson();
    }

    /**
     * Export data Payment ke Excel.
     */
    public function exportExcel(Request $request)
    {
        $bulan = $request->filled('bulan') ? (int) $request->bulan : null;
        $tahun = $request->filled('tahun') ? (int) $request->tahun : null;
        $status = $request->filled('status') ? (string) $request->status : null;
        $nop = $this->normalizeNop($request->query('nop'));

        $filename = 'payment_pln_'
            .($status ? strtolower($status).'_' : '')
            .($nop ? strtolower(str_replace(' ', '_', $nop)).'_' : '')
            .now()->format('Ymd_His').'.xlsx';

        return (new PaymentPlnExport($bulan, $tahun, $status, $nop))->download($filename);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'payment_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200'],
            'status' => ['required', 'in:Done,Pending'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tahun' => ['required', 'integer', 'between:2000,2100'],
        ]);

        try {
            DB::transaction(function () use ($request, $validated): void {
                Excel::import(
                    new CentralizedPaymentImport($validated['status'], $validated['bulan'], $validated['tahun']),
                    $request->file('payment_file')
                );
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['payment_file' => 'File Payment gagal diproses. Periksa format file atau hubungi administrator.']);
        }

        return back()->with('success', 'Upload Payment '.$validated['status'].' berhasil dan data website telah diperbarui.');
    }

    private function normalizeNop(mixed $value): ?string
    {
        $nop = strtoupper(trim((string) $value));
        $nop = preg_replace('/^NOP[\s-]+/', '', $nop) ?? $nop;

        return $nop !== '' && $nop !== 'ALL' ? $nop : null;
    }

    private function applyNopFilter($query, ?string $nop): void
    {
        if ($nop === null) {
            return;
        }

        $query->whereExists(function ($nopQuery) use ($nop) {
            $nopQuery->selectRaw('1')
                ->from('listrik_pln as payment_nop_listrik')
                ->whereRaw('UPPER(TRIM(payment_nop_listrik.site_id)) = UPPER(TRIM(payment_pln_effective.site_id))')
                ->whereRaw(
                    "REPLACE(REPLACE(UPPER(TRIM(payment_nop_listrik.nop)), 'NOP ', ''), 'NOP-', '') = ?",
                    [$nop]
                );
        });
    }
}
