<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class DataAssetTowerExport implements FromQuery, WithHeadings, WithMapping, WithEvents
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
            'No', 'Site ID', 'Site Name', 'Site Company', 'Site Type',
            'Grouping', 'Brand', 'Part Name', 'Owner', 'Ownership Status',
            'Note', 'Tower Height', 'Building Height', 'Tower Type',
            'Update By', 'Tanggal Update',
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
            $value($row->site_company),
            $value($row->site_type),
            $value($row->grouping),
            $value($row->brand),
            $value($row->part_name),
            $value($row->owner),
            $value($row->ownership_status),
            $value($row->note),
            $value($row->tower_height),
            $value($row->building_height),
            $value($row->tower_type),
            $value($row->update_by),
            $value($row->tanggal_update?->format('Y-m-d')),
        ];
    }
}
