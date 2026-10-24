<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\RecurringIpas;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class RecurringIpasExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function query(): Builder
    {
        return RecurringIpas::query()->orderBy('site_code');
    }

    public function headings(): array
    {
        return [
            'No', 'Site ID', 'Site Name', 'Alamat',
            'Contract Description', 'Source ID', 'SOW Detail', 'SOW ID',
            'Year Amount', 'Start Date', 'End Date',
        ];
    }

    /** @param RecurringIpas $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->site_code,
            $row->site_name,
            $row->alamat,
            $row->contract_description,
            $row->source_id,
            $row->sow_detail,
            $row->sow_id,
            $row->year_amount,
            $row->start_date?->format('Y-m-d'),
            $row->end_date?->format('Y-m-d'),
        ];
    }
}
