<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\EquipmentRelocation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Export a round-trippable representation of the equipment_relocations table.
 *
 * The audit fields are intentionally exported for visibility, but are ignored
 * on import so a spreadsheet cannot overwrite system audit data.
 */
class EquipmentRelocationExport extends DefaultValueBinder implements FromQuery, WithCustomValueBinder, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function query(): Builder
    {
        return EquipmentRelocation::query()->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id',
            'donor_uniq_key',
            'donor_acceptor',
            'site_target_source',
            'pic',
            'progress',
            'remark',
            'created_by',
            'updated_by',
            'created_at',
            'updated_at',
        ];
    }

    /** @param EquipmentRelocation $row */
    public function map($row): array
    {
        return [
            $row->id,
            $row->donor_uniq_key,
            $row->donor_acceptor,
            $row->site_target_source,
            $row->pic,
            $row->progress,
            $row->remark,
            $row->created_by,
            $row->updated_by,
            $row->created_at?->format('Y-m-d H:i:s'),
            $row->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Keep database strings as literal Excel text. This preserves leading
     * zeroes in keys and prevents spreadsheet formula interpretation.
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }
}
