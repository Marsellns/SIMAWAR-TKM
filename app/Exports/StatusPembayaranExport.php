<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\ListrikPln;
use App\Models\StatusPembayaran;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class StatusPembayaranExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(
        private readonly ?int $listrikPlnId = null
    ) {}

    public function query(): Builder
    {
        $query = StatusPembayaran::query()->orderByDesc('tahun')->orderByDesc('bulan');

        if ($this->listrikPlnId !== null) {
            $query->where('listrik_pln_id', $this->listrikPlnId);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Pelanggan',
            'Bulan',
            'Tahun',
            'Harga (Rp)',
            'Remark',
            'Update By',
            'Tanggal',
        ];
    }

    /** @param StatusPembayaran $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        $namaBulan = self::BULAN_NAMA[(int)$row->bulan] ?? (string)$row->bulan;

        return [
            $no,
            $row->id_pelanggan,
            $namaBulan,
            $row->tahun,
            (float) $row->harga,
            $row->remark ?? '-',
            $row->update_by ?? '-',
            $row->tanggal?->format('d-m-Y') ?? '-',
        ];
    }
}
