<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\ListrikPln;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ListrikPlnExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents
{
    use HasReadableWorksheetLayout;

    public function __construct(
        private readonly ?int $tahun = null,
        private readonly ?int $bulan = null,
        private readonly ?string $nop = null,
    ) {}

    public function collection(): Enumerable
    {
        return ListrikPln::query()
            ->when($this->tahun !== null, fn ($query) => $query->whereYear('tanggal', $this->tahun))
            ->when($this->bulan !== null, fn ($query) => $query->whereMonth('tanggal', $this->bulan))
            ->when($this->nop !== null, fn ($query) => $query->whereRaw(
                "REPLACE(REPLACE(UPPER(TRIM(nop)), 'NOP ', ''), 'NOP-', '') = ?",
                [strtoupper(trim($this->nop))]
            ))
            ->latest('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'ID Pelanggan',
            'Site ID',
            'Site Name',
            'Alamat',
            'Status Aktif Site',
            'Nama Pelanggan',
            'Daya (VA)',
            'Phasa',
            'Status AMR',
            'Gol Tarif',
            'Unit Layanan PLN',
            'Jenis Bayar',
            'TP Owner',
            'NOP',
            'Update By',
            'Tanggal',
            'Created At',
            'Updated At',
        ];
    }

    public function map($item): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $item->id_pelanggan,
            $item->site_id,
            $item->site_name,
            $item->alamat,
            $item->status_aktif_site,
            $item->nama_pelanggan,
            $item->daya_va,
            $item->phasa,
            $item->status_amr,
            $item->gol_tarif,
            $item->unit_layanan_pln,
            $item->jenis_bayar,
            $item->tp_owner,
            $item->nop,
            $item->update_by,
            $item->tanggal?->format('d-m-Y') ?? '-',
            $item->created_at?->format('d-m-Y H:i') ?? '-',
            $item->updated_at?->format('d-m-Y H:i') ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): ?array
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']]],
        ];
    }
}
