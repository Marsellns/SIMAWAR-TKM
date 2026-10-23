<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class PoVarcostExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use HasReadableWorksheetLayout;

    public function __construct(private readonly Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['PO Number', 'Capex/Opex', 'Description', 'GR Status', 'PO Year', 'Delivery Date', 'Update By', 'Update At'];
    }

    public function map($po): array
    {
        return [
            $po->po_number,
            $po->expense_type,
            $po->description,
            $po->gr_status,
            $po->po_year,
            $po->delivery_date?->format('Y-m-d'),
            $po->update_by,
            $po->update_at?->format('Y-m-d H:i:s'),
        ];
    }
}
