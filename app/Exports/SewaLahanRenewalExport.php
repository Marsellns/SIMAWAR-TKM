<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\SewaLahanRenewal;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;

class SewaLahanRenewalExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    protected ?int $tahun;

    private const MONTHS_2025 = ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    private const MONTHS_2026 = ['jan', 'feb', 'mar', 'apr', 'may', 'jun'];

    public function __construct(?int $tahun = null)
    {
        $this->tahun = $tahun;
    }

    public function query(): Builder
    {
        $query = SewaLahanRenewal::query()->orderBy('site_code');

        if ($this->tahun) {
            $query->where('tahun_renewal', $this->tahun);
        }

        return $query;
    }

    public function headings(): array
    {
        $headings = [
            'No', 'Site ID', 'Site Name', 'Tahun Renewal', 'Status Dokumen',
            'Status Perpanjangan',
            'No PKS Baru', 'Start Date Baru', 'End Date Baru', 'Harga Baru', 'Total Harga Baru',
            'No BAK Baru', 'Tgl BAK Baru', 'No SIP', 'Tgl Terima SIP',
            'Penawaran 1', 'Nego 1', 'Penawaran 2', 'Nego 2', 'Penawaran 3', 'Nego 3',
            'No PKS Lama', 'Start Date Lama', 'End Date Lama', 'Harga Lama',
            'Area', 'NOP', 'Ownership', 'Vendor', 'Masa Sewa/TH',
            'Tgl Negosiasi', 'Tgl PKS/BAK ke Owner', 'Tgl PKS ke Legal', 'Tgl Finance', 'Tgl Paid',
            'Keterangan', 'Update By', 'Tgl Update',
        ];

        // Monthly PnL 2025
        foreach (self::MONTHS_2025 as $m) {
            $u = ucfirst($m);
            $headings[] = "Rev {$u}-25";
            $headings[] = "Cost {$u}-25";
            $headings[] = "PnL {$u}-25";
        }

        // Monthly PnL 2026
        foreach (self::MONTHS_2026 as $m) {
            $u = ucfirst($m);
            $headings[] = "Rev {$u}-26";
            $headings[] = "Cost {$u}-26";
            $headings[] = "PnL {$u}-26";
        }

        return $headings;
    }

    /**
     * @param SewaLahanRenewal $row
     */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        $d = is_array($row->source_details)
            ? $row->source_details
            : (json_decode($row->source_details ?? '[]', true) ?: []);

        $mapped = [
            $no,
            $row->site_code,
            $row->site_name,
            $row->tahun_renewal,
            $row->status_dokumen,
            $row->status_perpanjangan,
            $row->no_pks_baru,
            $row->start_date_baru?->format('Y-m-d'),
            $row->end_date_baru?->format('Y-m-d'),
            $row->harga_baru,
            $row->total_harga_baru,
            $row->no_bak_baru,
            $row->tgl_bak_baru?->format('Y-m-d'),
            $row->no_sip,
            $row->tgl_terima_sip?->format('Y-m-d'),
            $row->penawaran_1,
            $row->nego_1,
            $row->penawaran_2,
            $row->nego_2,
            $row->penawaran_3,
            $row->nego_3,
            $row->no_pks_lama,
            $row->start_date_lama?->format('Y-m-d'),
            $row->end_date_lama?->format('Y-m-d'),
            $row->harga_lama,
            $d['area'] ?? null,
            $d['nop'] ?? null,
            $d['ownership'] ?? null,
            $d['vendor'] ?? null,
            $d['masa_sewath'] ?? $d['masa_sewa_th'] ?? null,
            $d['tgl_negosiasi'] ?? null,
            $d['tgl_pksbak_ke_owner'] ?? null,
            $d['tgl_pks_ke_legal'] ?? null,
            $d['tgl_finance'] ?? null,
            $d['tgl_paid'] ?? null,
            $row->keterangan,
            $row->update_by,
            $row->tgl_update?->format('Y-m-d'),
        ];

        // 2025 PnL
        foreach (self::MONTHS_2025 as $m) {
            $mapped[] = $d["rev_{$m}_25"] ?? $d["rev_{$m}-25"] ?? null;
            $mapped[] = $d["cost_{$m}_25"] ?? $d["cost_{$m}-25"] ?? null;
            $mapped[] = $d["pnl_{$m}_25"] ?? $d["pnl_{$m}-25"] ?? null;
        }

        // 2026 PnL
        foreach (self::MONTHS_2026 as $m) {
            $altM = $m === 'may' ? 'mei' : $m;
            $mapped[] = $d["rev_{$m}_26"] ?? $d["rev_{$altM}_26"] ?? null;
            $mapped[] = $d["cost_{$m}_26"] ?? $d["cost_{$altM}_26"] ?? null;
            $mapped[] = $d["pnl_{$m}_26"] ?? $d["pnl_{$altM}_26"] ?? null;
        }

        return $mapped;
    }
}
