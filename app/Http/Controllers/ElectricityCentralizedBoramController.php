<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Exports\BongkarRampungMandiriExport;
use App\Imports\Electricity\CentralizedBongkarRampungImport;
use App\Models\BongkarRampungMandiri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Yajra\DataTables\Facades\DataTables;

class ElectricityCentralizedBoramController extends Controller
{
    public function showUpload(): View
    {
        return view('electricity.centralized.upload-excel', [
            'type' => 'boram',
            'title' => 'Bongkar Rampung',
            'fileField' => 'boram_file',
            'uploadRoute' => route('electricity.centralized.bongkar-rampung-mandiri.upload'),
            'templateRoute' => route('electricity.centralized.bongkar-rampung-mandiri.template'),
            'headers' => ['No', 'Aksi', 'ID Pelanggan', 'Site ID', 'Site Name', 'Alamat', 'Daya', 'Phasa', 'Golongan Tarif', 'NOP', 'No Surat Boram', 'Tgl Surat Boram', 'Mitra Boram', 'Created By', 'Tgl Update'],
            'months' => [],
        ]);
    }

    public function template()
    {
        $headers = ['No', 'Aksi', 'ID Pelanggan', 'Site ID', 'Site Name', 'Alamat', 'Daya', 'Phasa', 'Golongan Tarif', 'NOP', 'No Surat Boram', 'Tgl Surat Boram', 'Mitra Boram', 'Created By', 'Tgl Update'];
        return Excel::download(new class($headers) implements FromArray, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headers) {}
            public function array(): array { return [['Daftar_Site_Tidak_Aktif'], $this->headers]; }
        }, 'template-bongkar-rampung-centralized.xlsx');
    }
    public function index(): View
    {
        return view('electricity.centralized.bongkar-rampung.index');
    }

    public function data(Request $request): JsonResponse
    {
        $isAdmin = auth()->user()->hasRole('admin');
        $query = BongkarRampungMandiri::query()->orderByDesc('id');

        $dataTable = DataTables::of($query);

        if ($isAdmin) {
            $dataTable->addColumn(
                'aksi',
                fn (BongkarRampungMandiri $item) => view('electricity.centralized.bongkar-rampung.partials.aksi', compact('item'))->render()
            );
        }

        return $dataTable
            ->addIndexColumn()
            ->rawColumns($isAdmin ? ['aksi'] : [])
            ->toJson();
    }

    public function exportExcel()
    {
        $filename = 'bongkar_rampung_mandiri_' . now()->format('Ymd_His') . '.xlsx';
        return (new BongkarRampungMandiriExport())->download($filename);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate(['boram_file' => ['required', 'file', 'mimes:xls,xlsx', 'max:51200']]);

        try {
            DB::transaction(function () use ($request): void {
                Excel::import(new CentralizedBongkarRampungImport(), $request->file('boram_file'));
            });
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['boram_file' => 'File Bongkar Rampung gagal diproses. Periksa format file atau hubungi administrator.']);
        }

        return back()->with('success', 'Upload Bongkar Rampung berhasil dan data website telah diperbarui.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_pelanggan' => ['required', 'string', 'max:50'],
            'site_id'      => ['required', 'string', 'max:50'],
            'site_name'    => ['nullable', 'string', 'max:255'],
        ]);

        BongkarRampungMandiri::create($validated);

        return redirect()
            ->route('electricity.centralized.bongkar-rampung-mandiri.index')
            ->with('success', 'Data Bongkar Rampung berhasil ditambahkan.');
    }

    public function update(Request $request, BongkarRampungMandiri $bongkarRampungMandiri): RedirectResponse
    {
        $validated = $request->validate([
            'id_pelanggan' => ['required', 'string', 'max:50'],
            'site_id'      => ['required', 'string', 'max:50'],
            'site_name'    => ['nullable', 'string', 'max:255'],
        ]);

        $bongkarRampungMandiri->update($validated);

        return redirect()
            ->route('electricity.centralized.bongkar-rampung-mandiri.index')
            ->with('success', 'Data Bongkar Rampung berhasil diperbarui.');
    }

    public function destroy(BongkarRampungMandiri $bongkarRampungMandiri): RedirectResponse
    {
        $bongkarRampungMandiri->delete();

        return redirect()
            ->route('electricity.centralized.bongkar-rampung-mandiri.index')
            ->with('success', 'Data Bongkar Rampung berhasil dihapus.');
    }
}
