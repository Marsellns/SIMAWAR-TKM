<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\Read\InbuildingAll;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class InbuildingAllExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    protected int $tahun;

    public function __construct(int $tahun = 2025)
    {
        $this->tahun = $tahun;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function query(): Builder
    {
        return InbuildingAll::query()
            ->where('tahun', $this->tahun)
            ->orderBy('site_id');
    }

    public function headings(): array
    {
        return [
            'No',
            'Site ID',
            'Site Name',
            'Status',
            'Nama BM',
            'TP/NONTP',
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
    }

    /** @param InbuildingAll $row */
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
            $row->tp_nontp ?? '-',
            (float) $row->jan,
            (float) $row->feb,
            (float) $row->mar,
            (float) $row->apr,
            (float) $row->mei,
            (float) $row->jun,
            (float) $row->jul,
            (float) $row->ags,
            (float) $row->sep,
            (float) $row->okt,
            (float) $row->nov,
            (float) $row->des,
        ];
    }
}
