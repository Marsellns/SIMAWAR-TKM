<?php

namespace App\Support;

/**
 * Kolom yang dapat diisi pengguna dan dibaca oleh importer setiap modul.
 * Kolom internal database (id, timestamps, soft delete, source_details)
 * sengaja tidak ditampilkan di file upload.
 */
final class InfrastructureUploadTemplate
{
    public static function headers(string $dataset): array
    {
        return match ($dataset) {
            'combat' => self::combat(),
            'sewa-lahan' => [
                'Site ID', 'Site Name', 'Tahun Renewal', 'Status Dokumen', 'Status Perpanjangan',
                'No PKS Baru', 'Start Date Baru', 'End Date Baru', 'Harga Baru', 'Total Harga Baru',
                'No BAK Baru', 'Tgl BAK Baru', 'No SIP', 'Tgl Terima SIP',
                'Penawaran 1', 'Tanggal Penawaran 1', 'Nego 1', 'Tanggal Nego 1',
                'Penawaran 2', 'Tanggal Penawaran 2', 'Nego 2', 'Tanggal Nego 2',
                'Penawaran 3', 'Tanggal Penawaran 3', 'Nego 3', 'Tanggal Nego 3',
                'No PKS Lama', 'Start Date Lama', 'End Date Lama', 'Harga Lama',
                'Keterangan', 'Update By', 'Tgl Update',
            ],
            'recurring-ipas' => [
                'Site ID', 'Site Name', 'Alamat', 'Site Owner', 'RTP', 'Tgl Update',
                'Contract Description', 'Source ID', 'SOW Detail', 'SOW ID',
                'Year Amount', 'Start Date', 'End Date',
            ],
            'recurring-tagihan-ipas' => [
                'Site ID', 'Site Name', 'TP', 'Contract Type', 'Termin', 'Periode Ke',
                'Termin Start', 'Termin End', 'Amount', 'Batch Name', 'Recurring',
            ],
            'jaknet' => [
                'Site ID', 'Site Name', 'No PKS', 'Tanggal Mulai', 'Tanggal Berakhir',
                'Contact Person', 'Contact Address', 'Telp', 'Nilai', 'Nilai Per Tahun',
                'Tahun Berakhir', 'Tgl Update',
            ],
            'site-unlock' => [
                'Site ID', 'Site Name', 'Class', 'City', 'Batch', 'Status',
                'Final Status', 'Update By', 'Tanggal',
            ],
            default => ['Site ID', 'Site Name'],
        };
    }

    public static function headingRow(string $dataset): int
    {
        return in_array($dataset, ['combat', 'sewa-lahan', 'recurring-tagihan-ipas'], true) ? 1 : 2;
    }

    private static function combat(): array
    {
        $headers = [
            'Site ID', 'Site Name', 'Tahun Justi Dirnet', 'Status Dokumen', 'Status Perpanjangan',
            'No PKS Baru', 'Start Date Baru', 'End Date Baru', 'Harga Baru', 'Total Harga Baru',
            'Penawaran 1', 'Nego 1', 'Penawaran 2', 'Nego 2', 'Penawaran 3', 'Nego 3',
            'No PKS Lama', 'Start Date Lama', 'End Date Lama', 'Harga Lama',
            'Nomor Surat', 'Keterangan', 'Update By', 'Tanggal',
        ];

        foreach (['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'] as $month) {
            foreach (['Revenue', 'Cost', 'PnL'] as $metric) {
                $headers[] = "{$metric} {$month} 2026";
            }
        }

        return $headers;
    }
}

