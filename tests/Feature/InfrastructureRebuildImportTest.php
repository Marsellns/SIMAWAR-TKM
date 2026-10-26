<?php

namespace Tests\Feature;

use App\Imports\Datasets\InfrastructureRebuildImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class InfrastructureRebuildImportTest extends TestCase
{
    use RefreshDatabase, CreatesUsersWithRoles;

    private array $files = [];
    private array $priorBackups = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->priorBackups = glob(storage_path('app/backups/infrastructure-*.json')) ?: [];
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) if (is_file($file)) unlink($file);
        foreach (array_diff(glob(storage_path('app/backups/infrastructure-*.json')) ?: [], $this->priorBackups) as $file) {
            $backup = json_decode(file_get_contents($file), true);
            if (($backup['database'] ?? null) === DB::connection()->getDatabaseName()) unlink($file);
        }
        parent::tearDown();
    }

    public function test_reader_preserves_contract_history_and_maps_only_active_transaction_sheets(): void
    {
        $data = (new InfrastructureRebuildImport())->read($this->workbook());
        $this->assertCount(2, $data['sewa-lahan']);
        $this->assertCount(2, $data['combat']);
        $sewa = $data['sewa-lahan'][0];
        $this->assertSame('SEWA01', $sewa['site_code']);
        $this->assertSame('Sewa Site', $sewa['site_name']);
        $this->assertSame(2026, $sewa['tahun_renewal']);
        $this->assertSame('2026-01-01', $sewa['start_date_baru']);
        $this->assertSame(925000000.0, $sewa['penawaran_1']);
        $this->assertNull($data['sewa-lahan'][1]['end_date_baru']);
        $details = json_decode($sewa['source_details'], true);
        $this->assertSame('Telkomsel', $details['tp']);
        $this->assertSame('Telkomsel', $details['ownership']);
        $this->assertSame('2026-07-12', $details['tanggal_penawaran_1']);
        $this->assertSame('2026-07-01', $details['proses_negosiasi_at']);
        $this->assertSame('2026-07-15', $details['proses_pks_owner_at']);
        $this->assertSame(107.45, $details['longtitude']);
        $this->assertSame('DATA SEWA LAHAN', $details['_source']['sheet']);
        $this->assertSame(2, $details['_source']['row']);
        $this->assertSame([2025, 2026], array_column($data['combat'], 'tahun_justi_dirnet'));
        $combat = $data['combat'][1];
        $this->assertNull($combat['revenue_mei_2026']);
        $this->assertSame(500.0, $combat['revenue_jun_2026']);
        $this->assertSame(350.0, $combat['cost_jun_2026']);
        $this->assertSame(150.0, $combat['pnl_jun_2026']);
        $details = json_decode($combat['source_details'], true);
        $this->assertSame(700, $details['rev_jul_26']);
        $this->assertSame(499, $details['_source']['cells']['revenue_mei_2026']);
        $export = new \App\Exports\CombatSiteExport();
        $exported = $export->map((new \App\Models\CombatSite())->forceFill($combat));
        $this->assertCount(count($export->headings()), $exported);
        $this->assertSame(700, array_combine($export->headings(), $exported)['Revenue Jul 2026']);
    }

    public function test_long_combat_document_status_keeps_full_text_in_detail(): void
    {
        $fullStatus = '-Perpanjangan belum dapat diproses'."\n"
            .str_repeat('Koordinasi PKS Sitak dengan pihak terkait. ', 8);
        $record = (new \App\Imports\Datasets\CombatSiteImport())->mapSourceRow([
            'site_id' => 'LONG-STATUS',
            'year' => 2025,
            'status_dokumen' => $fullStatus,
        ]);

        $this->assertLessThanOrEqual(150, mb_strlen($record['status_dokumen']));
        $this->assertSame($fullStatus, json_decode($record['source_details'], true)['status_dokumen']);
        $id = DB::table('combat_sites')->insertGetId($record);

        $this->actingAs($this->userWithRole('viewer'));
        $this->getJson(route('infrastruktur.combat.show', $id))
            ->assertOk()
            ->assertJsonPath('site.status_dokumen', $fullStatus)
            ->assertJsonPath('history.0.status_dokumen', $fullStatus);
    }

    public function test_sync_is_repeatable_keeps_other_modules_and_backs_up_the_old_snapshot(): void
    {
        DB::table('sewa_lahan_renewals')->insert(['site_code' => 'OLD-SEWA', 'tahun_renewal' => 2020]);
        DB::table('combat_sites')->insert(['site_code' => 'OLD-COMBAT']);
        DB::table('site_owners')->insert(['site_code' => 'MASTER-KEEP', 'site_name' => 'Unchanged']);
        $path = $this->workbook();
        $this->artisan('dataset:import-infrastructure-rebuild', ['--file' => $path])->assertSuccessful();
        $this->assertDatabaseCount('sewa_lahan_renewals', 2);
        $this->assertDatabaseCount('combat_sites', 2);
        $this->assertDatabaseHas('site_owners', ['site_code' => 'MASTER-KEEP']);
        $backups = array_values(array_diff(glob(storage_path('app/backups/infrastructure-*.json')) ?: [], $this->priorBackups));
        $this->assertCount(1, $backups);
        $old = json_decode(file_get_contents($backups[0]), true);
        $this->assertSame('OLD-SEWA', $old['tables']['sewa_lahan_renewals'][0]['site_code']);
        $this->assertSame('OLD-COMBAT', $old['tables']['combat_sites'][0]['site_code']);
        $this->artisan('dataset:import-infrastructure-rebuild', ['--file' => $path])->assertSuccessful();
        $this->assertDatabaseCount('sewa_lahan_renewals', 2);
        $this->assertDatabaseCount('combat_sites', 2);
        $this->artisan('dataset:import-infrastructure-rebuild', ['--file' => $this->workbook(emptyCombat: true)])->assertFailed();
        $this->assertDatabaseCount('sewa_lahan_renewals', 2);
        $this->assertDatabaseCount('combat_sites', 2);
    }

    public function test_sewa_portfolios_and_combat_have_separate_accessible_chart_panels(): void
    {
        $this->actingAs($this->userWithRole('viewer'));
        foreach ([['sewa-lahan', ['ownership_scope' => 'Telkomsel']], ['sewa-lahan', ['ownership_scope' => 'TP']], ['combat', []]] as [$module, $params]) {
            $scope = $module === 'combat' ? 'combat' : 'sewa';
            $response = $this->get(route('infrastruktur.'.$module.'.index', $params))->assertOk();
            $html = $response->getContent();
            $this->assertSame(5, substr_count($html, 'data-infra-section="'));
            $this->assertSame(5, substr_count($html, 'data-infra-section-panel="'));
            $response->assertSee('id="infra-'.$scope.'-tab-renewal"', false)
                ->assertSee('id="infra-'.$scope.'-panel-renewal"', false)
                ->assertSee('infra-empty-state', false);
        }
        $html = $this->get(route('infrastruktur.index'))->assertOk()->getContent();
        $this->assertSame(1, substr_count($html, 'id="infra-renewal-chart"'));
        $this->assertSame(1, substr_count($html, 'id="infra-renewal-tab-chart"'));
    }

    public function test_year_and_site_owner_filter_reaches_chart_and_existing_table_endpoints(): void
    {
        DB::table('sewa_lahan_renewals')->insert([
            ['site_code' => 'TP-HISTORY', 'tahun_renewal' => 2025, 'source_details' => json_encode(['ownership' => 'TP', 'status' => 'On Air'])],
            ['site_code' => 'TP-HISTORY', 'tahun_renewal' => 2026, 'source_details' => json_encode(['ownership' => 'TP', 'status' => 'Off Air'])],
            ['site_code' => 'TEL-CURRENT', 'tahun_renewal' => 2026, 'source_details' => json_encode(['ownership' => 'Telkomsel', 'status' => 'On Air'])],
        ]);
        DB::table('combat_sites')->insert(['site_code' => 'COM-CURRENT', 'tahun_justi_dirnet' => 2026]);
        $this->actingAs($this->userWithRole('viewer'));

        $this->get(route('infrastruktur.index', ['tahun' => 2026]))
            ->assertOk()->assertSee('id="infrastructure-scope-filter"', false);
        $this->get(route('infrastruktur.sewa-lahan.index', ['tahun' => 2026, 'ownership_scope' => 'TP']))
            ->assertOk()->assertSee('name="ownership_scope"', false);
        $this->get(route('infrastruktur.combat.index', ['tahun' => 2026]))
            ->assertOk()->assertSee('Tahun Justi Combat');

        $filters = ['scope' => 'sewa', 'tahun' => 2026, 'ownership_scope' => 'TP'];
        $this->getJson(route('infrastruktur.dashboard.data', $filters))
            ->assertOk()->assertJsonPath('cards.total', 1)->assertJsonPath('cards.unique_sites', 1);
        $this->getJson(route('infrastruktur.dashboard.details', $filters + ['filter_field' => 'all_records']))
            ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('data.0.site_code', 'TP-HISTORY');
        $this->getJson(route('infrastruktur.sewa-lahan.data', [
            'tahun' => 2026, 'ownership_scope' => 'TP', 'unique_sites' => 1,
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.site_code', 'TP-HISTORY');
        $this->getJson(route('infrastruktur.combat.data', [
            'tahun' => 2026, 'unique_sites' => 1, 'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1);
    }

    public function test_combat_jarvis_filter_keeps_table_charts_details_and_export_in_sync(): void
    {
        DB::table('combat_sites')->insert([
            ['site_code' => 'JARVIS-OLD', 'tahun_justi_dirnet' => 2025, 'source_details' => json_encode(['database' => ['available_on_jarvis' => 'YES']])],
            ['site_code' => 'JARVIS-YES', 'tahun_justi_dirnet' => 2026, 'source_details' => json_encode(['database_revenue' => ['available_on_jarvis' => 'YES']])],
            ['site_code' => 'JARVIS-NO', 'tahun_justi_dirnet' => 2026, 'source_details' => json_encode(['database' => ['available_on_jarvis' => 'YES'], 'available_on_jarvis' => ' no '])],
            ['site_code' => 'JARVIS-DASH', 'tahun_justi_dirnet' => 2026, 'source_details' => json_encode(['available_on_jarvis' => '-'])],
            ['site_code' => 'JARVIS-EMPTY', 'tahun_justi_dirnet' => 2026, 'source_details' => null],
        ]);
        $this->actingAs($this->userWithRole('viewer'));

        $this->get(route('infrastruktur.combat.index', ['available_on_jarvis' => 'NO']))
            ->assertOk()
            ->assertSee('name="available_on_jarvis"', false)
            ->assertSee('value="YES"', false)
            ->assertSee('value="NO"', false)
            ->assertSee('value="-"', false);

        foreach (['YES' => ['JARVIS-YES'], 'NO' => ['JARVIS-NO'], '-' => ['JARVIS-DASH', 'JARVIS-EMPTY']] as $status => $expectedSites) {
            $filters = ['scope' => 'combat', 'tahun' => 2026, 'available_on_jarvis' => $status];
            $table = $this->getJson(route('infrastruktur.combat.data', $filters + [
                'draw' => 1, 'start' => 0, 'length' => 10,
            ]))->assertOk()->assertJsonPath('recordsFiltered', count($expectedSites));
            $this->assertEqualsCanonicalizing($expectedSites, array_column($table->json('data'), 'site_code'));

            $this->getJson(route('infrastruktur.dashboard.data', $filters))
                ->assertOk()->assertJsonPath('cards.total', count($expectedSites));
            $this->getJson(route('infrastruktur.dashboard.details', $filters + ['filter_field' => 'all_records']))
                ->assertOk()->assertJsonPath('count', count($expectedSites));
            $this->assertEqualsCanonicalizing(
                $expectedSites,
                (new \App\Exports\CombatSiteExport(2026, $status))->query()->pluck('site_code')->all()
            );
        }

        $this->getJson(route('infrastruktur.combat.data', [
            'tahun' => 2025, 'available_on_jarvis' => 'YES', 'unique_sites' => 1,
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.site_code', 'JARVIS-OLD');
    }

    public function test_rebuild_uploads_merge_selected_contracts_and_refresh_portfolio_charts(): void
    {
        DB::table('sewa_lahan_renewals')->insert(['site_code' => 'OLD-SEWA']);
        DB::table('combat_sites')->insert(['site_code' => 'OLD-COMBAT']);
        $this->actingAs($this->userWithRole('admin'));
        $path = $this->workbook(includeTp: true);

        foreach (['sewa-lahan', 'sewa-lahan', 'combat'] as $dataset) {
            $this->post(route('infrastruktur.upload.store', $dataset), [
                'dataset_file' => new UploadedFile($path, 'Rebuild.xlsm',
                    'application/vnd.ms-excel.sheet.macroEnabled.12', null, true),
            ])->assertSessionHasNoErrors()->assertRedirect(route('infrastruktur.'.$dataset.'.index'));
            if ($dataset === 'sewa-lahan') {
                $this->assertDatabaseCount('sewa_lahan_renewals', 4);
                $this->assertDatabaseHas('combat_sites', ['site_code' => 'OLD-COMBAT']);
            }
        }
        $this->assertDatabaseCount('combat_sites', 3);
        $this->assertDatabaseHas('sewa_lahan_renewals', ['site_code' => 'OLD-SEWA']);
        $this->assertDatabaseHas('combat_sites', ['site_code' => 'OLD-COMBAT']);
        $details = json_decode(DB::table('combat_sites')->where('site_code', 'COMBAT01')->first()->source_details, true);
        $this->assertSame('Rebuild.xlsm', $details['_source']['file']);
        $this->assertSame('DATABASE COMBAT', $details['_source']['sheet']);

        foreach ([['sewa', 'Telkomsel', 1], ['sewa', 'TP', 1], ['combat', null, 2]] as [$scope, $owner, $sites]) {
            $params = ['scope' => $scope, 'ownership_scope' => $owner];
            $response = $this->getJson(route('infrastruktur.dashboard.data', $params))
                ->assertOk()->assertJsonPath('cards.unique_sites', $sites);
            $this->assertSame(
                $response->json('cards.total'),
                array_sum(array_column($response->json('status'), 'y'))
            );
            foreach (['owners', 'pks_status', 'lease_status', 'renewal_status', 'nop', 'pipeline'] as $series) {
                $this->assertSame($sites, array_sum(array_column($response->json($series), 'y')));
            }
            $this->getJson(route('infrastruktur.dashboard.details', $params))
                ->assertOk()->assertJsonPath('count', $sites);
        }
    }

    public function test_admin_can_replace_both_infrastructure_snapshots_with_one_workbook(): void
    {
        DB::table('sewa_lahan_renewals')->insert(['site_code' => 'OLD-SEWA']);
        DB::table('combat_sites')->insert(['site_code' => 'OLD-COMBAT']);
        DB::table('site_owners')->insert(['site_code' => 'KEEP-OTHER-MODULE']);
        $this->actingAs($this->userWithRole('admin'));
        $this->get(route('infrastruktur.upload', 'sewa-lahan'))
            ->assertOk()->assertSee('name="replace_snapshot"', false);

        $this->post(route('infrastruktur.upload.store', 'sewa-lahan'), [
            'replace_snapshot' => '1',
            'dataset_file' => new UploadedFile($this->workbook(), 'Infra.xlsm',
                'application/vnd.ms-excel.sheet.macroEnabled.12', null, true),
        ])->assertSessionHasNoErrors()->assertRedirect(route('infrastruktur.sewa-lahan.index'));

        $this->assertDatabaseCount('sewa_lahan_renewals', 2);
        $this->assertDatabaseCount('combat_sites', 2);
        $this->assertDatabaseMissing('sewa_lahan_renewals', ['site_code' => 'OLD-SEWA']);
        $this->assertDatabaseMissing('combat_sites', ['site_code' => 'OLD-COMBAT']);
        $this->assertDatabaseHas('site_owners', ['site_code' => 'KEEP-OTHER-MODULE']);
        $backups = array_values(array_diff(glob(storage_path('app/backups/infrastructure-*.json')) ?: [], $this->priorBackups));
        $this->assertCount(1, $backups);
    }

    public function test_invalid_rebuild_upload_preserves_the_existing_snapshot(): void
    {
        DB::table('combat_sites')->insert(['site_code' => 'KEEP-COMBAT']);
        $this->actingAs($this->userWithRole('admin'));
        $this->post(route('infrastruktur.upload.store', 'combat'), [
            'dataset_file' => new UploadedFile($this->workbook(emptyCombat: true), 'Rebuild.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertSessionHasErrors('dataset_file');
        $this->assertDatabaseCount('combat_sites', 1);
        $this->assertDatabaseHas('combat_sites', ['site_code' => 'KEEP-COMBAT']);
    }

    public function test_rebuild_missing_the_requested_sheet_does_not_import_its_backup_as_a_template(): void
    {
        DB::table('sewa_lahan_renewals')->insert(['site_code' => 'KEEP-SEWA']);
        $this->actingAs($this->userWithRole('admin'));
        $this->post(route('infrastruktur.upload.store', 'sewa-lahan'), [
            'dataset_file' => new UploadedFile($this->workbook(missingSewa: true), 'Rebuild.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true),
        ])->assertSessionHasErrors('dataset_file');
        $this->assertDatabaseCount('sewa_lahan_renewals', 1);
        $this->assertDatabaseHas('sewa_lahan_renewals', ['site_code' => 'KEEP-SEWA']);
        $this->assertDatabaseMissing('sewa_lahan_renewals', ['site_code' => 'EXCLUDED']);
    }

    public function test_sewa_detail_edit_uses_existing_admin_permissions_and_update_validation(): void
    {
        $id = DB::table('sewa_lahan_renewals')->insertGetId(['site_code' => 'EDIT-SEWA']);
        $url = route('infrastruktur.sewa-lahan.edit', $id);

        $this->actingAs($this->userWithRole('viewer'));
        $this->get($url.'?modal=1')->assertForbidden();
        $this->getJson(route('infrastruktur.sewa-lahan.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->assertJsonMissingPath('data.0.edit_url');

        $this->actingAs($this->userWithRole('admin'));
        $this->get($url.'?modal=1')->assertOk()->assertSee('data-sewa-edit-form', false);
        $this->getJson(route('infrastruktur.sewa-lahan.data', ['draw' => 1, 'start' => 0, 'length' => 10]))
            ->assertOk()->assertJsonPath('data.0.edit_url', $url);
        $this->putJson(route('infrastruktur.sewa-lahan.update', $id), ['site_code' => 'EDIT-SEWA', 'status_perpanjangan' => 'PKS'])
            ->assertOk()->assertJsonPath('message', 'Sewa Lahan site EDIT-SEWA berhasil diperbarui.');
        $this->assertDatabaseHas('sewa_lahan_renewals', ['id' => $id, 'status_perpanjangan' => 'PKS']);
        $this->putJson(route('infrastruktur.sewa-lahan.update', $id), ['site_code' => 'EDIT-SEWA', 'harga_baru' => -1])
            ->assertUnprocessable()->assertJsonValidationErrors('harga_baru');
    }

    private function workbook(bool $emptyCombat = false, bool $includeTp = false, bool $missingSewa = false): string
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->setTitle('BACKUP')->fromArray([['Site ID'], ['EXCLUDED']]);
        $book->createSheet()->setTitle('DATABASE RENEWAL')->fromArray([['Site ID'], ['MASTER-EXCLUDED']]);
        $sewa = [
            ['Site ID', 'Sitename', 'Tahun', 'Periode Awal (Baru)', 'Periode Akhir (Baru)', 'tp', 'tp', 'Penawaran 1', 'Tanggal Penawaran 1', 'Tgl Negosiasi', 'Tgl PKS/BAK ke Owner', 'Longtitude', 'latitude'],
            ['sewa01', 'Sewa Site', 2026, '2026-01-01', '2026-12-31', 'Telkomsel', null, 925000000, '2026-07-12', '2026-07-01', '2026-07-15', 107.45, -6.49],
            ['sewa01', 'Sewa Site', 2027, '2027-01-01', '00:00:00', 'Telkomsel', null],
        ];
        if ($includeTp) {
            $sewa[] = ['SEWA-TP', 'TP Site', 2026, '2026-01-01', '2026-12-31', 'TP', null];
        }
        if (! $missingSewa) {
            $book->createSheet()->setTitle('DATA SEWA LAHAN')->fromArray($sewa);
        }
        $combat = [
            ['Site ID', 'Site Name', 'Year', 'Rev Jun 26', 'Rev Jul 26', 'revenue_month', 'revenue_mei_2026', 'tracy_month', 'margindirect'],
        ];
        if (! $emptyCombat) {
            $combat[] = ['COMBAT01', 'Combat Site', 2025, 200, 300, null, null, null, null];
            $combat[] = ['COMBAT01', 'Combat Site', 2026, 500, 700, 202606, 499, 202606, 150];
        }
        $book->createSheet()->setTitle('DATABASE COMBAT')->fromArray($combat);
        $path = tempnam(sys_get_temp_dir(), 'infra-rebuild-');
        $this->files[] = $path;
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        return $path;
    }
}

