<?php

namespace App\Support;

use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

final class ExcelWorksheetLayout
{
    public static function apply(AfterSheet $event): void
    {
        $export = $event->getConcernable();
        $sheet = $event->sheet->getDelegate();
        // Upload templates with a title in A1 keep their existing row-two headers.
        $headerRow = (! $sheet->cellExists('B1') || $sheet->getCell('B1')->getValue() === null)
            && $sheet->cellExists('B2')
            && $sheet->getCell('B2')->getValue() !== null ? 2 : 1;
        $lastColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn($headerRow));

        while ($lastColumnIndex > 0 && trim((string) $sheet->getCellByColumnAndRow($lastColumnIndex, $headerRow)->getValue()) === '') {
            $lastColumnIndex--;
        }

        if ($lastColumnIndex === 0) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex($lastColumnIndex);
        $lastRow = $sheet->getHighestRow();
        $customWidths = $export instanceof WithColumnWidths ? $export->columnWidths() : [];
        $headings = [];

        for ($index = 1; $index <= $lastColumnIndex; $index++) {
            $column = Coordinate::stringFromColumnIndex($index);
            $heading = trim((string) $sheet->getCellByColumnAndRow($index, $headerRow)->getValue());
            $headings[] = $heading;

            if (! array_key_exists($column, $customWidths)) {
                $sheet->getColumnDimension($column)
                    ->setAutoSize(false)
                    ->setWidth(self::columnWidth($heading));
            }
        }

        $sheet->getDefaultRowDimension()->setRowHeight(21);
        $sheet->getRowDimension($headerRow)->setRowHeight(40);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B60020']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
                'indent' => 1,
            ],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '8B0018']],
            ],
        ]);

        if ($headerRow > 1) {
            $sheet->getRowDimension(1)->setRowHeight(29);
            $sheet->getStyle("A1:{$lastColumn}1")->applyFromArray([
                'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '6E1022']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FCE8EC']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        if ($sheet->getFreezePane() === null) {
            $keepSiteColumns = $headerRow === 1
                && count($headings) >= 3
                && strcasecmp($headings[0], 'No') === 0
                && strcasecmp($headings[1], 'Site ID') === 0
                && strcasecmp($headings[2], 'Site Name') === 0;

            $sheet->freezePane(($keepSiteColumns ? 'D' : 'A').($headerRow + 1));
        }

        if ($lastRow > $headerRow && $sheet->getAutoFilter()->getRange() === '') {
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        }

        if ($lastRow > $headerRow) {
            foreach ($headings as $index => $heading) {
                if (preg_match('/tanggal|date|^tgl\b|status|periode|month/i', $heading)
                    || ! preg_match('/harga|penawaran|nego|revenue|cost|pnl|profit.?loss|amount|tagihan|\(rp\)/i', $heading)) {
                    continue;
                }

                $column = Coordinate::stringFromColumnIndex($index + 1);
                $sheet->getStyle($column.($headerRow + 1).":{$column}{$lastRow}")
                    ->getNumberFormat()->setFormatCode('#,##0.00');
            }
        }
    }

    private static function columnWidth(string $heading): float
    {
        if (preg_match('/alamat|address|keterangan|remark|description|source details|batch name/i', $heading)) {
            return 52;
        }

        if (preg_match('/site name|nama site|nama pelanggan/i', $heading)) {
            return 34;
        }

        if (preg_match('/donor_uniq_key|no\s+(?:pks|bak|sip|surat|npwp)|nomor surat|contract.*(?:no|number)/i', $heading)) {
            return 29;
        }

        if (preg_match('/site id|site code|id pelanggan|donor_uniq_key|\bnop\b/i', $heading)) {
            return 21;
        }

        if (preg_match('/tanggal|date|created_at|updated_at|^tgl\b/i', $heading)) {
            return 21;
        }

        if (preg_match('/harga|penawaran|nego|revenue|cost|pnl|profit|loss|amount|tagihan|\brp\b/i', $heading)) {
            return 23;
        }

        return min(38, max(16, mb_strlen($heading) + 5));
    }
}
