<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\ListrikInbuilding;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class ListrikInbuildingExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function chunkSize(): int
    {
        return 500;
    }

    public function query(): Builder
    {
        return ListrikInbuilding::query()->orderBy('site_id');
    }

    public function headings(): array
    {
        return [
            'No',
            'Site ID',
            'Site Name',
            'Status',
            'Nama BM',
            'No NPWP',
            'Alamat',
            'Telkomsel / TP',
            'Daya',
            'Harga/kWh',
            'Update By',
            'Tanggal',
        ];
    }

    /** @param ListrikInbuilding $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->site_id,
            $row->site_name ?? '-',
            $row->status ?? 'Active',
            $row->nama_bm ?? '-',
            $row->no_npwp ?? '-',
            $row->alamat ?? '-',
            $row->telkomsel_tp ?? '-',
            $row->daya ? number_format($row->daya, 0, ',', '.') : '-',
            $row->harga_per_kwh ? (float) $row->harga_per_kwh : 0,
            $row->update_by ?? '-',
            $row->tanggal ? $row->tanggal->format('d-m-Y') : '-',
        ];
    }
}
