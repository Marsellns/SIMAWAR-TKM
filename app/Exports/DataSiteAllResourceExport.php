<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class DataSiteAllResourceExport implements FromQuery, WithHeadings, WithMapping, WithEvents
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
            'No', 'Site ID (Dapot)', 'Site Name (Dapot)', 'Class (Dapot)',
            'City (Dapot)', 'NOP (Dapot)', 'Coverage (Dapot)', 'PLN (Dapot)',
            'Capacity (Dapot)', 'ID Pel (Dapot)', 'Tgl Update (Dapot)',
            'ANT Site (ANT)', 'Site Owner (ANT)', 'RTP (ANT)', 'Type (ANT)',
            'Alamat (ANT)', 'Tgl Update (ANT)', 'Contract (Ipas)',
            'Contract Type (Ipas)',
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        $value = static fn ($item) => $item === null || $item === '' ? '-' : $item;

        return [
            $no,
            $value($row->site_id),
            $value($row->site_name),
            $value($row->site_class),
            $value($row->city),
            $value($row->nop),
            $value($row->coverage_type),
            $value($row->pln_connection),
            $value($row->capacity),
            $value($row->id_pel),
            $value($row->tgl_update),
            $value($row->ant_site),
            $value($row->ant_site_owner),
            $value($row->ant_rtp),
            $value($row->ant_type),
            $value($row->ant_alamat),
            $value($row->ant_tgl_update),
            $value($row->ipas_contract),
            $value($row->ipas_contract_type),
        ];
    }
}
