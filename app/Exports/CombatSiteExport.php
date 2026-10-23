<?php

namespace App\Exports;

use App\Exports\Concerns\HasReadableWorksheetLayout;
use App\Models\CombatSite;
use App\Support\CombatSourceDetails;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;

class CombatSiteExport implements FromQuery, WithHeadings, WithMapping, WithEvents
{
    use Exportable;
    use HasReadableWorksheetLayout;

    protected ?int $tahun;

    public function __construct(?int $tahun = null)
    {
        $this->tahun = $tahun;
    }

    public function query(): Builder
    {
        $query = CombatSite::query()->orderBy('site_code');

        if ($this->tahun) {
            $query->where('tahun_justi_dirnet', $this->tahun);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'No', 'Site ID', 'Site Name', 'Tahun Justi Dirnet', 'Status Dokumen',
            'Status Perpanjangan',
            'No PKS Baru', 'Start Date Baru', 'End Date Baru', 'Harga Baru', 'Total Harga Baru',
            'Penawaran 1', 'Nego 1', 'Penawaran 2', 'Nego 2', 'Penawaran 3', 'Nego 3',
            'No PKS Lama', 'Start Date Lama', 'End Date Lama', 'Harga Lama',
            'Nomor Surat', 'Keterangan', 'Update By', 'Tanggal',
            // Blok Monthly PnL 2026
            'Revenue Jan 2026', 'Cost Jan 2026', 'PnL Jan 2026',
            'Revenue Feb 2026', 'Cost Feb 2026', 'PnL Feb 2026',
            'Revenue Mar 2026', 'Cost Mar 2026', 'PnL Mar 2026',
            'Revenue Apr 2026', 'Cost Apr 2026', 'PnL Apr 2026',
            'Revenue Mei 2026', 'Cost Mei 2026', 'PnL Mei 2026',
            'Revenue Jun 2026', 'Cost Jun 2026', 'PnL Jun 2026',
            'Revenue Jul 2026', 'Cost Jul 2026', 'PnL Jul 2026',
            'Revenue Aug 2026', 'Cost Aug 2026', 'PnL Aug 2026',
            // Profitability & Operasional
            'Revenue Month', 'Margin Direct', 'Profitability', 'Status Revenue', 'Status Payload', 'Status Sewa',
            // Detail Teknis & Lokasi
            'TP / Vendor', 'NOP', 'Region', 'Alamat', 'Desa', 'Kecamatan', 'Kabupaten',
            'Status Combat', 'Height Combat (m)', 'Height RF', 'Date Connect',
        ];
    }

    /**
     * @param CombatSite $row
     */
    public function map($row): array
    {
        static $no = 0;
        $no++;

        $d = CombatSourceDetails::flattened($row->source_details);

        return [
            $no,
            $row->site_code,
            $row->site_name,
            $row->tahun_justi_dirnet,
            $row->status_dokumen,
            $row->status_perpanjangan,
            $row->no_pks_baru,
            $row->start_date_baru?->format('Y-m-d'),
            $row->end_date_baru?->format('Y-m-d'),
            $row->harga_baru,
            $row->total_harga_baru,
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
            $row->nomor_surat,
            $row->keterangan,
            $row->update_by,
            $row->tanggal?->format('Y-m-d'),
            // Blok Monthly PnL 2026
            $row->revenue_jan_2026 ?? $d['rev_jan_26'] ?? $d['revenue_jan_2026'] ?? null,
            $row->cost_jan_2026 ?? $d['cost_jan_26'] ?? $d['cost_jan_2026'] ?? null,
            $row->pnl_jan_2026 ?? $d['pnl_jan_26'] ?? $d['pnl_jan_2026'] ?? null,

            $row->revenue_feb_2026 ?? $d['rev_feb_26'] ?? $d['revenue_feb_2026'] ?? null,
            $row->cost_feb_2026 ?? $d['cost_feb_26'] ?? $d['cost_feb_2026'] ?? null,
            $row->pnl_feb_2026 ?? $d['pnl_feb_26'] ?? $d['pnl_feb_2026'] ?? null,

            $row->revenue_mar_2026 ?? $d['rev_mar_26'] ?? $d['revenue_mar_2026'] ?? null,
            $row->cost_mar_2026 ?? $d['cost_mar_26'] ?? $d['cost_mar_2026'] ?? null,
            $row->pnl_mar_2026 ?? $d['pnl_mar_26'] ?? $d['pnl_mar_2026'] ?? null,

            $row->revenue_apr_2026 ?? $d['rev_apr_26'] ?? $d['revenue_apr_2026'] ?? null,
            $row->cost_apr_2026 ?? $d['cost_apr_26'] ?? $d['cost_apr_2026'] ?? null,
            $row->pnl_apr_2026 ?? $d['pnl_apr_26'] ?? $d['pnl_apr_2026'] ?? null,

            $row->revenue_mei_2026 ?? $d['rev_mei_26'] ?? $d['revenue_mei_2026'] ?? null,
            $row->cost_mei_2026 ?? $d['cost_mei_26'] ?? $d['cost_mei_2026'] ?? null,
            $row->pnl_mei_2026 ?? $d['pnl_mei_26'] ?? $d['pnl_mei_2026'] ?? null,

            $row->revenue_jun_2026 ?? $d['rev_jun_26'] ?? $d['revenue_jun_2026'] ?? null,
            $row->cost_jun_2026 ?? $d['cost_jun_26'] ?? $d['cost_jun_2026'] ?? null,
            $row->pnl_jun_2026 ?? $d['pnl_jun_26'] ?? $d['pnl_jun_2026'] ?? null,

            $d['rev_jul_26'] ?? null,
            $d['cost_jul_26'] ?? null,
            $d['pnl_jul_26'] ?? null,
            $d['rev_aug_26'] ?? null,
            $d['cost_aug_26'] ?? null,
            $d['pnl_aug_26'] ?? null,

            // Profitability & Operasional
            $d['revenue_month'] ?? null,
            $d['margindirect'] ?? null,
            $d['profitability'] ?? null,
            $d['status_revenue'] ?? null,
            $d['status_payload'] ?? null,
            $d['status_sewa'] ?? null,

            // Detail Teknis & Lokasi
            $d['tp'] ?? $d['vendor'] ?? null,
            $d['nop'] ?? null,
            $d['region'] ?? null,
            $d['address'] ?? null,
            $d['desa'] ?? null,
            $d['kecamatan'] ?? null,
            $d['kabupaten'] ?? null,
            $d['status_combat'] ?? null,
            $d['height_combat_m'] ?? $d['height_combat'] ?? null,
            $d['height_rf'] ?? null,
            $d['date_connect'] ?? null,
        ];
    }
}
