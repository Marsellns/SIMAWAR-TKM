<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Imports\OperationalDatasetImport;
use App\Support\OperationalDatasets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class OperationalDataEntryController extends Controller
{
    public function create(string $dataset): View
    {
        $definition = OperationalDatasets::DEFINITIONS[$dataset];

        return view('operations.entry', ['dataset' => $dataset, 'definition' => $definition, 'fields' => OperationalDatasets::fields($dataset)]);
    }

    public function store(Request $request, string $dataset)
    {
        OperationalDatasets::save($dataset, $request->all(), true, $request->user()->name);

        return redirect()->route(OperationalDatasets::DEFINITIONS[$dataset][2])->with('success', 'Data berhasil ditambahkan. Ringkasan mengikuti data yang tersimpan.');
    }

    public function upload(Request $request, string $dataset)
    {
        $request->validate(['dataset_file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200']]);
        $import = new OperationalDatasetImport($dataset, $request->user()->name);
        try {
            DB::transaction(function () use ($import, $request): void {
                Excel::import($import, $request->file('dataset_file'));
                if ($import->processed === 0) {
                    throw ValidationException::withMessages(['dataset_file' => 'File tidak berisi data.']);
                }
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['dataset_file' => 'File gagal diproses. Periksa header dan isi template.']);
        }

        return redirect()->route(OperationalDatasets::DEFINITIONS[$dataset][2])->with('success', $import->processed.' baris disimpan. Data lain tetap tersedia.');
    }

    public function template(string $dataset)
    {
        $fields = OperationalDatasets::fields($dataset);
        $headers = array_keys($fields);
        $formats = [];
        foreach (array_values($fields) as $index => $field) {
            if ($field['type'] === 'text') {
                $formats[Coordinate::stringFromColumnIndex($index + 1)] = NumberFormat::FORMAT_TEXT;
            }
        }

        return Excel::download(new class($headers, $formats) implements FromArray, WithColumnFormatting, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headers, private readonly array $formats) {}

            public function array(): array
            {
                return [$this->headers];
            }

            public function columnFormats(): array
            {
                return $this->formats;
            }
        }, 'template-'.$dataset.'.xlsx');
    }
}
