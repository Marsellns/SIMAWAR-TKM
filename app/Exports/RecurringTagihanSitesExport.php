<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\RecurringTagihanIpas;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecurringTagihanSitesExport implements FromQuery, WithColumnWidths, WithHeadings, WithMapping, WithStyles, WithTitle, WithEvents
{
    use HasReadableWorksheetLayout;

    public function query()
    {
        return RecurringTagihanIpas::query()->orderBy('site_code');
    }

    public function title(): string
    {
        return 'Ringkasan per Site';
    }

    public function headings(): array
    {
        return ['Site ID', 'Site Name', 'TP', 'Contract Type', 'Termin', 'Periode Ke', 'Rentang Termin', 'Jumlah Rincian', 'Total Tagihan', 'Batch Name'];
    }

    public function map($row): array
    {
        return [$row->site_code, $row->site_name, $row->tp, $row->contract_type, $row->termin, $row->periode_ke,
            ($row->termin_start?->format('d-m-Y') ?? '—').' s.d. '.($row->termin_end?->format('d-m-Y') ?? '—'),
            $row->term_count, $row->amount, $row->batch_name];
    }

    public function columnWidths(): array
    {
        return ['A' => 16, 'B' => 40, 'C' => 38, 'D' => 20, 'E' => 18, 'F' => 18, 'G' => 32, 'H' => 18, 'I' => 25, 'J' => 45];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('B2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        $sheet->getStyle('A1:J'.$sheet->getHighestRow())->getAlignment()->setVertical('top')->setWrapText(true);
        $sheet->getStyle('I2:I'.$sheet->getHighestRow())->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'B60020']]]];
    }
}
