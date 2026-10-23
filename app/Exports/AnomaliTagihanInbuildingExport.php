<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\Read\AnomaliTagihanInbuilding;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class AnomaliTagihanInbuildingExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    private int $rowNumber = 0;

    protected ?int $bulan;
    protected ?int $tahun;

    public function __construct(?int $bulan = null, ?int $tahun = null)
    {
        $this->bulan = $bulan;
        $this->tahun = $tahun;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function query(): Builder
    {
        // Threshold: Kenaikan > 50%
        $query = AnomaliTagihanInbuilding::query()
            ->where('kenaikan_persen', '>', 50)
            ->orderBy('site_id');

        if ($this->tahun && $this->bulan) {
            $code = sprintf('%04d-%02d', $this->tahun, $this->bulan);
            $query->where('periode_saat_ini', $code);
        } elseif ($this->tahun) {
            $query->where('periode_saat_ini', 'like', $this->tahun . '-%');
        } elseif ($this->bulan) {
            $mCode = sprintf('-%02d', $this->bulan);
            $query->where('periode_saat_ini', 'like', '%' . $mCode);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No',
            'Site ID',
            'Periode Sebelumnya',
            'Tagihan Sebelumnya',
            'Periode Saat Ini',
            'Tagihan Saat Ini',
            'Selisih',
            'Kenaikan (%)',
        ];
    }

    /** @param AnomaliTagihanInbuilding $row */
    public function map($row): array
    {
        return [
            ++$this->rowNumber,
            $row->site_id,
            $row->periode_sebelumnya,
            (float) $row->tagihan_sebelumnya,
            $row->periode_saat_ini,
            (float) $row->tagihan_saat_ini,
            (float) $row->selisih,
            (float) $row->kenaikan_persen,
        ];
    }
}
