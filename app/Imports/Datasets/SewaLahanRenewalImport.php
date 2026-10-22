<?php

namespace App\Imports\Datasets;

use Illuminate\Support\Collection;

/**
 * Infrastruktur management — 01 Sewa Lahan (Renewal Eastern Jabotabek).
 * Memetakan ekspor legacy dan baris aktif DATA SEWA LAHAN dari workbook Rebuild.
 * Kolom "No" dan "Aksi" (artefak UI) tidak diimpor.
 */
class SewaLahanRenewalImport extends BaseDatasetImport
{
    protected function uploadKey(): array
    {
        return ['site_code', 'tahun_renewal', 'no_pks_baru'];
    }

    public function headingRow(): int
    {
        return 1;
    }

    protected function table(): string
    {
        return 'sewa_lahan_renewals';
    }

    protected function mapRow(Collection $row): ?array
    {
        return $this->mapSourceRow($row->toArray());
    }

    public function mapSourceRow(array $source): ?array
    {
        // Nilai tahapan disimpan di kolom utama, sedangkan tanggal opsional
        // tetap di source_details agar workbook lama tidak perlu migrasi DB.
        foreach (range(1, 3) as $number) {
            foreach (['penawaran', 'nego'] as $stage) {
                $key = "tanggal_{$stage}_{$number}";
                $aliases = $stage === 'nego'
                    ? [$key, "tgl_nego_{$number}", "tanggal_negosiasi_{$number}", "tgl_negosiasi_{$number}"]
                    : [$key, "tgl_penawaran_{$number}"];
                $date = null;
                foreach ($aliases as $alias) {
                    $date = $this->parseDate($source[$alias] ?? null);
                    if ($date !== null) break;
                }
                if ($date !== null) $source[$key] = $date;
                else unset($source[$key]);
            }
        }
        // Workbook Rebuild mencatat alur operasional, bukan enam nominal
        // penawaran. Simpan tanggalnya secara terpisah untuk timeline adaptif.
        $rebuildDates = [
            'proses_negosiasi_at' => ['tgl_negosiasi', 'tanggal_negosiasi'],
            'proses_pks_owner_at' => ['tgl_pksbak_ke_owner', 'tgl_pks_bak_ke_owner', 'tgl_pks_ke_owner'],
            'proses_pks_legal_at' => ['tgl_pks_ke_legal'],
            'proses_budget_at' => ['tgl_budget'],
            'proses_paid_at' => ['tgl_paid'],
            'proses_drop_at' => ['tgl_drop'],
        ];
        foreach ($rebuildDates as $key => $aliases) {
            $date = null;
            foreach ($aliases as $alias) {
                $date = $this->parseDate($source[$alias] ?? null);
                if ($date !== null) break;
            }
            if ($date !== null) $source[$key] = $date;
            else unset($source[$key]);
        }
        $row = new Collection($source);
        $siteCode = $this->cleanText($row['site_id'] ?? null);

        if ($siteCode === null) {
            return null;
        }

        return [
            'site_code'           => $siteCode,
            'site_name'           => $this->cleanText($row['site_name'] ?? null),
            'tahun_renewal'       => $this->parseYear($row['year'] ?? $row['tahun_renewal'] ?? null),
            // STATUS and STATUS DOKUMEN are different dimensions.  Do not
            // place On Air/Off Air values into the document-status column.
            'status_dokumen'      => $this->cleanText($row['status_dokumen'] ?? null),
            'status_perpanjangan' => $this->cleanText($row['status_perpanjangan'] ?? null),
            'no_pks_baru'         => $this->cleanText($row['nomor_pks_baru'] ?? $row['no_pks_baru'] ?? null),
            'start_date_baru'     => $this->parseDate($row['new_period_awal'] ?? $row['start_date_baru'] ?? null),
            'end_date_baru'       => $this->parseDate($row['new_period_akhir'] ?? $row['end_date_baru'] ?? null),
            'harga_baru'          => $this->parseMoney($row['harga_baruth'] ?? $row['harga_baru_th'] ?? $row['harga_baru'] ?? null),
            'total_harga_baru'    => $this->parseMoney($row['total_harga_baru'] ?? null),
            'no_bak_baru'         => $this->cleanText($row['no_bak_baru'] ?? null),
            'tgl_bak_baru'        => $this->parseDate($row['tanggal_bak'] ?? $row['tgl_bak_baru'] ?? null),
            'no_sip'              => $this->cleanText($row['no_sip'] ?? null),
            'tgl_terima_sip'      => $this->parseDate($row['tgl_terima_sip'] ?? null),
            'penawaran_1'         => $this->parseMoney($row['penawaran_1'] ?? null),
            'nego_1'              => $this->parseMoney($row['nego_1'] ?? null),
            'penawaran_2'         => $this->parseMoney($row['penawaran_2'] ?? null),
            'nego_2'              => $this->parseMoney($row['nego_2'] ?? null),
            'penawaran_3'         => $this->parseMoney($row['penawaran_3'] ?? null),
            'nego_3'              => $this->parseMoney($row['nego_3'] ?? null),
            'no_pks_lama'         => $this->cleanText($row['nomor_pks_existing'] ?? $row['no_pks_lama'] ?? null),
            'start_date_lama'     => $this->parseDate($row['periode_awal_existing'] ?? $row['start_date_lama'] ?? null),
            'end_date_lama'       => $this->parseDate($row['periode_akhir_existing'] ?? $row['end_date_lama'] ?? null),
            'harga_lama'          => $this->parseMoney($row['harga_existing_th'] ?? $row['harga_lama'] ?? null),
            'keterangan'          => $this->cleanText($row['keterangan'] ?? null),
            'update_by'           => $this->cleanText($row['update_by'] ?? null),
            'tgl_update'          => $this->parseDate($row['tgl_update'] ?? null),
            'source_details'      => json_encode($this->sourceDetails($row), JSON_UNESCAPED_UNICODE),
        ];
    }
}

