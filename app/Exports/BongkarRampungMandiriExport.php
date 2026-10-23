<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\BongkarRampungMandiri;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class BongkarRampungMandiriExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function query(): Builder
    {
        return BongkarRampungMandiri::query()->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Pelanggan',
            'Site ID',
            'Site Name',
        ];
    }

    /** @param BongkarRampungMandiri $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->id_pelanggan,
            $row->site_id,
            $row->site_name ?? '-',
        ];
    }
}
