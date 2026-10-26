<?php

namespace App\Imports\Datasets;

use Illuminate\Support\Collection;

/**
 * Infrastruktur management — 02 Combat.
 * Memetakan ekspor legacy dan transaksi DATABASE COMBAT dari workbook Rebuild.
 * Mapping kolom untuk sheet DATABASE dan DATABASE_REVENUE. Penggabungan
 * kedua sheet per Site ID ditangani CombatWorkbookImport agar satu site tidak
 * tersimpan berulang hanya karena punya beberapa baris tahun revenue.
 *
 * Tabel snapshot diisi ulang tiap import. CombatWorkbookImport memastikan
 * hasil akhirnya satu baris kanonis per Site ID.
 * Blok bulanan Jan–Jun 2026 disimpan flat; kolom PnL di sumber berbentuk
 * "1.543.712 (Profit)" / "-17.485.329 (Loss)" → parseMoney menangani.
 */
class CombatSiteImport extends BaseDatasetImport
{
    public function headingRow(): int
    {
        return 1;
    }

    private const MONTHS = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun'];

    protected function table(): string
    {
        return 'combat_sites';
    }

    protected function mapRow(Collection $row): ?array
    {
        return $this->mapSourceRow($row->toArray());
    }

    /**
     * Map satu baris sumber. Method public ini juga dipakai importer workbook
     * untuk mengonsolidasikan sheet DATABASE dan DATABASE_REVENUE.
     */
    public function mapSourceRow(array $source): ?array
    {
        $row = new Collection($source);
        $siteCode = $this->cleanText($row['site_id'] ?? null);

        if ($siteCode === null) {
            return null;
        }

        $statusDokumen = $this->cleanText($row['status_dokumen'] ?? null);
        if ($statusDokumen !== null && mb_strlen($statusDokumen) > 150) {
            // The table keeps a short searchable status. The complete source
            // text remains in source_details for the Combat detail view.
            $firstLine = preg_split('/\R/u', (string) $row['status_dokumen'], 2)[0] ?? '';
            $statusDokumen = mb_substr($this->cleanText($firstLine) ?: $statusDokumen, 0, 149).'…';
        }

        $mapped = [
            'site_code'           => $siteCode,
            'site_name'           => $this->cleanText($row['site_name'] ?? null),
            'tahun_justi_dirnet'  => $this->parseYear($row['tahun_justi_dirnet'] ?? $row['renewal_cycle'] ?? null),
            // STATUS and STATUS DOKUMEN are different dimensions.  Do not
            // place On Air/Off Air values into the document-status column.
            'status_dokumen'      => $statusDokumen,
            'status_perpanjangan' => $this->cleanText($row['status_perpanjangan'] ?? null),
            'no_pks_baru'         => $this->cleanText($row['nomor_pks_baru'] ?? $row['no_pks_baru'] ?? null),
            'start_date_baru'     => $this->parseDate($row['periode_awal_baru'] ?? $row['start_date_baru'] ?? null),
            'end_date_baru'       => $this->parseDate($row['periode_akhir_baru'] ?? $row['end_date_baru'] ?? null),
            'harga_baru'          => $this->parseMoney($row['harga_baru'] ?? $row['total_harga_baru'] ?? null),
            'total_harga_baru'    => $this->parseMoney($row['total_harga_baru'] ?? null),
            'penawaran_1'         => $this->parseMoney($row['penawaran_1'] ?? null),
            'nego_1'              => $this->parseMoney($row['nego_1'] ?? null),
            'penawaran_2'         => $this->parseMoney($row['penawaran_2'] ?? null),
            'nego_2'              => $this->parseMoney($row['nego_2'] ?? null),
            'penawaran_3'         => $this->parseMoney($row['penawaran_3'] ?? null),
            'nego_3'              => $this->parseMoney($row['nego_3'] ?? null),
            'no_pks_lama'         => $this->cleanText($row['nomor_pks_existing'] ?? $row['no_pks_lama'] ?? null),
            'start_date_lama'     => $this->parseDate($row['periode_awal_existing'] ?? $row['start_date_lama'] ?? null),
            'end_date_lama'       => $this->parseDate($row['periode_akhir_existing'] ?? $row['end_date_lama'] ?? null),
            'harga_lama'          => $this->parseMoney($row['total_harga_existing'] ?? $row['harga_lama'] ?? null),
            'nomor_surat'         => $this->cleanText($row['nomor_surat'] ?? null),
            'keterangan'          => $this->cleanText($row['keterangan'] ?? null),
            'update_by'           => $this->cleanText($row['update_by'] ?? null),
            'tanggal'             => $this->parseDate($row['tanggal'] ?? null),
        ];

        // Blok bulanan: heading "Revenue Jan 2026" → "revenue_jan_2026", dst.
        foreach (self::MONTHS as $month) {
            $mapped["revenue_{$month}_2026"] = $this->parseMoney($row["revenue_{$month}_2026"] ?? null);
            $mapped["cost_{$month}_2026"] = $this->parseMoney($row["cost_{$month}_2026"] ?? null);
            $mapped["pnl_{$month}_2026"] = $this->parseMoney($row["pnl_{$month}_2026"] ?? null);
        }

        // Sheet DATABASE menyimpan Margin Direct Mei tanpa kolom PnL/Cost.
        // Revenue-nya sudah terbaca di loop di atas, jadi cek PnL yang kosong.
        $margin = $this->parseMoney($row['margindirect'] ?? null);
        if ($mapped['revenue_mei_2026'] !== null && $mapped['pnl_mei_2026'] === null && $margin !== null) {
            $mapped['pnl_mei_2026'] = $margin;
            $mapped['cost_mei_2026'] = $mapped['revenue_mei_2026'] - $margin;
        }

        $mapped['source_details'] = json_encode($this->sourceDetails($row), JSON_UNESCAPED_UNICODE);

        return $mapped;
    }
}
