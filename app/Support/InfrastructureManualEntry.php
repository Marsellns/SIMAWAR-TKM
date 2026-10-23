<?php

namespace App\Support;

use App\Models\Bapss;
use App\Models\CombatSite;
use App\Models\DataSiteUnlock;
use App\Models\JaknetContract;
use App\Models\RecurringIpas;
use App\Models\RecurringTagihanIpas;
use App\Models\SewaLahanRenewal;

/** Manual entry uses the existing tables and the same record identities as Excel uploads. */
final class InfrastructureManualEntry
{
    public const DATASETS = [
        'sewa-lahan' => ['label' => 'Sewa Lahan', 'model' => SewaLahanRenewal::class, 'key' => ['site_code', 'tahun_renewal', 'no_pks_baru']],
        'combat' => ['label' => 'Combat', 'model' => CombatSite::class, 'key' => ['site_code']],
        'recurring-ipas' => ['label' => 'Recurring (ANT & Ipas)', 'model' => RecurringIpas::class, 'key' => ['site_code', 'source_id', 'sow_id', 'start_date']],
        'recurring-tagihan-ipas' => ['label' => 'Recurring (Tagihan Ipas)', 'model' => RecurringTagihanIpas::class, 'key' => ['site_code', 'batch_name', 'periode_ke', 'termin_start']],
        'jaknet' => ['label' => 'Jaknet & Dapot', 'model' => JaknetContract::class, 'key' => ['site_code', 'no_pks', 'tanggal_mulai']],
        'site-unlock' => ['label' => 'Data Site Unlock', 'model' => DataSiteUnlock::class, 'key' => ['site_code', 'batch', 'tanggal']],
        'bapss' => ['label' => 'BAPSS', 'model' => Bapss::class, 'key' => ['site_code']],
    ];

    /** [Label, input type, storage length] for modules without an existing edit form. */
    public static function fields(string $dataset): array
    {
        $identity = ['site_code' => ['Site ID', 'text', 30], 'site_name' => ['Site Name', 'text', 255]];

        return $identity + match ($dataset) {
            'recurring-ipas' => [
                'alamat' => ['Alamat', 'textarea', 10000], 'site_owner' => ['Site Owner', 'text', 100],
                'rtp' => ['RTP', 'text', 100], 'contract_description' => ['Contract Description', 'textarea', 10000],
                'source_id' => ['Source ID', 'text', 100], 'sow_detail' => ['SOW Detail', 'textarea', 10000],
                'sow_id' => ['SOW ID', 'text', 100], 'year_amount' => ['Year Amount (Rp)', 'number', null],
                'start_date' => ['Start Date', 'date', null], 'end_date' => ['End Date', 'date', null],
            ],
            'recurring-tagihan-ipas' => [
                'tp' => ['TP', 'text', 100], 'contract_type' => ['Contract Type', 'text', 100],
                'termin' => ['Termin', 'text', 100], 'periode_ke' => ['Periode Ke', 'text', 50],
                'termin_start' => ['Termin Start', 'date', null], 'termin_end' => ['Termin End', 'date', null],
                'amount' => ['Amount (Rp)', 'number', null], 'batch_name' => ['Batch Name', 'text', 150],
            ],
            'jaknet' => [
                'no_pks' => ['No PKS', 'text', 255], 'tanggal_mulai' => ['Tanggal Mulai', 'date', null],
                'tanggal_berakhir' => ['Tanggal Berakhir', 'date', null], 'contact_person' => ['Contact Person', 'text', 150],
                'contact_address' => ['Contact Address', 'textarea', 10000], 'telp' => ['Telepon', 'tel', 100],
                'nilai' => ['Nilai (Rp)', 'number', null], 'nilai_per_tahun' => ['Nilai Per Tahun (Rp)', 'number', null],
                'tahun_berakhir' => ['Tahun Berakhir', 'year', null], 'is_site_unlock' => ['Site Unlock', 'checkbox', null],
            ],
            'site-unlock' => [
                'site_class' => ['Class', 'text', 50], 'city' => ['City', 'text', 100],
                'batch' => ['Batch', 'text', 100], 'status' => ['Status', 'text', 100],
                'final_status' => ['Final Status', 'text', 100], 'tanggal' => ['Tanggal', 'date', null],
            ],
            default => [],
        };
    }

    /** Additional source columns already used by table cells, popups and charts. */
    public static function sourceFields(string $dataset): array
    {
        $common = [
            'status' => ['Status Site', 'text', 100], 'nop' => ['NOP', 'text', 100],
            'ownership' => ['Ownership (Telkomsel / TP)', 'ownership', 100],
        ];

        return $common + ($dataset === 'combat' ? [
            'renewal_pattern' => ['Renewal Pattern', 'text', 255], 'match_nbae' => ['Match NBAE', 'text', 255],
            'available_on_jarvis' => ['Available on Jarvis', 'text', 100], 'type' => ['Type', 'text', 100],
            'combat_type' => ['Combat Type', 'text', 100], 'status_combat' => ['Status Combat', 'text', 100],
            'region' => ['Region', 'text', 100], 'kabupaten' => ['Kabupaten', 'text', 100], 'tp' => ['TP / Vendor', 'text', 100],
            'tanggal_bak' => ['Tanggal BAK', 'date', null],
            'start_sewa' => ['Start Sewa', 'date', null], 'end_sewa' => ['End Sewa', 'date', null],
            'date_on_air' => ['Date on Air', 'date', null],
        ] : ['area' => ['Area', 'text', 100], 'vendor' => ['Vendor', 'text', 100]]) + [
            'tgl_negosiasi' => ['Tanggal Negosiasi', 'date', null], 'tgl_pks_bak_ke_owner' => ['Tanggal PKS / BAK ke Owner', 'date', null],
            'tgl_pks_ke_legal' => ['Tanggal PKS ke Legal', 'date', null], 'tgl_budget' => ['Tanggal Budget', 'date', null],
            'tgl_finance' => ['Tanggal Finance', 'date', null],
            'tgl_paid' => ['Tanggal Pembayaran', 'date', null], 'tgl_drop' => ['Tanggal Drop', 'date', null],
        ];
    }

    public const MONTHS = ['jan' => 'Januari', 'feb' => 'Februari', 'mar' => 'Maret', 'apr' => 'April', 'mei' => 'Mei', 'jun' => 'Juni', 'jul' => 'Juli', 'ags' => 'Agustus', 'sep' => 'September', 'okt' => 'Oktober', 'nov' => 'November', 'des' => 'Desember'];
}
