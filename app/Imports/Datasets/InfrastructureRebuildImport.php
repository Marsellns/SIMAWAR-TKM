<?php

namespace App\Imports\Datasets;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/** Reads only the two active transaction sheets; backup/pivot/master sheets are excluded. */
class InfrastructureRebuildImport
{
    public const DEFAULT_FILE = 'DATASET/02 Infrastruktur management/Sewa Lahan & Combat/Rebuild Database Combat dan Sewa Lahan(AutoRecovered) (1).xlsm';

    public const SHEETS = [
        'sewa-lahan' => ['sheet' => 'DATA SEWA LAHAN', 'table' => 'sewa_lahan_renewals'],
        'combat' => ['sheet' => 'DATABASE COMBAT', 'table' => 'combat_sites'],
    ];

    /** Website uploads merge contracts; the explicit CLI snapshot operation remains available. */
    public function merge(array $datasets): void
    {
        if ($datasets === [] || array_diff(array_keys($datasets), array_keys(self::SHEETS)) !== []) {
            throw new RuntimeException('Dataset Rebuild tidak valid.');
        }
        DB::transaction(function () use ($datasets): void {
            foreach ($datasets as $key => $records) {
                if ($records === []) throw new RuntimeException('File tidak berisi transaksi.');
                $table = self::SHEETS[$key]['table'];
                $year = $key === 'combat' ? 'tahun_justi_dirnet' : 'tahun_renewal';
                foreach ($records as $record) {
                    $record['site_code'] = strtoupper(trim($record['site_code']));
                    $identity = array_intersect_key($record + [$year => null, 'no_pks_baru' => null], array_flip(['site_code', $year, 'no_pks_baru']));
                    $record['updated_at'] = now();
                    $record['deleted_at'] = null;
                    DB::table($table)->updateOrInsert($identity, function (bool $exists) use ($record): array {
                        if (! $exists) $record['created_at'] = now();
                        return $record;
                    });
                }
            }
        });
    }

    public function supports(string $path): bool
    {
        $reader = IOFactory::createReaderForFile($path);
        if (! method_exists($reader, 'listWorksheetNames')) {
            return false;
        }

        return array_intersect(
            array_column(self::SHEETS, 'sheet'),
            $reader->listWorksheetNames($path)
        ) !== [];
    }

    /** Replace validated source snapshots together and retain the previous data privately. */
    public function synchronize(array $datasets): string
    {
        if ($datasets === [] || array_diff(array_keys($datasets), array_keys(self::SHEETS)) !== []) {
            throw new RuntimeException('Dataset Rebuild tidak valid.');
        }
        foreach ($datasets as $records) {
            if ($records === []) {
                throw new RuntimeException('Snapshot Rebuild kosong; data sebelumnya dipertahankan.');
            }
        }
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Sinkronisasi ini harus dijalankan pada database MySQL aplikasi.');
        }

        $backup = storage_path('app/backups/infrastructure-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json');
        if (! is_dir(dirname($backup))) mkdir(dirname($backup), 0700, true);
        DB::transaction(function () use ($datasets, $backup): void {
            $old = ['created_at' => now()->toIso8601String(), 'database' => DB::connection()->getDatabaseName(), 'tables' => []];
            foreach ($datasets as $key => $records) {
                $table = self::SHEETS[$key]['table'];
                $old['tables'][$table] = DB::table($table)->lockForUpdate()->get()->all();
            }
            if (file_put_contents($backup, json_encode($old, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)) === false) {
                throw new RuntimeException('Backup snapshot sebelumnya gagal dibuat.');
            }
            chmod($backup, 0600);
            foreach ($datasets as $key => $records) {
                $table = self::SHEETS[$key]['table'];
                DB::table($table)->delete();
                $timestamp = now();
                foreach ($records as &$record) {
                    $record['created_at'] = $timestamp;
                    $record['updated_at'] = $timestamp;
                }
                unset($record);
                foreach (array_chunk($records, 250) as $chunk) DB::table($table)->insert($chunk);
                if (DB::table($table)->count() !== count($records)) {
                    throw new RuntimeException('Jumlah sumber dan MySQL tidak cocok: '.$table);
                }
            }
        });

        return $backup;
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function read(string $path, ?string $only = null, ?string $sourceName = null): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('Workbook Rebuild tidak ditemukan: '.$path);
        }
        if ($only !== null && ! isset(self::SHEETS[$only])) {
            throw new RuntimeException('Pilihan --only harus sewa-lahan atau combat.');
        }

        $definitions = $only === null ? self::SHEETS : [$only => self::SHEETS[$only]];
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly(array_column($definitions, 'sheet'));
        $book = $reader->load($path);
        $fingerprint = hash_file('sha256', $path);
        $result = [];

        try {
            foreach ($definitions as $key => $definition) {
                $sheet = $book->getSheetByName($definition['sheet']);
                if ($sheet === null) {
                    throw new RuntimeException('Sheet wajib tidak ditemukan: '.$definition['sheet']);
                }
                $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
                $headers = [];
                for ($column = 1; $column <= $lastColumn; $column++) {
                    $headers[$column] = Str::slug(trim((string) $sheet->getCell([$column, 1])->getValue()), '_');
                }
                if (! in_array('site_id', $headers, true)) {
                    throw new RuntimeException('Kolom Site ID tidak ditemukan pada '.$definition['sheet']);
                }

                $records = [];
                for ($number = 2; $number <= $sheet->getHighestDataRow(); $number++) {
                    $source = [];
                    foreach ($headers as $column => $header) {
                        if ($header === '') continue;
                        $cell = $sheet->getCell([$column, $number]);
                        // Cached values are read without running workbook macros or volatile formulas.
                        $value = $cell->getDataType() === DataType::TYPE_FORMULA
                            ? $cell->getOldCalculatedValue()
                            : $cell->getValue();
                        if (! array_key_exists($header, $source) || blank($source[$header])) {
                            $source[$header] = $value;
                        }
                    }
                    $siteCode = strtoupper(trim((string) ($source['site_id'] ?? '')));
                    if ($siteCode === '') continue;
                    if (str_starts_with($siteCode, '#') || $siteCode === '-') {
                        throw new RuntimeException('Site ID tidak valid pada '.$definition['sheet'].' baris '.$number);
                    }

                    $normalized = $source;
                    $normalized['site_id'] = $siteCode;
                    $normalized['site_name'] = $source['site_name'] ?? $source['sitename'] ?? null;
                    $normalized['tahun_renewal'] = $source['tahun'] ?? null;
                    $normalized['tahun_justi_dirnet'] = $source['year'] ?? null;
                    $normalized['start_date_baru'] = $source['periode_awal_baru'] ?? null;
                    $normalized['end_date_baru'] = $source['periode_akhir_baru'] ?? null;
                    $normalized['start_date_lama'] = $source['periode_awal_existing'] ?? null;
                    $normalized['end_date_lama'] = $source['periode_akhir_existing'] ?? null;
                    $normalized['keterangan'] = $source['remarks'] ?? $source['remark'] ?? null;
                    $normalized['ownership'] = $source['ownership'] ?? $source['tp'] ?? null;
                    $normalized['vendor'] = $source['vendor'] ?? $source['tp'] ?? null;
                    $normalized['area'] = $source['area'] ?? $source['kabupaten'] ?? null;
                    $normalized['harga_lama'] = $source['harga_lama'] ?? $source['total_harga_existing'] ?? null;
                    $normalized['renewal_pattern'] = $source['renewal_pattern'] ?? $source['renewal'] ?? null;
                    if ($key === 'combat') {
                        $this->normalizeCombatFinancials($normalized);
                    }
                    $mapper = $key === 'combat' ? new CombatSiteImport() : new SewaLahanRenewalImport();
                    $mapped = $mapper->mapSourceRow($normalized);
                    if ($mapped === null) {
                        throw new RuntimeException('Baris tidak dapat dipetakan pada '.$definition['sheet'].' baris '.$number);
                    }
                    if ($key === 'sewa-lahan') {
                        $normalized = json_decode($mapped['source_details'], true, 512, JSON_THROW_ON_ERROR);
                    }
                    $normalized['_source'] = [
                        'file' => basename($sourceName ?? $path), 'sha256' => $fingerprint,
                        'sheet' => $definition['sheet'], 'row' => $number, 'cells' => $source,
                    ];
                    $mapped['source_details'] = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
                    $records[] = $mapped;
                }
                if ($records === []) {
                    throw new RuntimeException('Sheet '.$definition['sheet'].' tidak berisi transaksi; snapshot lama dipertahankan.');
                }
                $result[$key] = $records;
            }
        } finally {
            $book->disconnectWorksheets();
        }

        return $result;
    }

    private function normalizeCombatFinancials(array &$row): void
    {
        // The legacy header says "Mei", while revenue_month records its actual period.
        $period = (string) ($row['revenue_month'] ?? '');
        if (preg_match('/^2026(0[1-9]|1[0-2])$/', $period)) {
            $months = ['01' => 'jan', '02' => 'feb', '03' => 'mar', '04' => 'apr', '05' => 'mei', '06' => 'jun', '07' => 'jul', '08' => 'aug', '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dec'];
            $month = $months[substr($period, 4)];
            $row['rev_'.$month.'_26'] ??= $row['revenue_mei_2026'] ?? null;
            if ($month !== 'mei') unset($row['revenue_mei_2026']);
            if ((string) ($row['tracy_month'] ?? '') === $period && is_numeric($row['margindirect'] ?? null) && is_numeric($row['rev_'.$month.'_26'] ?? null)) {
                $row['pnl_'.$month.'_26'] = (float) $row['margindirect'];
                $row['cost_'.$month.'_26'] = (float) $row['rev_'.$month.'_26'] - (float) $row['margindirect'];
            }
        }
        foreach (['jan', 'feb', 'mar', 'apr', 'mei', 'jun'] as $month) {
            foreach (['revenue' => 'rev', 'cost' => 'cost', 'pnl' => 'pnl'] as $metric => $sourceKey) {
                $row[$metric.'_'.$month.'_2026'] ??= $row[$sourceKey.'_'.$month.'_26'] ?? null;
            }
        }
    }
}

