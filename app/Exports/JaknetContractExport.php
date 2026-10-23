<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\JaknetContract;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;

class JaknetContractExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    protected bool $hideUnlock;

    public function __construct(bool $hideUnlock = false)
    {
        $this->hideUnlock = $hideUnlock;
    }

    public function query(): Builder
    {
        $query = JaknetContract::query()->orderBy('site_code');

        if ($this->hideUnlock) {
            $query->where(function ($q) {
                $q->where('is_site_unlock', false)->orWhereNull('is_site_unlock');
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No', 'Site ID', 'Site Name', 'No. PKS',
            'Tanggal Mulai', 'Tanggal Berakhir', 'Contact Person',
            'Contact Address', 'Telp', 'Nilai', 'Nilai/Thn',
            'Tahun Berakhir', 'Tgl Update', 'Site Unlock',
        ];
    }

    /** @param JaknetContract $row */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        return [
            $no,
            $row->site_code,
            $row->site_name,
            $row->no_pks,
            $row->tanggal_mulai?->format('Y-m-d'),
            $row->tanggal_berakhir?->format('Y-m-d'),
            $row->contact_person,
            $row->contact_address,
            $row->telp,
            $row->nilai,
            $row->nilai_per_tahun,
            $row->tahun_berakhir,
            $row->tgl_update?->format('Y-m-d'),
            $row->is_site_unlock ? 'Ya' : 'Tidak',
        ];
    }
}
