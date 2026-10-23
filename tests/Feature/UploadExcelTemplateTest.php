<?php

namespace Tests\Feature;

use App\Imports\Electricity\CentralizedAnomaliImport;
use App\Imports\Electricity\CentralizedListrikAllImport;
use App\Imports\Electricity\CentralizedPaymentImport;
use App\Imports\Electricity\ListrikInbuildingImport;
use App\Imports\Electricity\ListrikPlnImport;
use App\Imports\SimawarPnLImport;
use App\Support\InfrastructureUploadTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class UploadExcelTemplateTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    public function test_all_twelve_templates_have_importable_headers(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach (['combat', 'sewa-lahan', 'recurring-ipas', 'recurring-tagihan-ipas', 'jaknet', 'site-unlock'] as $dataset) {
            $sheet = $this->downloadSheet(route('infrastruktur.upload.template', $dataset));
            $this->assertSame(
                InfrastructureUploadTemplate::headers($dataset),
                $this->header($sheet, InfrastructureUploadTemplate::headingRow($dataset))
            );
            if ($dataset === 'combat') {
                $this->assertSame('DATABASE', $sheet->getTitle());
            }
        }

        $pnlHeaders = $this->header($this->downloadSheet(route('pnl.template')), 2);
        $this->assertSame('Site ID', $pnlHeaders[0]);
        $this->assertContains('Rev Dec-'.now()->format('y'), $pnlHeaders);
        $this->assertContains('rev_dec_'.now()->format('y'), HeadingRowFormatter::format($pnlHeaders));
        $plnHeaders = $this->header($this->downloadSheet(route('electricity.centralized.template-flagging')), 1);
        $this->assertSame('ID Pelanggan', $plnHeaders[0]);
        $this->assertContains('daya_va', HeadingRowFormatter::format($plnHeaders));
        $this->assertSame('ID Pelanggan', $this->header($this->downloadSheet(route('electricity.centralized.payment.template')), 2)[1]);
        $this->assertSame('ID Pelanggan', $this->header($this->downloadSheet(route('electricity.centralized.anomali.template')), 1)[1]);
        $allHeaders = $this->header($this->downloadSheet(route('electricity.centralized.listrik-all.template')), 2);
        $this->assertSame('Flagging Des '.now()->year, $allHeaders[array_key_last($allHeaders)]);
        $inbuildingHeaders = $this->header($this->downloadSheet(route('electricity.inbuilding.template-tagihan-ibc')), 2);
        $this->assertSame('Site ID', $inbuildingHeaders[0]);
        $this->assertContains('harga_per_kwh', HeadingRowFormatter::format($inbuildingHeaders));
    }

    public function test_infrastructure_templates_expose_source_dates_as_excel_date_columns(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        foreach (['combat' => ['Start Sewa', 'End Sewa', 'Tanggal BAK', 'Tgl PKS ke Legal'],
            'sewa-lahan' => ['Tgl BAK Baru', 'Tgl PKS/BAK ke Owner', 'Tgl PKS ke Legal']] as $dataset => $dateHeaders) {
            $sheet = $this->downloadSheet(route('infrastruktur.upload.template', $dataset));
            $headers = InfrastructureUploadTemplate::headers($dataset);
            foreach ($dateHeaders as $header) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(array_search($header, $headers, true) + 1);
                $firstDataRow = InfrastructureUploadTemplate::headingRow($dataset) + 1;
                $this->assertSame('dd mmm yyyy', $sheet->getStyle($column.$firstDataRow)->getNumberFormat()->getFormatCode());
            }
        }
    }

    public function test_infrastructure_template_upload_adds_a_site_and_can_update_it_without_erasing_other_sites(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        DB::table('sewa_lahan_renewals')->insert(['site_code' => 'OLD-001', 'site_name' => 'Existing']);

        $headers = InfrastructureUploadTemplate::headers('sewa-lahan');
        $values = ['Site ID' => 'NEW-001', 'Site Name' => 'New Site', 'Tahun Renewal' => 2026,
            'No PKS Baru' => 'PKS-001', 'Harga Baru' => 125000,
            'Penawaran 1' => 925000000, 'Tanggal Penawaran 1' => '2026-07-12',
            'Nego 1' => 900000000, 'Tanggal Nego 1' => '2026-07-28',
            'Tgl PKS ke Legal' => 45834];
        $this->uploadInfrastructure('sewa-lahan', $headers, $values);
        $values['Harga Baru'] = 150000;
        $this->uploadInfrastructure('sewa-lahan', $headers, $values);

        $this->assertDatabaseCount('sewa_lahan_renewals', 2);
        $this->assertDatabaseHas('sewa_lahan_renewals', ['site_code' => 'OLD-001']);
        $this->assertDatabaseHas('sewa_lahan_renewals', ['site_code' => 'NEW-001', 'harga_baru' => 150000]);
        $this->assertDatabaseHas('sewa_lahan_renewals', ['site_code' => 'NEW-001', 'penawaran_1' => 925000000, 'nego_1' => 900000000]);
        $details = json_decode(DB::table('sewa_lahan_renewals')->where('site_code', 'NEW-001')->value('source_details'), true);
        $this->assertSame('2026-07-12', $details['tanggal_penawaran_1']);
        $this->assertSame('2026-07-28', $details['tanggal_nego_1']);
        $this->assertSame('2025-06-26', $details['proses_pks_legal_at']);
    }

    public function test_combat_template_upload_preserves_existing_sites(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        DB::table('combat_sites')->insert(['site_code' => 'OLD-001', 'site_name' => 'Existing']);

        $this->uploadInfrastructure('combat', InfrastructureUploadTemplate::headers('combat'), [
            'Site ID' => 'NEW-001', 'Site Name' => 'New Combat Site',
            'Tahun Justi Dirnet' => 2026, 'Harga Baru' => 100000,
            'Total Harga Baru' => 250000, 'Revenue Jan 2026' => 500000,
            'Start Sewa' => 45420, 'End Sewa' => 45784,
            'Tanggal BAK' => 45821, 'Tgl PKS ke Legal' => 45834,
        ]);

        $this->assertDatabaseCount('combat_sites', 2);
        $this->assertDatabaseHas('combat_sites', ['site_code' => 'OLD-001']);
        $this->assertDatabaseHas('combat_sites', [
            'site_code' => 'NEW-001', 'harga_baru' => 100000,
            'total_harga_baru' => 250000, 'revenue_jan_2026' => 500000,
        ]);
        $details = json_decode(DB::table('combat_sites')->where('site_code', 'NEW-001')->value('source_details'), true);
        $this->assertSame(45420, $details['database']['start_sewa']);
        $this->assertSame(45834, $details['database']['tgl_pks_ke_legal']);
    }

    public function test_remaining_infrastructure_templates_store_their_module_fields(): void
    {
        $this->actingAs($this->userWithRole('admin'));

        $cases = [
            'recurring-ipas' => [
                'table' => 'recurring_ipas',
                'values' => ['Site ID' => 'REC-001', 'Site Name' => 'Recurring Site',
                    'Source ID' => 'SRC-1', 'SOW ID' => 'SOW-1', 'Year Amount' => 120000],
                'expected' => ['site_code' => 'REC-001', 'source_id' => 'SRC-1', 'year_amount' => 120000],
            ],
            'recurring-tagihan-ipas' => [
                'table' => 'recurring_tagihan_ipas',
                'values' => ['Site ID' => 'TAG-001', 'Site Name' => 'Tagihan Site',
                    'Termin' => '1', 'Termin Start' => '2026-01-01', 'Amount' => 450000],
                'expected' => ['site_code' => 'TAG-001', 'termin_start' => '2026-01-01', 'amount' => 450000],
            ],
            'jaknet' => [
                'table' => 'jaknet_contracts',
                'values' => ['Site ID' => 'JAK-001', 'Site Name' => 'Jaknet Site',
                    'No PKS' => 'PKS-JAK', 'Nilai Per Tahun' => 875000],
                'expected' => ['site_code' => 'JAK-001', 'no_pks' => 'PKS-JAK', 'nilai_per_tahun' => 875000],
            ],
            'site-unlock' => [
                'table' => 'data_site_unlocks',
                'values' => ['Site ID' => 'UNL-001', 'Site Name' => 'Unlock Site',
                    'Class' => 'A', 'Final Status' => 'Done'],
                'expected' => ['site_code' => 'UNL-001', 'site_class' => 'A', 'final_status' => 'Done'],
            ],
        ];

        foreach ($cases as $dataset => $case) {
            DB::table($case['table'])->insert(['site_code' => 'OLD-001']);
            $this->uploadInfrastructure($dataset, InfrastructureUploadTemplate::headers($dataset), $case['values']);
            $this->assertDatabaseCount($case['table'], 2);
            $this->assertDatabaseHas($case['table'], ['site_code' => 'OLD-001']);
            $this->assertDatabaseHas($case['table'], $case['expected']);
        }
    }

    public function test_recurring_tagihan_excel_upload_preserves_parallel_terms_and_updates_site_summary(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $headers = InfrastructureUploadTemplate::headers('recurring-tagihan-ipas');
        $this->assertContains('Recurring', $headers);
        $this->get(route('infrastruktur.upload', 'recurring-tagihan-ipas'))
            ->assertOk()->assertSee('Setiap baris Excel adalah satu rincian termin');

        $upload = function (array $records) use ($headers): void {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($headers, null, 'A1');
            foreach ($records as $index => $record) {
                $sheet->fromArray(array_map(static fn ($header) => $record[$header] ?? null, $headers), null, 'A'.($index + 2));
            }
            $path = sys_get_temp_dir().'/simaster-ipas-upload-'.uniqid('', true).'.xlsx';
            (new Xlsx($spreadsheet))->save($path);
            try {
                $this->post(route('infrastruktur.upload.store', 'recurring-tagihan-ipas'), [
                    'dataset_file' => new UploadedFile($path, 'ipas.xlsx',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
                ])->assertRedirect(route('infrastruktur.recurring-tagihan-ipas.index'));
            } finally {
                @unlink($path);
                $spreadsheet->disconnectWorksheets();
            }
        };

        $first = ['Site ID' => 'BKS648', 'Site Name' => 'Bekasi', 'TP' => 'DHOST',
            'Contract Type' => 'MLA', 'Termin' => '1', 'Periode Ke' => '1',
            'Termin Start' => '2026-01-01', 'Termin End' => '2026-12-31',
            'Amount' => '100.25', 'Batch Name' => 'Batch 1', 'Recurring' => 'SRC-1'];
        $upload([$first, array_replace($first, ['Amount' => '200.50', 'Recurring' => 'SRC-2']),
            array_replace($first, ['Site ID' => 'JKP846', 'Amount' => '50.75', 'Recurring' => 'SRC-3'])]);
        $this->assertDatabaseCount('recurring_tagihan_ipas', 2);
        $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 3);
        $this->assertDatabaseHas('recurring_tagihan_ipas', ['site_code' => 'BKS648', 'term_count' => 2, 'amount' => '300.75']);

        $upload([array_replace($first, ['Amount' => '125.25'])]);
        $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 3);
        $this->assertDatabaseHas('recurring_tagihan_ipas', ['site_code' => 'BKS648', 'term_count' => 2, 'amount' => '325.75']);
        $this->assertDatabaseHas('recurring_tagihan_ipas', ['site_code' => 'JKP846', 'term_count' => 1, 'amount' => '50.75']);
        $sources = DB::table('recurring_tagihan_ipas_terms')->where('site_code', 'BKS648')->pluck('source_details')
            ->map(fn ($json) => json_decode($json, true)['recurring'])->sort()->values()->all();
        $this->assertSame(['SRC-1', 'SRC-2'], $sources);
    }

    public function test_pnl_accepts_periods_after_june_2026(): void
    {
        $import = new SimawarPnLImport();
        $import->collection(collect([collect([
            'site_id' => 'PNL-001', 'site_name' => 'New PnL Site',
            'rev_jul_26' => 200000, 'cost_jul_26' => 75000,
        ])]));

        $siteId = DB::table('sites')->where('site_id', 'PNL-001')->value('id');
        $this->assertDatabaseHas('site_monthly_metrics', [
            'site_id' => $siteId, 'bulan' => 7, 'tahun' => 2026,
            'revenue' => 200000, 'cost' => 75000,
        ]);
    }

    public function test_centralized_uploads_preserve_other_records_and_update_matching_records(): void
    {
        DB::table('payment_pln')->insert(['id_pelanggan' => 'OLD', 'site_id' => 'OLD',
            'status' => 'Done', 'bulan' => 6, 'tahun' => 2026]);
        $paymentRows = collect([
            collect(['Payment']),
            collect(['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Daya', 'Phasa', 'Gol Tarif', 'Unit PLN', 'Harga', 'Update By', 'Tanggal']),
            collect([1, 'ACC-1', 'SITE-1', 'New Site', 1000, '1 Phasa', 'B2', 'PLN', 2000, 'Admin', '2026-07-15']),
        ]);
        (new CentralizedPaymentImport('Done', 7, 2026))->collection($paymentRows);
        $paymentRows[2]->put(8, 2500);
        (new CentralizedPaymentImport('Done', 7, 2026))->collection($paymentRows);
        $this->assertDatabaseCount('payment_pln', 2);
        $this->assertDatabaseHas('payment_pln', ['id_pelanggan' => 'ACC-1', 'harga' => 2500]);

        DB::table('anomali_tagihan_pln')->insert(['id_pelanggan' => 'OLD', 'site_id' => 'OLD']);
        $anomalyRows = collect([
            collect(['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Bulan', 'Tahun', 'Harga Sebelumnya', 'Harga Sekarang', 'Kenaikan (%)']),
            collect([1, 'ACC-1', 'SITE-1', 'New Site', 7, 2026, 1000, 1500, 50]),
        ]);
        (new CentralizedAnomaliImport())->collection($anomalyRows);
        (new CentralizedAnomaliImport())->collection($anomalyRows);
        $this->assertDatabaseCount('anomali_tagihan_pln', 2);
        $this->assertDatabaseHas('anomali_tagihan_pln', ['id_pelanggan' => 'ACC-1', 'selisih' => 500]);

        DB::table('listrik_all')->insert(['id_pelanggan' => 'OLD', 'site_id' => 'OLD', 'tahun' => 2025]);
        $allRows = collect([
            collect(['Listrik All - Template']),
            collect(['No', 'ID Pelanggan', 'Site ID', 'Site Name', 'Gol Tarif', 'Unit PLN', 'Flagging Jan 2026']),
            collect([1, 'ACC-1', 'SITE-1', 'New Site', 'B2', 'PLN', 1500]),
        ]);
        (new CentralizedListrikAllImport())->collection($allRows);
        $allRows[2]->put(6, 1750);
        (new CentralizedListrikAllImport())->collection($allRows);
        $this->assertDatabaseCount('listrik_all', 2);
        $this->assertDatabaseHas('listrik_all', ['id_pelanggan' => 'ACC-1', 'tahun' => 2026, 'jan' => 1750]);
    }

    public function test_listrik_pln_and_inbuilding_match_existing_rows_by_their_import_keys(): void
    {
        $pln = new ListrikPlnImport();
        $pln->collection(collect([collect(['id_pelanggan' => 'ACC-1', 'site_id' => 'SITE-1', 'daya_va' => 1000])]));
        $pln->collection(collect([collect(['id_pelanggan' => 'ACC-1', 'site_id' => 'SITE-1', 'daya_va' => 2000])]));
        $this->assertDatabaseCount('listrik_pln', 1);
        $this->assertDatabaseHas('listrik_pln', ['id_pelanggan' => 'ACC-1', 'daya_va' => 2000]);

        $inbuilding = new ListrikInbuildingImport();
        $inbuilding->collection(collect([collect(['site_id' => 'IBC-1', 'harga_per_kwh' => 1000])]));
        $inbuilding->collection(collect([collect(['site_id' => 'IBC-1', 'harga_per_kwh' => 1500])]));
        $this->assertDatabaseCount('listrik_inbuilding', 1);
        $this->assertDatabaseHas('listrik_inbuilding', ['site_id' => 'IBC-1', 'harga_per_kwh' => 1500]);
    }

    private function downloadSheet(string $url): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $response = $this->get($url)->assertOk();
        return IOFactory::load($response->baseResponse->getFile()->getPathname())->getActiveSheet();
    }

    private function header(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $row): array
    {
        return array_values(array_filter(
            $sheet->rangeToArray('A'.$row.':'.$sheet->getHighestDataColumn().$row)[0],
            static fn ($value) => $value !== null && $value !== ''
        ));
    }

    private function uploadInfrastructure(string $dataset, array $headers, array $values): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($dataset === 'combat' ? 'DATABASE' : 'Data');
        $headingRow = InfrastructureUploadTemplate::headingRow($dataset);
        $sheet->fromArray($headers, null, 'A'.$headingRow);
        $sheet->fromArray(array_map(static fn ($header) => $values[$header] ?? null, $headers), null, 'A'.($headingRow + 1));
        $path = sys_get_temp_dir().'/simaster-upload-'.uniqid('', true).'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        try {
            $this->post(route('infrastruktur.upload.store', $dataset), [
                'dataset_file' => new UploadedFile($path, 'template.xlsx',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
            ])->assertRedirect(route('infrastruktur.'.$dataset.'.index'));
        } finally {
            @unlink($path);
            $spreadsheet->disconnectWorksheets();
        }
    }
}
