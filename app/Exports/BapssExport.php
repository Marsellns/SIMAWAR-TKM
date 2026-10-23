<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\Bapss;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class BapssExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function query(): Builder
    {
        return Bapss::query()->orderBy('site_code');
    }

    public function headings(): array
    {
        return [
            'No', 'Site ID', 'Site Name', 'Tgl BAPSS', 'Tgl Dismantle',
            'Remark', 'PDF BAPSS', 'PDF BA Dismantle', 'Update By', 'Tgl Update',
        ];
    }

    /** @param Bapss $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->site_code,
            $row->site_name,
            $row->tgl_bapss?->format('Y-m-d') ?? '-',
            $row->tgl_dismantle?->format('Y-m-d') ?? '-',
            $row->remark,
            $row->pdf_bapss ?: '-',
            $row->pdf_ba_dismantle ?: '-',
            $row->update_by,
            $row->tgl_update?->format('Y-m-d H:i') ?? '-',
        ];
    }
}
