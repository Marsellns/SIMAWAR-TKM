<?php

namespace Tests\Feature;

use App\Imports\Datasets\RecurringTagihanIpasImport;
use App\Models\RecurringTagihanIpas;
use App\Models\User;
use App\Support\RecurringTagihanSummary;
use App\Support\RecurringTagihanWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class RecurringTagihanSitesTest extends TestCase
{
    use RefreshDatabase;

    private function record(int $termin, array $extra = []): array
    {
        return array_replace([
            'site_code' => ' bks648 ', 'site_name' => 'BKS648IM1_APTMUTIARAARBITEL',
            'tp' => 'DHOST', 'contract_type' => 'NON-MLA', 'termin' => (string) $termin,
            'periode_ke' => (string) $termin, 'termin_start' => (2013 + $termin).'-10-21',
            'termin_end' => (2014 + $termin).'-10-20', 'amount' => '366000000.25',
            'source_details' => ['recurring' => 'source-'.$termin, 'infra_contract' => '2', 'contract_no' => 'M100002563', 'po_number' => 'PO-'.$termin],
        ], $extra);
    }

    public function test_sites_are_unique_with_exact_totals_and_on_demand_contract_details(): void
    {
        $writer = app(RecurringTagihanWriter::class);
        $writer->store(array_map(fn ($n) => $this->record($n), [2, 4, 3, 5, 1]));
        $writer->store([$this->record(1, ['termin_start' => '2019-10-21', 'termin_end' => '2020-10-20',
            'source_details' => ['recurring' => 'renewal-1', 'infra_contract' => '4867']])]);
        $this->assertDatabaseCount('recurring_tagihan_ipas', 1);
        $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 6);
        $this->assertDatabaseHas('recurring_tagihan_ipas', ['site_code' => 'BKS648', 'termin' => '1–5',
            'term_count' => 6, 'amount' => '2196000001.50', 'termin_start' => '2014-10-21', 'termin_end' => '2020-10-20']);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));
        $this->getJson(route('infrastruktur.recurring-tagihan-ipas.data', ['length' => 10, 'draw' => 1]))
            ->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.term_count', 6);
        $this->getJson(route('infrastruktur.recurring-tagihan-ipas.show', RecurringTagihanIpas::first()->id))
            ->assertOk()->assertJsonCount(6, 'data')->assertJsonPath('data.0.source_details.po_number', 'PO-1');
    }

    public function test_repeat_upload_updates_the_term_without_erasing_other_contracts_or_metadata(): void
    {
        $writer = app(RecurringTagihanWriter::class);
        $writer->store([$this->record(1), $this->record(2)]);
        $import = new RecurringTagihanIpasImport;
        $rows = collect([collect(['site_id' => 'bks648', 'site_name' => 'BKS648IM1_APTMUTIARAARBITEL', 'tp' => 'DHOST',
            'contract_type' => 'NON-MLA', 'termin' => '1', 'periode_ke' => '1', 'termin_start' => '2014-10-21',
            'termin_end' => '2015-10-20', 'amount' => '100.25', 'recurring' => 'source-1'])]);
        $import->collection($rows);
        $import->collection($rows);
        $this->assertDatabaseCount('recurring_tagihan_ipas', 1);
        $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 2);
        $this->assertDatabaseHas('recurring_tagihan_ipas', ['amount' => '366000100.50', 'term_count' => 2]);
        $this->assertSame('PO-1', json_decode(DB::table('recurring_tagihan_ipas_terms')->where('termin', '1')->value('source_details'), true)['po_number']);
    }

    public function test_missing_labels_and_mixed_providers_are_preserved_and_filter_selects_whole_sites(): void
    {
        $this->assertSame('1, 3, 5', RecurringTagihanSummary::labels(collect(['5', '1', '3', '1'])));
        $this->assertSame('1–3, 7–8, Annual', RecurringTagihanSummary::labels(collect(['8', 'Annual', '2', '3', '1', '7'])));
        app(RecurringTagihanWriter::class)->store([$this->record(1), $this->record(2, ['tp' => 'OTHER'])]);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));
        $this->getJson(route('infrastruktur.recurring-tagihan-ipas.data', ['tp' => 'DHOST', 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.term_count', 2);
        $this->getJson(route('infrastruktur.recurring-tagihan-ipas.data', ['tp' => 'DHO', 'length' => 10]))
            ->assertOk()->assertJsonPath('recordsFiltered', 0);
    }

    public function test_analytics_counts_original_terms_and_filters_provider_without_double_counting_sites(): void
    {
        app(RecurringTagihanWriter::class)->store([
            $this->record(1, ['amount' => '100.25']),
            $this->record(2, ['tp' => 'OTHER', 'amount' => '200.50']),
            $this->record(1, ['site_code' => 'JKP846', 'contract_type' => 'MLA', 'amount' => '50.75']),
        ]);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));

        $this->get(route('infrastruktur.recurring-tagihan-ipas.index'))
            ->assertOk()->assertSee('tagihan-tp-chart')->assertSee('tagihan-contract-chart')->assertSee('tagihan-year-chart')
            ->assertSee('id="tagihan-table"', false)->assertDontSee('id="tagihan-drilldown-table"', false);

        $all = $this->getJson(route('infrastruktur.recurring-tagihan-ipas.analytics'))->assertOk();
        $all->assertJsonPath('metrics.sites', 2)
            ->assertJsonPath('metrics.details', 3)
            ->assertJsonPath('metrics.amount', '351.50')
            ->assertJsonPath('metrics.details_per_site', 1.5)
            ->assertJsonPath('providers.0.label', 'DHOST')
            ->assertJsonPath('providers.0.total', 2)
            ->assertJsonPath('contracts.0.label', 'NON-MLA')
            ->assertJsonPath('contracts.0.total', '300.75')
            ->assertJsonPath('years.0.label', '2014')
            ->assertJsonPath('years.0.total', 2);

        $filtered = $this->getJson(route('infrastruktur.recurring-tagihan-ipas.analytics', ['tp' => 'DHOST']))->assertOk();
        $filtered->assertJsonPath('metrics.sites', 2)
            ->assertJsonPath('metrics.details', 2)
            ->assertJsonPath('metrics.amount', '151.00')
            ->assertJsonCount(1, 'providers');

        $this->getJson(route('infrastruktur.recurring-tagihan-ipas.analytics', ['tp' => 'DHO']))
            ->assertOk()->assertJsonPath('metrics.sites', 0)->assertJsonPath('metrics.details', 0)
            ->assertJsonPath('metrics.details_per_site', 0)->assertJsonCount(0, 'years');
    }

    public function test_analytics_drilldown_returns_exact_source_terms_for_each_chart_bucket(): void
    {
        app(RecurringTagihanWriter::class)->store([
            $this->record(1, ['amount' => '100.25']),
            $this->record(2, ['tp' => 'OTHER', 'amount' => '200.50']),
            $this->record(1, ['site_code' => 'JKP846', 'contract_type' => 'MLA', 'amount' => '50.75']),
        ]);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));
        $route = fn (array $query) => route('infrastruktur.recurring-tagihan-ipas.details', $query + ['draw' => 1, 'length' => 10]);

        $this->getJson($route(['dimension' => 'tp', 'value' => 'DHOST']))
            ->assertOk()->assertJsonPath('recordsFiltered', 2)->assertJsonMissingPath('data.0.source_details');
        $this->getJson($route(['tp' => 'DHOST', 'dimension' => 'contract', 'value' => 'MLA']))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.site_code', 'JKP846');
        $this->getJson($route(['dimension' => 'year', 'value' => '2014']))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson($route(['dimension' => 'before_year', 'value' => '2015']))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson($route(['dimension' => 'contract_other', 'except' => ['NON-MLA']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.contract_type', 'MLA');
        $this->getJson($route(['dimension' => 'tp_other', 'except' => ['DHOST']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.tp', 'OTHER');
        $this->getJson($route(['dimension' => 'missing_year']))
            ->assertOk()->assertJsonPath('recordsFiltered', 0);
        $this->getJson($route(['dimension' => 'all', 'sort' => 'amount_desc']))
            ->assertOk()->assertJsonPath('recordsFiltered', 3)->assertJsonPath('data.0.amount', '200.50');
        $this->getJson($route(['tp' => 'DHOST', 'dimension' => 'all']))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson($route(['dimension' => 'all', 'columns' => [
            ['data' => 'site_code', 'name' => 'site_code', 'orderable' => 'true', 'searchable' => 'true'],
        ], 'order' => [['column' => 0, 'dir' => 'asc']]]))
            ->assertOk()->assertJsonPath('data.0.site_code', 'BKS648');
        $this->getJson($route(['dimension' => 'year', 'value' => 'invalid']))->assertStatus(422);
        $this->getJson($route(['dimension' => 'unknown']))->assertStatus(422);
    }

    public function test_chart_choices_filter_the_existing_site_table_without_changing_stored_site_totals(): void
    {
        app(RecurringTagihanWriter::class)->store([
            $this->record(1, ['amount' => '100.25']),
            $this->record(2, ['tp' => 'OTHER', 'contract_type' => 'MLA', 'amount' => '200.50']),
            $this->record(1, ['site_code' => 'JKP846', 'contract_type' => 'MLA', 'amount' => '50.75']),
        ]);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));
        $route = fn (array $query) => route('infrastruktur.recurring-tagihan-ipas.data', $query + ['draw' => 1, 'length' => 10]);

        $this->getJson($route(['dimension' => 'tp', 'value' => 'OTHER']))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.site_code', 'BKS648')
            ->assertJsonPath('data.0.term_count', 2)
            ->assertJsonPath('data.0.amount', '300.75');
        $this->getJson($route(['tp' => 'DHOST', 'dimension' => 'contract', 'value' => 'MLA']))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.site_code', 'JKP846');
        $this->getJson($route(['dimension' => 'contract_other', 'except' => ['NON-MLA']]))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson($route(['dimension' => 'year', 'value' => '2015']))
            ->assertOk()->assertJsonPath('recordsFiltered', 1)->assertJsonPath('data.0.site_code', 'BKS648');
        $this->getJson($route(['dimension' => 'before_year', 'value' => '2015']))
            ->assertOk()->assertJsonPath('recordsFiltered', 2);
        $this->getJson($route(['dimension' => 'missing_year']))
            ->assertOk()->assertJsonPath('recordsFiltered', 0);
        $this->getJson($route(['dimension' => 'all', 'sort' => 'amount_desc']))
            ->assertOk()->assertJsonPath('data.0.site_code', 'BKS648');
        $this->getJson($route(['dimension' => 'invalid']))->assertStatus(422);
    }

    public function test_excel_summary_has_one_site_per_row_and_a_single_date_range(): void
    {
        app(RecurringTagihanWriter::class)->store([$this->record(1), $this->record(2)]);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));
        $response = $this->get(route('infrastruktur.upload.export', 'recurring-tagihan-ipas'))->assertOk();
        $book = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $sheet = $book->getActiveSheet();
        $this->assertSame(2, $sheet->getHighestDataRow());
        $this->assertSame('Rentang Termin', $sheet->getCell('G1')->getValue());
        $this->assertSame('BKS648', $sheet->getCell('A2')->getValue());
        $this->assertSame('1–2', $sheet->getCell('E2')->getValue());
        $this->assertSame('21-10-2014 s.d. 20-10-2016', $sheet->getCell('G2')->getValue());
        $this->assertEquals('732000000.50', $sheet->getCell('I2')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_summary_upload_is_rejected_without_creating_a_fake_term(): void
    {
        $this->expectException(ValidationException::class);
        (new RecurringTagihanIpasImport)->collection(collect([collect(['site_id' => 'BKS648', 'termin' => '1–5', 'jumlah_rincian' => 5])]));
    }

    public function test_detail_csv_can_be_reimported_without_duplicates_or_metadata_loss(): void
    {
        app(RecurringTagihanWriter::class)->store([$this->record(1), $this->record(2)]);
        $this->actingAs(User::factory()->create(['account_status' => 'approved']));
        $response = $this->get(route('infrastruktur.upload.export', ['dataset' => 'recurring-tagihan-ipas', 'detail' => 1]))->assertOk();
        $csv = $response->streamedContent();
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, substr($csv, 3));
        rewind($stream);
        $headers = array_map(fn ($value) => Str::slug($value, '_'), fgetcsv($stream, escape: ''));
        $rows = collect();
        while (($values = fgetcsv($stream, escape: '')) !== false) {
            $rows->push(collect(array_combine($headers, $values)));
        }
        fclose($stream);
        (new RecurringTagihanIpasImport)->collection($rows);
        $this->assertDatabaseCount('recurring_tagihan_ipas', 1);
        $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 2);
        $this->assertDatabaseHas('recurring_tagihan_ipas', ['amount' => '732000000.50']);
        $this->assertSame('PO-1', json_decode(DB::table('recurring_tagihan_ipas_terms')->where('termin', '1')->value('source_details'), true)['po_number']);
    }

    public function test_currency_parser_preserves_cents_and_large_values(): void
    {
        $this->assertSame('9999999999999999.99', RecurringTagihanSummary::money('9999999999999999.99'));
        $this->assertSame('366000000.25', RecurringTagihanSummary::money('Rp 366.000.000,25'));
        $this->assertSame('366000000.25', RecurringTagihanSummary::money('366,000,000.25'));
        $this->assertNull(RecurringTagihanSummary::money(null));
    }

    public function test_snapshot_import_groups_sites_and_empty_snapshot_preserves_existing_data(): void
    {
        $book = new Spreadsheet;
        $sheet = $book->getActiveSheet();
        $sheet->fromArray([
            ['Site ID', 'Site Name', 'Termin', 'Termin Start', 'Termin End', 'Amount', 'Recurring'],
            ['BKS648', 'Bekasi', '1', '2014-10-21', '2015-10-20', '100.25', 'src-1'],
            ['BKS648', 'Bekasi', '2', '2015-10-21', '2016-10-20', '200.50', 'src-2'],
            ['JKP846', 'Jakarta', '1', '2018-02-14', '2019-02-13', '50.75', 'src-3'],
        ]);
        $path = sys_get_temp_dir().'/ipas-snapshot-'.uniqid().'.xlsx';
        try {
            (new Xlsx($book))->save($path);
            $this->artisan('dataset:import-recurring-tagihan-ipas', ['--file' => $path])->assertExitCode(0);
            $this->assertDatabaseCount('recurring_tagihan_ipas', 2);
            $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 3);
            $this->assertDatabaseHas('recurring_tagihan_ipas', ['site_code' => 'BKS648', 'termin' => '1–2', 'amount' => '300.75']);
            $sheet->removeRow(2, 3);
            (new Xlsx($book))->save($path);
            $this->artisan('dataset:import-recurring-tagihan-ipas', ['--file' => $path])->assertExitCode(1);
            $this->assertDatabaseCount('recurring_tagihan_ipas', 2);
            $this->assertDatabaseCount('recurring_tagihan_ipas_terms', 3);
        } finally {
            @unlink($path);
            $book->disconnectWorksheets();
        }
    }
}
