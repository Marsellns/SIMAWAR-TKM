<?php

namespace Tests\Feature;

use App\Models\Bapss;
use App\Models\CombatSite;
use App\Models\SewaLahanRenewal;
use App\Models\User;
use App\Support\InfrastructureManualEntry;
use App\Support\InfrastructureMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfrastructureManualEntryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create(['account_status' => 'approved']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_every_dataset_creates_in_its_existing_table_and_remains_visible_through_datatables(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        foreach (InfrastructureManualEntry::DATASETS as $dataset => $definition) {
            $table = (new $definition['model'])->getTable();
            $fields = match ($dataset) {
                'sewa-lahan' => ['tahun_renewal' => 2028, 'harga_baru' => '125000.50', 'performance' => ['jan' => null]],
                'combat' => ['tahun_justi_dirnet' => 2028, 'total_harga_baru' => '250000.50'],
                'recurring-ipas' => ['source_id' => 'SRC-1', 'sow_id' => 'SOW-1', 'year_amount' => '120000.25', 'end_date' => '2028-12-31'],
                'recurring-tagihan-ipas' => ['tp' => 'MITRATEL', 'batch_name' => 'Batch 1', 'periode_ke' => '1', 'amount' => '5000.25'],
                'jaknet' => ['no_pks' => 'PKS-001', 'telp' => '0812345678', 'tanggal_berakhir' => '2028-12-31', 'nilai' => '90000.50', 'is_site_unlock' => '1'],
                'site-unlock' => ['city' => 'Bekasi', 'batch' => 'Batch 1', 'tanggal' => '2026-10-17'],
                default => ['tgl_bapss' => '2026-10-17', 'remark' => 'Manual BAPSS'],
            };
            $this->post(route('infrastruktur.manual.store', $dataset), ['site_code' => ' man001 ', 'site_name' => 'Manual Site'] + $fields)
                ->assertSessionHasNoErrors()->assertRedirect()->assertSessionHas('success');
            $this->assertDatabaseHas($table, ['site_code' => 'MAN001', 'site_name' => 'Manual Site']);
            $this->assertDatabaseHas($table, array_diff_key($fields, ['performance' => true]));
            $response = $this->getJson(route('infrastruktur.'.$dataset.'.data', ['length' => 10, 'start' => 0, 'draw' => 1]))->assertOk();
            $this->assertTrue(collect($response->json('data'))->contains('site_code', 'MAN001'), $dataset);
        }
        // Infrastructure creation must not create or modify PnL master sites.
        $this->assertDatabaseCount('sites', 0);
        $this->assertDatabaseCount('site_monthly_metrics', 0);
    }

    public function test_manual_inputs_drive_existing_ownership_status_and_performance_calculations(): void
    {
        $this->actingAs($this->admin());
        foreach (['sewa-lahan' => SewaLahanRenewal::class, 'combat' => CombatSite::class] as $dataset => $model) {
            $payload = ['site_code' => 'MAN002', 'source_details' => ['status' => 'On Air', 'nop' => 'BEKASI', 'ownership' => 'TP'],
                'start_date_baru' => '2026-01-01', 'end_date_baru' => '2027-01-01',
                'performance_year' => 2026, 'performance' => ['jan' => ['revenue' => 1500.25, 'cost' => 2000.50]]];
            $this->post(route('infrastruktur.manual.store', $dataset), $payload)->assertSessionHasNoErrors()->assertRedirect();
            $site = $model::firstOrFail();
            $this->assertSame('TP', $site->source_details['ownership']);
            $this->assertSame('On Air', $site->source_details['status']);
            $this->assertSame(-500.25, InfrastructureMetrics::sitePerformance($site)[0]['pnl']);
            $scope = $dataset === 'combat' ? 'combat' : 'sewa';
            $this->getJson(route('infrastruktur.dashboard.data', ['scope' => $scope]))->assertOk()->assertJsonPath('cards.unique_sites', 1);
        }
        $this->getJson(route('infrastruktur.sewa-lahan.data', ['filter_field' => 'ownership', 'filter_value' => 'TP', 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.source_details.ownership', 'TP');
        $this->assertDatabaseCount('site_monthly_metrics', 0);
    }

    public function test_combat_manual_source_dates_are_validated_and_stored_as_dates(): void
    {
        $this->actingAs($this->admin());
        $sourceDates = ['start_sewa' => '2024-05-08', 'end_sewa' => '2025-05-07',
            'tanggal_bak' => '2025-06-13', 'tgl_pks_ke_legal' => '2025-06-26'];
        $this->post(route('infrastruktur.manual.store', 'combat'), [
            'site_code' => 'DATE001', 'source_details' => $sourceDates,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals($sourceDates, array_intersect_key(CombatSite::firstOrFail()->source_details, $sourceDates));

        $this->postJson(route('infrastruktur.manual.store', 'combat'), [
            'site_code' => 'DATE002', 'source_details' => ['start_sewa' => '45784'],
        ])->assertUnprocessable()->assertJsonValidationErrors('source_details.start_sewa');
    }

    public function test_repeated_create_is_rejected_without_overwriting_and_distinct_contracts_are_allowed(): void
    {
        $this->actingAs($this->admin());
        foreach (InfrastructureManualEntry::DATASETS as $dataset => $definition) {
            $payload = ['site_code' => 'DUP001', 'site_name' => 'Original'];
            $this->postJson(route('infrastruktur.manual.store', $dataset), $payload)->assertRedirect();
            $this->postJson(route('infrastruktur.manual.store', $dataset), ['site_code' => ' dup001 ', 'site_name' => 'Overwrite'])
                ->assertUnprocessable()->assertJsonValidationErrors('site_code');
            $this->assertSame('Original', $definition['model']::firstOrFail()->site_name);
            $this->assertSame(1, $definition['model']::count());
        }
        $this->post(route('infrastruktur.manual.store', 'sewa-lahan'), ['site_code' => 'DUP001', 'tahun_renewal' => 2027, 'no_pks_baru' => 'PKS-2'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, SewaLahanRenewal::count());
    }

    public function test_invalid_dates_amounts_and_unknown_source_attributes_do_not_write_data(): void
    {
        $this->actingAs($this->admin());
        foreach ([
            ['sewa-lahan', ['site_code' => 'BAD', 'harga_baru' => -1], 'harga_baru'],
            ['combat', ['site_code' => 'BAD', 'end_date_baru' => '2026-01-01', 'start_date_baru' => '2026-02-01'], 'end_date_baru'],
            ['jaknet', ['site_code' => 'BAD', 'nilai' => '1e99'], 'nilai'],
            ['jaknet', ['site_code' => 'BAD', 'nilai' => '10000000000000000'], 'nilai'],
            ['recurring-ipas', ['site_code' => 'BAD', 'end_date' => '2026-01-01', 'start_date' => '2027-01-01'], 'end_date'],
            ['recurring-tagihan-ipas', ['site_code' => 'BAD', 'amount' => '1.001'], 'amount'],
            ['site-unlock', ['site_code' => ['invalid']], 'site_code'],
            ['combat', ['site_code' => 'BAD', 'source_details' => ['admin' => true]], 'source_details'],
            ['sewa-lahan', ['site_code' => 'BAD', 'performance' => ['jan' => ['revenue' => -1]]], 'performance.jan.revenue'],
        ] as [$dataset, $payload, $error]) {
            $this->postJson(route('infrastruktur.manual.store', $dataset), $payload)->assertUnprocessable()->assertJsonValidationErrors($error);
        }
        foreach (InfrastructureManualEntry::DATASETS as $definition) {
            $this->assertSame(0, $definition['model']::count());
        }
    }

    public function test_admin_only_actions_and_both_entry_options_render_for_every_module(): void
    {
        $viewer = User::factory()->create(['account_status' => 'approved']);
        foreach (InfrastructureManualEntry::DATASETS as $dataset => $definition) {
            $url = route('infrastruktur.manual.store', $dataset);
            $this->postJson($url, ['site_code' => 'X'])->assertUnauthorized();
        }
        $this->actingAs($viewer);
        foreach (InfrastructureManualEntry::DATASETS as $dataset => $definition) {
            $this->postJson(route('infrastruktur.manual.store', $dataset), ['site_code' => 'X'])->assertForbidden();
            $this->get(route('infrastruktur.'.$dataset.'.index'))->assertOk()->assertDontSee('data-infrastructure-manual-form', false);
        }
        $this->actingAs($this->admin());
        foreach (InfrastructureManualEntry::DATASETS as $dataset => $definition) {
            $this->get(route('infrastruktur.'.$dataset.'.index'))->assertOk()->assertSee('Tambah Manual')
                ->assertSee('Upload Excel')->assertSee('data-infrastructure-manual-form', false)
                ->assertSee(route('infrastruktur.manual.store', $dataset), false);
        }
        $this->postJson('/infrastruktur/unknown/manual', ['site_code' => 'X'])->assertNotFound();
    }

    public function test_validation_returns_old_input_and_reopens_the_popup(): void
    {
        $this->actingAs($this->admin());
        $url = route('infrastruktur.sewa-lahan.index');
        $this->from($url)->post(route('infrastruktur.manual.store', 'sewa-lahan'), [
            '_manual_dataset' => 'sewa-lahan', 'site_code' => '', 'site_name' => 'Do not lose this',
        ])->assertRedirect($url)->assertSessionHasErrors('site_code', null, 'infrastructureManual')->assertSessionHasInput('_manual_dataset', 'sewa-lahan');
        // JSON session serialization must reload the same cookie on redirect,
        // just as a browser does, rather than starting another session ID.
        $page = $this->withCookie(config('session.cookie'), session()->getId())->get($url);
        $page->assertOk()->assertSee('data-reopen="true"', false)->assertSee('Do not lose this')->assertSee('Data belum tersimpan');
    }

    public function test_bapss_pdf_remains_private_and_audit_fields_cannot_be_spoofed(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->post(route('infrastruktur.manual.store', 'bapss'), [
            'site_code' => 'PDF001', 'update_by' => 'Spoofed', 'pdf_bapss' => 'https://invalid.example/a.pdf',
            'pdf_bapss_file' => UploadedFile::fake()->createWithContent('bapss.pdf', "%PDF-1.7\nTest"),
        ])->assertSessionHasNoErrors()->assertRedirect();
        $record = Bapss::firstOrFail();
        $this->assertSame($admin->name, $record->update_by);
        Storage::disk('private')->assertExists($record->pdf_bapss);
        Storage::disk('public')->assertMissing($record->pdf_bapss);
        $this->get(route('infrastruktur.bapss.file', [$record, 'bapss']))->assertOk();
        $this->postJson(route('infrastruktur.manual.store', 'bapss'), [
            'site_code' => 'PDF002', 'pdf_bapss_file' => UploadedFile::fake()->create('bad.txt', 1, 'text/plain'),
        ])->assertUnprocessable()->assertJsonValidationErrors('pdf_bapss_file');
        $this->assertSame(1, Bapss::count());
    }
}
