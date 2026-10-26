<?php

namespace App\Console\Commands;

use App\Imports\Datasets\InfrastructureRebuildImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ImportInfrastructureRebuild extends Command
{
    protected $signature = 'dataset:import-infrastructure-rebuild
        {--file= : Lokasi workbook relatif project atau path absolut}
        {--only= : sewa-lahan atau combat; default keduanya}
        {--dry-run : Baca dan cocokkan sumber tanpa mengubah MySQL}';

    protected $description = 'Sinkronkan transaksi aktif Rebuild Sewa Lahan dan Combat ke MySQL';

    public function handle(InfrastructureRebuildImport $importer): int
    {
        $file = (string) ($this->option('file') ?: InfrastructureRebuildImport::DEFAULT_FILE);
        $path = is_file($file) ? $file : base_path($file);
        try {
            $datasets = $importer->read($path, $this->option('only'));
            $summary = [];
            foreach ($datasets as $key => $records) {
                $summary[] = [$key, InfrastructureRebuildImport::SHEETS[$key]['sheet'], count($records), count(array_unique(array_column($records, 'site_code')))];
            }
            $this->table(['Dataset', 'Sheet aktif', 'Transaksi', 'Site ID unik'], $summary);
            if ($this->option('dry-run')) {
                $this->info('Pemeriksaan sumber selesai; database belum diubah.');
                return self::SUCCESS;
            }
            $backup = $importer->synchronize($datasets);
            $this->info('Transaksi Rebuild tersimpan pada MySQL '.DB::connection()->getDatabaseName().'.');
            $this->line('Backup snapshot sebelumnya: '.$backup);
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $message = $exception instanceof QueryException
                ? ($exception->getPrevious()?->getMessage() ?: 'Kesalahan database saat menyimpan data.')
                : $exception->getMessage();
            $this->error('Sinkronisasi dibatalkan: '.$message);
            return self::FAILURE;
        }
    }
}
