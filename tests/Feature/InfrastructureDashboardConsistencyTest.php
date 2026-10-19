<?php

namespace Tests\Feature;

use App\Http\Controllers\InfrastructureDashboardController;
use App\Models\CombatSite;
use App\Models\SewaLahanRenewal;
use App\Support\InfrastructureCanonicalSites;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InfrastructureDashboardConsistencyTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        config()->set('database.connections.infrastructure_dashboard_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('infrastructure_dashboard_testing');
        DB::setDefaultConnection('infrastructure_dashboard_testing');

        Schema::create('site_owners', function (Blueprint $table): void {
            $table->id();
            $table->string('site_code');
            $table->string('site_owner')->nullable();
            $table->timestamps();
        });
        foreach (['sewa_lahan_renewals', 'combat_sites'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name): void {
                $table->id();
                $table->json('source_details')->nullable();
                $table->string('site_code');
                $table->string('site_name')->nullable();
                $table->integer($name === 'combat_sites' ? 'tahun_justi_dirnet' : 'tahun_renewal')->nullable();
                $table->string('status_dokumen')->nullable();
                $table->string('status_perpanjangan')->nullable();
                $table->string('no_pks_baru')->nullable();
                $table->string('no_pks_lama')->nullable();
                $table->date('end_date_baru')->nullable();
                $table->date('end_date_lama')->nullable();
                $table->decimal('total_harga_baru', 18, 2)->nullable();
                $table->decimal('harga_baru', 18, 2)->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('infrastructure_dashboard_testing');
        DB::setDefaultConnection($this->originalConnection);

        parent::tearDown();
    }

    public function test_overview_renewal_chart_uses_only_sewa_years_and_risk_drilldown_matches_the_card(): void
    {
        SewaLahanRenewal::query()->create([
            'site_code' => 'SEWA-CONTRACT',
            'tahun_renewal' => 2025,
            'status_dokumen' => 'A. Negosiasi',
            'no_pks_baru' => 'PKS-001',
            'total_harga_baru' => 100,
        ]);
        SewaLahanRenewal::query()->create([
            'site_code' => 'SEWA-NO-PKS',
            'tahun_renewal' => 2025,
            'status_dokumen' => 'K. Pembayaran Done',
            'total_harga_baru' => 200,
        ]);
        CombatSite::query()->create([
            'site_code' => 'COMBAT-SAFE',
            'tahun_justi_dirnet' => 2030,
            'status_dokumen' => 'J. Reimburse RPJ Done',
            'no_pks_baru' => 'PKS-002',
            'total_harga_baru' => 300,
        ]);

        $controller = app(InfrastructureDashboardController::class);
        $overview = $controller->data(Request::create('/infrastruktur/dashboard-data'))->getData(true);

        $this->assertSame(3, $overview['cards']['total']);
        $this->assertSame(300, (int) $overview['cards']['risk_value']);
        $this->assertSame([['name' => '2025', 'y' => 2, 'filter_field' => 'tahun', 'filter_value' => '2025']], $overview['renewal_years']);

        $combat = $controller->data(Request::create('/infrastruktur/dashboard-data', 'GET', ['scope' => 'combat']))->getData(true);
        $this->assertSame('2030', $combat['renewal_years'][0]['name']);

        $risk = $controller->details(Request::create('/infrastruktur/dashboard-details', 'GET', [
            'filter_field' => 'summary_status',
            'filter_value' => 'risk',
        ]))->getData(true);

        $this->assertSame(2, $risk['count']);
        $this->assertEqualsCanonicalizing(['SEWA-CONTRACT', 'SEWA-NO-PKS'], array_column($risk['data'], 'site_code'));
    }

    public function test_duplicate_site_rows_are_selected_before_grouping_by_year(): void
    {
        SewaLahanRenewal::query()->create([
            'site_code' => 'CROSS-YEAR',
            'tahun_renewal' => 2025,
            'status_dokumen' => 'A. Negosiasi',
        ]);
        SewaLahanRenewal::query()->create([
            'site_code' => 'CROSS-YEAR',
            'tahun_renewal' => 2027,
        ]);

        $selected = InfrastructureCanonicalSites::fromRows(SewaLahanRenewal::query()->get());
        $this->assertSame(2025, $selected->first()->tahun_renewal);

        $overview = app(InfrastructureDashboardController::class)
            ->data(Request::create('/infrastruktur/dashboard-data'))
            ->getData(true);
        $this->assertSame(2, $overview['cards']['total']);
        $this->assertSame(1, $overview['cards']['unique_sites']);
        $this->assertSame([['name' => '2025', 'y' => 1, 'filter_field' => 'tahun', 'filter_value' => '2025']], $overview['renewal_years']);

        // Chart drill-downs retain the same canonical selection as their chart.
        foreach (['all', 'sewa'] as $scope) {
            $cards = app(InfrastructureDashboardController::class)
                ->data(Request::create('/infrastruktur/dashboard-data', 'GET', ['scope' => $scope]))
                ->getData(true)['cards'];
            $details = app(InfrastructureDashboardController::class)
                ->details(Request::create('/infrastruktur/dashboard-details', 'GET', ['scope' => $scope]))
                ->getData(true);
            $this->assertSame($cards['unique_sites'], $details['count']);
            $this->assertSame(['CROSS-YEAR'], array_column($details['data'], 'site_code'));
        }
    }

    public function test_total_site_cards_and_details_include_repeated_rows_in_every_scope(): void
    {
        foreach ([2025, 2026] as $year) {
            SewaLahanRenewal::query()->create(['site_code' => 'SHARED', 'tahun_renewal' => $year]);
            CombatSite::query()->create(['site_code' => 'SHARED', 'tahun_justi_dirnet' => $year]);
        }
        $controller = app(InfrastructureDashboardController::class);

        foreach (['all' => 4, 'sewa' => 2, 'combat' => 2] as $scope => $expected) {
            $cards = $controller->data(Request::create('/infrastruktur/dashboard-data', 'GET', [
                'scope' => $scope,
            ]))->getData(true)['cards'];
            $details = $controller->details(Request::create('/infrastruktur/dashboard-details', 'GET', [
                'scope' => $scope,
                'filter_field' => 'all_records',
            ]))->getData(true);

            $this->assertSame($expected, $cards['total']);
            $this->assertSame($expected, $details['count']);
            $this->assertCount($expected, $details['data']);
            $this->assertSame(1, $cards['unique_sites']);
        }
    }

    public function test_status_cards_and_drilldowns_count_every_database_row_even_with_repeated_site_ids(): void
    {
        SewaLahanRenewal::query()->create(['site_code' => 'SEWA-ON', 'source_details' => ['status' => 'On Air']]);
        SewaLahanRenewal::query()->create(['site_code' => 'SEWA-OFF', 'source_details' => ['status' => 'Off Air']]);
        SewaLahanRenewal::query()->create(['site_code' => 'SEWA-ON', 'source_details' => ['status' => 'On Air']]);
        CombatSite::query()->create(['site_code' => 'COMBAT-ON', 'source_details' => ['database' => ['status' => 'On Air']]]);
        CombatSite::query()->create(['site_code' => 'COMBAT-OFF', 'source_details' => ['database' => ['status' => 'Off Air']]]);
        CombatSite::query()->create(['site_code' => 'COMBAT-OFF', 'source_details' => ['database' => ['status' => 'Off Air']]]);

        $controller = app(InfrastructureDashboardController::class);
        foreach ([
            'sewa' => ['active' => 2, 'off_air' => 1, 'on_site' => 'SEWA-ON', 'off_site' => 'SEWA-OFF'],
            'combat' => ['active' => 1, 'off_air' => 2, 'on_site' => 'COMBAT-ON', 'off_site' => 'COMBAT-OFF'],
        ] as $scope => $expected) {
            $response = $controller->data(Request::create('/infrastruktur/dashboard-data', 'GET', [
                'scope' => $scope,
            ]))->getData(true);
            $cards = $response['cards'];

            $this->assertSame(3, $cards['total']);
            $this->assertSame(2, $cards['unique_sites']);
            $this->assertSame($expected['active'], $cards['active']);
            $this->assertSame($expected['off_air'], $cards['off_air']);
            $this->assertSame(3, $cards['without_pks']);
            $this->assertSame(3, array_sum(array_column($response['status'], 'y')));
            foreach ($response['status'] as $statusSlice) {
                $details = $controller->details(Request::create('/infrastruktur/dashboard-details', 'GET', [
                    'scope' => $scope,
                    'filter_field' => 'status',
                    'filter_value' => $statusSlice['filter_value'],
                ]))->getData(true);
                $this->assertSame($statusSlice['y'], $details['count']);
            }

            foreach (['active' => 'on_site', 'off_air' => 'off_site'] as $status => $siteKey) {
                $details = $controller->details(Request::create('/infrastruktur/dashboard-details', 'GET', [
                    'scope' => $scope,
                    'filter_field' => 'summary_status',
                    'filter_value' => $status,
                ]))->getData(true);

                $this->assertSame($expected[$status], $details['count']);
                $this->assertSame(array_fill(0, $expected[$status], $expected[$siteKey]), array_column($details['data'], 'site_code'));
                $this->assertSame($status === 'active' ? 'On Air' : 'Off Air', $details['data'][0]['status_site']);
            }

            $withoutPks = $controller->details(Request::create('/infrastruktur/dashboard-details', 'GET', [
                'scope' => $scope,
                'filter_field' => 'summary_status',
                'filter_value' => 'without_pks',
            ]))->getData(true);
            $this->assertSame($cards['without_pks'], $withoutPks['count']);
        }

        $overview = $controller->data(Request::create('/infrastruktur/dashboard-data'))->getData(true);
        $this->assertSame(6, $overview['cards']['total']);
        $this->assertSame(4, $overview['cards']['unique_sites']);
        $this->assertSame(3, $overview['cards']['active']);
        $this->assertSame(3, $overview['cards']['off_air']);
        $this->assertSame(6, array_sum(array_column($overview['status'], 'y')));

        SewaLahanRenewal::query()->create(['site_code' => 'NEW-ON', 'source_details' => ['status' => 'On Air']]);
        $updated = $controller->data(Request::create('/infrastruktur/dashboard-data'))->getData(true);
        $this->assertSame(7, $updated['cards']['total']);
        $this->assertSame(4, $updated['cards']['active']);
    }

    public function test_overview_alerts_count_an_overlapping_site_once(): void
    {
        $endDate = today()->subDay()->toDateString();
        SewaLahanRenewal::query()->create([
            'site_code' => 'OVERLAP-001',
            'status_dokumen' => 'A. Negosiasi',
            'end_date_baru' => $endDate,
        ]);
        CombatSite::query()->create([
            'site_code' => 'OVERLAP-001',
            'end_date_baru' => $endDate,
        ]);

        $overview = app(InfrastructureDashboardController::class)
            ->data(Request::create('/infrastruktur/dashboard-data'))
            ->getData(true);

        $this->assertSame(2, $overview['cards']['total']);
        $this->assertSame(1, $overview['cards']['unique_sites']);
        $this->assertCount(1, $overview['alerts']['expired']);
        $this->assertSame('Sewa Lahan', $overview['alerts']['expired'][0]['source']);
    }

    public function test_year_and_ownership_filters_use_the_same_rows_for_charts_cards_and_details(): void
    {
        SewaLahanRenewal::query()->create([
            'site_code' => 'TP-SHARED', 'tahun_renewal' => 2025,
            'source_details' => ['ownership' => 'TP', 'status' => 'On Air'],
            'status_dokumen' => 'Negosiasi',
        ]);
        SewaLahanRenewal::query()->create([
            'site_code' => 'TP-SHARED', 'tahun_renewal' => 2026,
            'source_details' => ['ownership' => 'TP', 'status' => 'Off Air'],
        ]);
        SewaLahanRenewal::query()->create([
            'site_code' => 'TEL-2026', 'tahun_renewal' => 2026,
            'source_details' => ['ownership' => 'Telkomsel', 'status' => 'On Air'],
        ]);
        CombatSite::query()->create([
            'site_code' => 'COMBAT-2026', 'tahun_justi_dirnet' => 2026,
            'source_details' => ['database' => ['status' => 'On Air']],
        ]);

        $controller = app(InfrastructureDashboardController::class);
        $request = ['scope' => 'sewa', 'tahun' => '2026', 'ownership_scope' => 'TP'];
        $filtered = $controller->data(Request::create('/infrastruktur/dashboard-data', 'GET', $request))->getData(true);
        $this->assertSame(1, $filtered['cards']['total']);
        $this->assertSame(1, $filtered['cards']['unique_sites']);
        $this->assertSame(1, $filtered['cards']['off_air']);
        $this->assertSame([['name' => '2026', 'y' => 1, 'filter_field' => 'tahun', 'filter_value' => '2026']], $filtered['renewal_years']);
        $this->assertSame(1, collect($filtered['owners'])->firstWhere('name', 'TP')['y']);
        $this->assertSame(1, array_sum(array_column($filtered['status'], 'y')));

        $details = $controller->details(Request::create('/infrastruktur/dashboard-details', 'GET', $request + ['filter_field' => 'all_records']))->getData(true);
        $this->assertSame($filtered['cards']['total'], $details['count']);
        $this->assertSame(['TP-SHARED'], array_column($details['data'], 'site_code'));
        $this->assertSame([2026], array_column($details['data'], 'tahun'));

        $overview = $controller->data(Request::create('/infrastruktur/dashboard-data', 'GET', ['tahun' => '2026']))->getData(true);
        $this->assertSame(3, $overview['cards']['total']);
        $this->assertSame(3, $overview['cards']['unique_sites']);
        $this->assertSame(2, $overview['dataset_rows']['sewa_lahan']);
        $this->assertSame(1, $overview['dataset_rows']['combat']);

        $combat = $controller->data(Request::create('/infrastruktur/dashboard-data', 'GET', ['scope' => 'combat', 'tahun' => '2025']))->getData(true);
        $this->assertSame(0, $combat['cards']['total']);
        $this->assertSame([], $combat['renewal_years']);
    }
}
