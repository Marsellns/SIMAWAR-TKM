<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\Read\PaymentPln;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class PaymentPlnExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    public function chunkSize(): int
    {
        return 2000;
    }

    private const BULAN_NAMA = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function __construct(
        private readonly ?int $bulan = null,
        private readonly ?int $tahun = null,
        private readonly ?string $status = null,
        private readonly ?string $nop = null,
    ) {}

    public function query(): Builder
    {
        $query = PaymentPln::query()->orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id');

        if ($this->bulan !== null) {
            $query->where('bulan', $this->bulan);
        }

        if ($this->tahun !== null) {
            $query->where('tahun', $this->tahun);
        }

        if ($this->status !== null && in_array($this->status, ['Done', 'Pending'])) {
            $query->where('status', $this->status);
        }

        if ($this->nop !== null) {
            $query->whereExists(function ($nopQuery) {
                $nopQuery->selectRaw('1')
                    ->from('listrik_pln as payment_nop_listrik')
                    ->whereRaw('UPPER(TRIM(payment_nop_listrik.site_id)) = UPPER(TRIM(payment_pln_effective.site_id))')
                    ->whereRaw(
                        "REPLACE(REPLACE(UPPER(TRIM(payment_nop_listrik.nop)), 'NOP ', ''), 'NOP-', '') = ?",
                        [strtoupper(trim($this->nop))]
                    );
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Pelanggan',
            'Site ID',
            'Site Name',
            'Daya (VA)',
            'Phasa',
            'Gol Tarif',
            'Unit PLN',
            'Harga (Rp)',
            'Update By',
            'Tanggal Status',
            'Status',
            'Bulan',
            'Tahun',
        ];
    }

    /** @param PaymentPln $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        $namaBulan = self::BULAN_NAMA[(int)$row->bulan] ?? (string)$row->bulan;

        return [
            $no,
            $row->id_pelanggan,
            $row->site_id,
            $row->site_name,
            $row->daya,
            $row->phasa,
            $row->gol_tarif,
            $row->unit_pln,
            (float) $row->harga,
            $row->update_by ?? '-',
            $row->tanggal_status?->format('d-m-Y') ?? '-',
            $row->status,
            $namaBulan,
            $row->tahun,
        ];
    }
}
