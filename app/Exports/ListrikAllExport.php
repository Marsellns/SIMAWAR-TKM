<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\ListrikAll;
use App\Models\Read\PaymentPlnMasterMonthly;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class ListrikAllExport implements FromCollection, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function __construct(
        private readonly ?int $tahun = null
    ) {}

    public function chunkSize(): int
    {
        return 1000;
    }

    public function collection(): Collection
    {
        $year = $this->tahun ?? max((int) \App\Models\ListrikAll::max('tahun'), (int) PaymentPlnMasterMonthly::max('tahun'), (int) now()->year);
        return \App\Support\ListrikAllQuery::forYear($year)->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Pelanggan',
            'Site ID',
            'Site Name',
            'Gol Tarif',
            'Unit PLN',
            'Tahun',
            'Jan',
            'Feb',
            'Mar',
            'Apr',
            'Mei',
            'Jun',
            'Jul',
            'Ags',
            'Sep',
            'Okt',
            'Nov',
            'Des',
        ];
    }

    /** @param ListrikAll $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->id_pelanggan,
            $row->site_id,
            $row->site_name,
            $row->gol_tarif,
            $row->unit_pln,
            $row->tahun,
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
