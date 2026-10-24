<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Imports\Electricity\ListrikInbuildingImport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ElectricityInbuildingController extends Controller
{
    public function showUploadTagihanIbc(): View
    {
        return view('electricity.inbuilding.upload-tagihan-ibc');
    }

    public function uploadTagihanIbc(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:20480',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xls', 'xlsx'])) {
            return back()->with('error', 'Format file tidak didukung. Hanya file .xls dan .xlsx yang diperbolehkan.')
                ->withInput();
        }

        try {
            $import = new ListrikInbuildingImport();
            \Illuminate\Support\Facades\DB::transaction(fn () => Excel::import($import, $file));

            $stats = $import->getStats();

            $message = "Upload berhasil. Data diproses: {$stats['inserted']} baris dimasukkan";
            if ($stats['skipped'] > 0) {
                $message .= ", {$stats['skipped']} baris dilewati (Site ID kosong)";
            }
            if ($stats['errors'] > 0) {
                $message .= ", {$stats['errors']} baris error";
            }

            return back()->with('success', $message);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Baris {$failure->row()}: " . implode(', ', $failure->errors());
            }
            return back()->with('error', 'Validasi gagal: ' . implode('; ', array_slice($errorMessages, 0, 5)))
                ->withInput();
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Gagal memproses file. Periksa format file atau hubungi administrator.')
                ->withInput();
        }
    }

    public function downloadTemplateTagihanIbc(): BinaryFileResponse
    {
        $headers = [
            'Site ID',
            'Site Name',
            'Status',
            'Nama BM',
            'No NPWP',
            'Alamat',
            'Telkomsel / TP',
            'Daya',
            'Harga per kWh',
            'Update By',
            'Tanggal',
        ];

        return Excel::download(new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headers) {}
            public function array(): array { return [['Data Inbuilding Export'], $this->headers]; }
        }, 'template-listrik-inbuilding.xlsx');
    }
}
