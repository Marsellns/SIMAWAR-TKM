<?php

namespace App\Http\Controllers;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Exports\RecurringTagihanSitesExport;
use App\Imports\Datasets\BapssImport;
use App\Imports\Datasets\CombatSiteImport;
use App\Imports\Datasets\CombatWorkbookImport;
use App\Imports\Datasets\DataSiteUnlockImport;
use App\Imports\Datasets\InfrastructureRebuildImport;
use App\Imports\Datasets\JaknetContractImport;
use App\Imports\Datasets\RecurringIpasImport;
use App\Imports\Datasets\RecurringTagihanIpasImport;
use App\Imports\Datasets\SewaLahanRenewalImport;
use App\Support\InfrastructureUploadTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InfrastructureUploadController extends Controller
{
    private const DATASETS = [
        'sewa-lahan' => ['label' => 'Sewa Lahan', 'table' => 'sewa_lahan_renewals', 'import' => SewaLahanRenewalImport::class],
        'combat' => ['label' => 'Combat', 'table' => 'combat_sites', 'import' => CombatSiteImport::class],
        'recurring-ipas' => ['label' => 'Recurring ANT & Ipas', 'table' => 'recurring_ipas', 'import' => RecurringIpasImport::class],
        'recurring-tagihan-ipas' => ['label' => 'Recurring Tagihan Ipas', 'table' => 'recurring_tagihan_ipas', 'import' => RecurringTagihanIpasImport::class],
        'jaknet' => ['label' => 'Jaknet & Dapot', 'table' => 'jaknet_contracts', 'import' => JaknetContractImport::class],
        'site-unlock' => ['label' => 'Data Site Unlock', 'table' => 'data_site_unlocks', 'import' => DataSiteUnlockImport::class],
        'bapss' => ['label' => 'BAPSS', 'table' => 'bapss', 'import' => BapssImport::class],
    ];

    public function create(string $dataset): View
    {
        abort_unless(isset(self::DATASETS[$dataset]), 404);

        return view('infrastruktur.upload', ['dataset' => $dataset, 'definition' => self::DATASETS[$dataset]]);
    }

    public function template(string $dataset)
    {
        abort_unless(isset(self::DATASETS[$dataset]), 404);

        $headers = InfrastructureUploadTemplate::headers($dataset);
        $rows = InfrastructureUploadTemplate::headingRow($dataset) === 1
            ? [$headers]
            : [[self::DATASETS[$dataset]['label'].' - Template Upload'], $headers];

        return Excel::download(new class($rows, $dataset) implements FromArray, WithTitle, WithColumnFormatting, WithStyles, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $rows, private readonly string $dataset) {}

            public function array(): array
            {
                return $this->rows;
            }

            public function title(): string
            {
                return $this->dataset === 'combat' ? 'DATABASE' : 'Data';
            }

            public function columnFormats(): array
            {
                return InfrastructureUploadTemplate::dateColumns($this->dataset);
            }

            public function styles(Worksheet $sheet): array
            {
                $firstDataRow = InfrastructureUploadTemplate::headingRow($this->dataset) + 1;
                foreach ($this->columnFormats() as $column => $format) {
                    $sheet->getStyle("{$column}{$firstDataRow}:{$column}250")
                        ->getNumberFormat()->setFormatCode($format);
                }

                return [];
            }
        }, 'template-'.str_replace('-', '_', $dataset).'.xlsx');
    }

    public function export(Request $request, string $dataset)
    {
        abort_unless(isset(self::DATASETS[$dataset]), 404);

        if ($dataset === 'recurring-tagihan-ipas' && ! $request->boolean('detail')) {
            return Excel::download(new RecurringTagihanSitesExport, 'tagihan-ipas-per-site-'.now()->format('Ymd_His').'.xlsx');
        }
        if ($dataset === 'recurring-tagihan-ipas') {
            // Stream large histories instead of constructing 1.5M Excel cells in memory.
            return response()->streamDownload(function (): void {
                $output = fopen('php://output', 'wb');
                fwrite($output, "\xEF\xBB\xBF");
                $columns = ['site_code', 'site_name', 'tp', 'contract_type', 'termin', 'periode_ke', 'termin_start', 'termin_end', 'amount', 'batch_name', 'source_details'];
                fputcsv($output, ['Site ID', 'Site Name', 'TP', 'Contract Type', 'Termin', 'Periode Ke', 'Termin Start', 'Termin End', 'Amount', 'Batch Name', 'Source Details'], escape: '');
                DB::table('recurring_tagihan_ipas_terms')->orderBy('id')->chunkById(1000, function ($rows) use ($output, $columns): void {
                    foreach ($rows as $row) {
                        fputcsv($output, array_map(fn ($column) => $row->{$column}, $columns), escape: '');
                    }
                });
                fclose($output);
            }, 'tagihan-ipas-rincian-'.now()->format('Ymd_His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $definitions = [
            'site-unlock' => [
                'headers' => ['Site ID', 'Site Name', 'Class', 'City', 'Batch', 'Status', 'Final Status', 'Update By', 'Tanggal'],
                'columns' => ['site_code', 'site_name', 'site_class', 'city', 'batch', 'status', 'final_status', 'update_by', 'tanggal'],
            ],
        ];
        abort_unless(isset($definitions[$dataset]), 404);

        $definition = $definitions[$dataset];
        $rows = DB::table(self::DATASETS[$dataset]['table'])
            ->select($definition['columns'])
            ->get()
            ->map(fn ($row) => array_map(fn ($column) => $row->{$column}, $definition['columns']))
            ->all();

        array_unshift($rows, $definition['headers']);

        return Excel::download(new class($rows) implements FromArray, WithEvents {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        }, $dataset.'-'.now()->format('Ymd_His').'.xlsx');
    }

    public function store(Request $request, string $dataset): RedirectResponse
    {
        abort_unless(isset(self::DATASETS[$dataset]), 404);
        $rebuildDataset = isset(InfrastructureRebuildImport::SHEETS[$dataset]);
        $formats = $rebuildDataset ? 'xlsx,xls,xlsm,csv' : 'xlsx,xls,csv';
        $request->validate([
            'dataset_file' => 'required|file|mimes:'.$formats.'|max:51200',
            'replace_snapshot' => 'sometimes|boolean',
        ]);
        $definition = self::DATASETS[$dataset];
        $import = $dataset === 'combat'
            ? new CombatWorkbookImport
            : new $definition['import'];
        if ($dataset !== 'combat') {
            $import->incremental();
        }

        try {
            if ($rebuildDataset) {
                $file = $request->file('dataset_file');
                $path = $file->getRealPath();
                if ($path === false) {
                    throw new \RuntimeException('File upload sementara tidak dapat dibaca.');
                }
                $rebuild = app(InfrastructureRebuildImport::class);
                if ($request->boolean('replace_snapshot')) {
                    $datasets = $rebuild->read($path, sourceName: $file->getClientOriginalName());
                    $rebuild->synchronize($datasets);

                    return redirect()->route('infrastruktur.'.$dataset.'.index')
                        ->with('success', 'Snapshot Sewa Lahan dan Combat berhasil diperbarui: '
                            .count($datasets['sewa-lahan']).' dan '.count($datasets['combat']).' catatan.');
                }
                if ($rebuild->supports($path)) {
                    $datasets = $rebuild->read($path, $dataset, $file->getClientOriginalName());
                    $rebuild->merge($datasets);
                    $count = count($datasets[$dataset]);

                    return redirect()->route('infrastruktur.'.$dataset.'.index')
                        ->with('success', "Sinkronisasi {$definition['label']} berhasil: {$count} catatan dari workbook Rebuild disimpan.");
                }
                if (strtolower($file->getClientOriginalExtension()) === 'xlsm') {
                    throw new \RuntimeException('Sheet aktif Rebuild tidak ditemukan pada workbook.');
                }
            }
            DB::transaction(function () use ($dataset, $import, $request): void {
                if ($dataset === 'combat' && $import instanceof CombatWorkbookImport) {
                    $path = $request->file('dataset_file')->getRealPath();
                    if ($path === false) {
                        throw new \RuntimeException('File upload sementara tidak dapat dibaca.');
                    }
                    $import->import($path, incremental: true);
                } else {
                    Excel::import($import, $request->file('dataset_file'));
                }
                if ($import->getStats()['inserted'] === 0) {
                    throw new \RuntimeException('Tidak ada baris dengan Site ID yang valid pada file upload.');
                }
            });
        } catch (\Throwable $e) {
            if ($e instanceof ValidationException) {
                throw $e;
            }
            report($e);

            return back()->withErrors(['dataset_file' => 'File gagal diproses. Periksa format file atau hubungi administrator.'])->withInput();
        }

        $stats = $import->getStats();

        return redirect()->route('infrastruktur.'.$dataset.'.index')
            ->with('success', "Upload {$definition['label']} berhasil: {$stats['inserted']} baris disimpan, {$stats['skipped']} baris dilewati.");
    }
}
