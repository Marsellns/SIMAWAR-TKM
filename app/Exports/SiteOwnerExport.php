<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class SiteOwnerExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use HasReadableWorksheetLayout;

    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'No', 'Site ID', 'Site Name', 'Site Class', 'Alamat', 'City',
            'NOP', 'Coverage Type', 'Status', 'PLN Connection', 'Capacity',
            'ID Pel', 'Tower Height', 'Site Owner', 'Tgl Update',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->site_code,
            $row->site_name,
            $row->site_class,
            $row->alamat,
            $row->city,
            $row->nop,
            $row->coverage_type,
            $row->status_mla,
            $row->pln_connection,
            $row->capacity,
            $row->id_pel,
            $row->tower_height,
            $row->site_owner,
            $row->tgl_update?->format('Y-m-d'),
        ];
    }
}
