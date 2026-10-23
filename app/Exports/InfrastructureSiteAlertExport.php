<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Support\CombatSourceDetails;
use App\Support\LeaseStatus;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class InfrastructureSiteAlertExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    private int $rowNumber = 0;

    public function __construct(
        private readonly string $categoryLabel,
        private readonly Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'No',
            'Kategori',
            'Site ID',
            'Site Name',
            'Status Masa Sewa',
            'Sisa Hari',
            'Tanggal Akhir',
            'Tahun Renewal / Justi',
            'Status Dokumen',
            'Status Perpanjangan',
            'Ownership / TP',
            'No PKS Baru',
            'No PKS Lama',
            'Harga Baru',
            'Total Harga Baru',
            'Keterangan',
            'Update By',
        ];
    }

    public function map($row): array
    {
        $details = CombatSourceDetails::flattened($row->source_details);
        $endDate = $row->end_date_baru ?? $row->end_date_lama;
        $daysRemaining = $endDate === null
            ? null
            : today()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false);

        return [
            ++$this->rowNumber,
            $this->categoryLabel,
            $row->site_code,
            $row->site_name,
            LeaseStatus::fromEndDate($endDate),
            $daysRemaining,
            $endDate?->format('Y-m-d'),
            $row->tahun_renewal ?? $row->tahun_justi_dirnet,
            $row->status_dokumen,
            $row->status_perpanjangan,
            $details['ownership'] ?? $details['tp'] ?? $details['vendor'] ?? null,
            $row->no_pks_baru,
            $row->no_pks_lama,
            $row->harga_baru,
            $row->total_harga_baru,
            $row->keterangan,
            $row->update_by,
        ];
    }
}
