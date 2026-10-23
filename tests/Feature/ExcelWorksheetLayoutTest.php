<?php

namespace Tests\Feature;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Exports\SewaLahanRenewalExport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

class ExcelWorksheetLayoutTest extends TestCase
{
    public function test_data_export_keeps_headers_and_values_while_improving_layout(): void
    {
        $export = new class implements FromArray, WithEvents
        {
            use HasReadableWorksheetLayout;

            public function array(): array
            {
                return [
                    ['No', 'Site ID', 'Site Name', 'Harga Baru'],
                    [1, 'SITE-001', 'Site Karawang', 1250000.5],
                ];
            }
        };

        $sheet = $this->exportSheet($export);

        $this->assertSame('Site ID', $sheet->getCell('B1')->getValue());
        $this->assertSame('SITE-001', $sheet->getCell('B2')->getValue());
        $this->assertSame(1250000.5, $sheet->getCell('D2')->getValue());
        $this->assertSame('B60020', $sheet->getStyle('A1')->getFill()->getStartColor()->getRGB());
        $this->assertSame('D2', $sheet->getFreezePane());
        $this->assertSame('A1:D2', $sheet->getAutoFilter()->getRange());
        $this->assertSame(34.0, $sheet->getColumnDimension('C')->getWidth());
        $this->assertSame('#,##0.00', $sheet->getStyle('D2')->getNumberFormat()->getFormatCode());
    }

    public function test_upload_template_keeps_title_and_header_rows(): void
    {
        $export = new class implements FromArray, WithColumnWidths, WithEvents
        {
            use HasReadableWorksheetLayout;

            public function array(): array
            {
                return [['Sewa Lahan - Template Upload'], ['Site ID', 'Site Name', 'Harga Baru']];
            }

            public function columnWidths(): array
            {
                return ['B' => 42];
            }
        };

        $sheet = $this->exportSheet($export);

        $this->assertSame('Sewa Lahan - Template Upload', $sheet->getCell('A1')->getValue());
        $this->assertSame('Site ID', $sheet->getCell('A2')->getValue());
        $this->assertSame('Site Name', $sheet->getCell('B2')->getValue());
        $this->assertSame(2, $sheet->getHighestRow());
        $this->assertSame('B60020', $sheet->getStyle('A2')->getFill()->getStartColor()->getRGB());
        $this->assertSame('A3', $sheet->getFreezePane());
        $this->assertSame(42.0, $sheet->getColumnDimension('B')->getWidth());
    }

    public function test_wide_sewa_lahan_export_has_readable_columns_without_moving_data(): void
    {
        $headings = (new SewaLahanRenewalExport)->headings();
        $export = new class($headings) implements FromArray, WithEvents
        {
            use HasReadableWorksheetLayout;

            public function __construct(private readonly array $headings) {}

            public function array(): array
            {
                return [$this->headings, array_fill(0, count($this->headings), null)];
            }
        };

        $sheet = $this->exportSheet($export);

        $this->assertCount(92, $headings);
        $this->assertSame('CN', $sheet->getHighestColumn());
        $this->assertSame('D2', $sheet->getFreezePane());
        $this->assertSame(40.0, $sheet->getRowDimension(1)->getRowHeight());
        $this->assertSame(29.0, $sheet->getColumnDimension('G')->getWidth());
        $this->assertSame(21.0, $sheet->getColumnDimension('H')->getWidth());
        $this->assertSame(23.0, $sheet->getColumnDimension('J')->getWidth());
    }

    public function test_empty_export_does_not_gain_an_extra_data_row(): void
    {
        $export = new class implements FromArray, WithEvents
        {
            use HasReadableWorksheetLayout;

            public function array(): array
            {
                return [['Site ID', 'Site Name']];
            }
        };

        $sheet = $this->exportSheet($export);

        $this->assertSame(1, $sheet->getHighestRow());
        $this->assertSame('A2', $sheet->getFreezePane());
    }

    public function test_existing_template_date_format_is_preserved(): void
    {
        $export = new class implements FromArray, WithColumnFormatting, WithEvents
        {
            use HasReadableWorksheetLayout;

            public function array(): array
            {
                return [
                    ['Site ID', 'Tanggal Penawaran 1', 'Harga Baru'],
                    ['SITE-001', null, 1250000],
                ];
            }

            public function columnFormats(): array
            {
                return ['B' => 'dd mmm yyyy'];
            }
        };

        $sheet = $this->exportSheet($export);

        $this->assertSame('dd mmm yyyy', $sheet->getStyle('B2')->getNumberFormat()->getFormatCode());
        $this->assertSame('#,##0.00', $sheet->getStyle('C2')->getNumberFormat()->getFormatCode());
    }

    private function exportSheet(object $export): Worksheet
    {
        $path = tempnam(sys_get_temp_dir(), 'excel-layout-');
        file_put_contents($path, ExcelFacade::raw($export, Excel::XLSX));

        try {
            $workbook = IOFactory::load($path);

            return $workbook->getActiveSheet();
        } finally {
            unlink($path);
        }
    }
}
