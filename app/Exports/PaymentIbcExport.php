<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\Read\PaymentIbc;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class PaymentIbcExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    protected ?int $bulan;
    protected ?int $tahun;
    protected ?string $status;

    public function __construct(?int $bulan = null, ?int $tahun = null, ?string $status = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->status = $status;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function query(): Builder
    {
        $query = PaymentIbc::query()->orderBy('site_id');

        if ($this->bulan) {
            $query->where('bulan', $this->bulan);
        }
        if ($this->tahun) {
            $query->where('tahun', $this->tahun);
        }
        if ($this->status && in_array($this->status, ['Done', 'Pending'])) {
            $query->where('status', $this->status);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Site ID',
            'Site Name',
            'Nama BM',
            'Daya',
            'Status',
            'Jumlah Tagihan',
            'Invoice',
            'Update By',
            'Tanggal Update Status',
        ];
    }

    /** @param PaymentIbc $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->site_id,
            $row->site_name ?? '-',
            $row->nama_bm ?? '-',
            $row->daya ? number_format($row->daya, 0, ',', '.') : '-',
            $row->status,
            $row->jumlah_tagihan ? (float) $row->jumlah_tagihan : 0,
            $row->invoice ?? '-',
            $row->update_by ?? '-',
            $row->tanggal_update_status ? $row->tanggal_update_status->format('d-m-Y') : '-',
        ];
    }
}
