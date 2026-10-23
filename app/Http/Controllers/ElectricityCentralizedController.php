<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Imports\Electricity\ListrikPlnImport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ElectricityCentralizedController extends Controller
{
    public function showUploadFlagging(): View
    {
        return view('electricity.centralized.upload-flagging');
    }

    public function uploadFlagging(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx|max:20480',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['xls', 'xlsx'])) {
            return back()->with('error', 'Format file tidak didukung. Hanya file .xls dan .xlsx yang diperbolehkan.')
                ->withInput();
        }

        try {
            $import = new ListrikPlnImport();
            \Illuminate\Support\Facades\DB::transaction(fn () => Excel::import($import, $file));

            $stats = $import->getStats();

            $message = "Upload berhasil. Data diproses: {$stats['inserted']} baris dimasukkan";
            if ($stats['skipped'] > 0) {
                $message .= ", {$stats['skipped']} baris dilewati (ID Pelanggan kosong)";
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

    public function downloadTemplateFlagging(): BinaryFileResponse
    {
        $headers = [
            'ID Pelanggan',
            'Site ID',
            'Site Name',
            'Alamat',
            'Status Aktif Site',
            'Nama Pelanggan',
            'Daya (VA)',
            'Phasa',
            'Status AMR',
            'Gol Tarif',
            'Unit Layanan PLN',
            'Jenis Bayar',
            'TP Owner',
            'NOP',
            'Update By',
            'Tanggal',
        ];

        $data = [['' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '', '' => '']];
        $data[0] = $headers;

        return Excel::download(new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings, WithEvents {
            use HasReadableWorksheetLayout;

            protected $headings;
            public function __construct(array $headings) { $this->headings = $headings; }
            public function array(): array { return []; }
            public function headings(): array { return $this->headings; }
        }, 'template-listrik-pln.xlsx');
    }
}
