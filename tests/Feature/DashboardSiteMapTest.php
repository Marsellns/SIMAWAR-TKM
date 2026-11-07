<?php

namespace Tests\Feature;

use App\Models\CombatSite;
use App\Models\SewaLahanRenewal;
use App\Models\Site;
use App\Support\SiteCoordinates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class DashboardSiteMapTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    public function test_map_uses_real_source_coordinates_and_retains_every_unmapped_site(): void
    {
        DB::table('site_owners')->insert([
            ['site_code' => 'BGR001', 'site_name' => 'Gandul', 'alamat' => 'Jl. Gandul 22', 'city' => 'Bogor', 'nop' => 'NOP BOGOR'],
            ['site_code' => 'BGR002', 'site_name' => 'Belum Ada Koordinat', 'alamat' => 'Jl. Raya 10', 'city' => 'Bogor', 'nop' => 'NOP BOGOR'],
        ]);
        CombatSite::factory()->create(['site_code' => ' bgr001 ', 'source_details' => ['database' => ['latitude' => '-6,392497', 'longitude' => '106,822832', 'nop' => 'BOGOR']]]);
        $response = $this->actingAs($this->userWithRole('viewer'))->getJson('/reports/site-map/feasibility')->assertOk();
        $response->assertJsonPath('summary.total', 2)->assertJsonPath('summary.mapped', 1)->assertJsonPath('summary.missing', 1);
        $this->assertSame(['lat' => -6.392497, 'lng' => 106.822832], $response->json('sites.0.point'));
        $response->assertJsonPath('sites.1.point', null)->assertJsonPath('sites.1.address', 'Jl. Raya 10');
    }

    public function test_infrastructure_map_matches_scope_year_ownership_and_excludes_deleted_rows(): void
    {
        foreach ([['TP01', 'TP', 2026], ['TSEL01', 'Telkomsel', 2026], ['OLD01', 'Telkomsel', 2025]] as [$code, $owner, $year]) {
            SewaLahanRenewal::create(['site_code' => $code, 'tahun_renewal' => $year, 'source_details' => ['ownership' => $owner, 'latitude' => '-6.2', 'longtitude' => '107.1']]);
        }
        $deleted = SewaLahanRenewal::create(['site_code' => 'DELETED', 'tahun_renewal' => 2026]); $deleted->delete();
        CombatSite::factory()->create(['site_code' => 'COMBAT01']);
        $this->actingAs($this->userWithRole('viewer'))->getJson('/reports/site-map/infrastructure?scope=sewa&tahun=2026&ownership_scope=TP')
            ->assertOk()->assertJsonPath('summary.total', 1)->assertJsonPath('sites.0.id', 'TP01')->assertJsonPath('sites.0.point.lng', 107.1);
    }

    public function test_cached_coordinate_is_reused_across_modules_and_invalidated_after_address_change(): void
    {
        DB::table('site_owners')->insert(['site_code' => 'BGR001', 'alamat' => 'Jl. Lama', 'city' => 'Bogor']);
        DB::table('site_locations')->insert(['site_code' => 'BGR001', 'address_hash' => SiteCoordinates::addressHash('Jl. Lama', 'Bogor'), 'latitude' => -6.5, 'longitude' => 106.8, 'source' => 'plus_code', 'status' => 'resolved']);
        DB::table('listrik_pln')->insert(['id_pelanggan' => '123', 'site_id' => 'BGR001', 'alamat' => 'Alamat lama PLN']);
        $this->actingAs($this->userWithRole('viewer'))->getJson('/reports/site-map/electricity?scope=centralized')->assertOk()->assertJsonPath('summary.mapped', 1);
        DB::table('site_owners')->where('site_code', 'BGR001')->update(['alamat' => 'Jl. Baru']);
        $this->getJson('/reports/site-map/feasibility')->assertOk()->assertJsonPath('summary.mapped', 0);
    }

    public function test_pnl_map_sums_selected_period_once_and_respects_profit_and_region_filters(): void
    {
        $site = Site::factory()->create(['site_id' => 'BGR001']);
        DB::table('site_owners')->insert(['site_code' => 'BGR001', 'city' => 'Bogor', 'nop' => 'NOP BOGOR']);
        foreach ([[1, 100, 40], [2, 200, 90]] as [$month, $revenue, $cost]) {
            DB::table('site_monthly_metrics')->insert(['site_id' => $site->id, 'tahun' => 2026, 'bulan' => $month, 'revenue' => $revenue, 'cost' => $cost, 'profit_loss' => $revenue - $cost, 'is_anomaly' => 0]);
        }
        $this->actingAs($this->userWithRole('viewer_profit_loss'))->getJson('/reports/site-map/pnl?tahun=2026&bulan=all&nop=NOP%20BOGOR&status=Profit')
            ->assertOk()->assertJsonPath('summary.total', 1)->assertJsonPath('sites.0.fields.0.value', 'Rp 300')->assertJsonPath('sites.0.fields.2.value', 'Rp 170');
        $this->getJson('/reports/site-map/pnl?tahun=2026&bulan=1&city=BEKASI')->assertOk()->assertJsonPath('summary.total', 0);
    }

    public function test_scoped_viewers_cannot_request_maps_from_other_modules(): void
    {
        foreach (['viewer_electricity' => 'electricity', 'viewer_profit_loss' => 'pnl', 'viewer_equipment_relocation' => 'equipment'] as $role => $ownModule) {
            $this->actingAs($this->userWithRole($role))->getJson('/reports/site-map/'.$ownModule)->assertOk();
            foreach (['pnl', 'electricity', 'equipment', 'infrastructure', 'feasibility'] as $module) if ($module !== $ownModule) $this->getJson('/reports/site-map/'.$module)->assertForbidden();
        }
        $this->actingAs($this->userWithRole('viewer_po_monitoring'))->getJson('/reports/site-map/pnl')->assertForbidden();
    }

    public function test_map_mounts_inside_existing_filament_pages_and_input_is_validated(): void
    {
        $this->actingAs($this->userWithRole('viewer'))->get('/data-potensi')->assertOk()->assertSee('data-dashboard-site-map', false);
        $this->getJson('/reports/site-map/infrastructure?scope=centralized')->assertUnprocessable();
        $this->getJson('/reports/site-map/pnl?bulan=99')->assertUnprocessable();
        $this->getJson('/reports/site-map/unknown')->assertNotFound();
    }

    public function test_local_resolution_decodes_available_plus_codes_without_external_address_requests(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        DB::table('site_owners')->insert(['site_code' => 'TST001', 'alamat' => 'G222+22, Jl. Contoh, Jonggol', 'city' => 'Bogor']);
        $before = DB::table('site_owners')->first();
        $this->artisan('sites:resolve-locations')->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'TST001', 'source' => 'plus_code', 'status' => 'resolved']);
        $this->assertEquals($before, DB::table('site_owners')->first());
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    public function test_guests_and_pending_accounts_cannot_read_site_coordinates(): void
    {
        $this->getJson('/reports/site-map/feasibility')->assertUnauthorized();
        $user = $this->userWithRole('viewer');
        $user->forceFill(['account_status' => 'pending'])->save();
        $this->actingAs($user)->getJson('/reports/site-map/feasibility')->assertForbidden();
    }

    public function test_geocoding_saves_only_an_unambiguous_address_match_without_changing_the_master(): void
    {
        $http = \Illuminate\Support\Facades\Http::class;
        $http::preventStrayRequests();
        config(['site-map.geocoding_url' => 'https://geocoder.example.test/api', 'site-map.geocoding_delay_ms' => 1000]);
        foreach (['GOOD', 'WRONG', 'AMBIGUOUS'] as $code) {
            DB::table('site_owners')->insert(['site_code' => $code, 'alamat' => "Jl. Contoh {$code} No. 12, Kec. Tamansari, Kab. Bogor", 'city' => 'Bogor']);
        }
        $before = DB::table('site_owners')->orderBy('site_code')->get();
        $this->artisan('sites:resolve-locations')->assertSuccessful();
        $http::assertNothingSent();
        $http::fake(function ($request) use ($http) {
            preg_match('/Contoh (GOOD|WRONG|AMBIGUOUS)/', $request['q'], $match);
            $code = $match[1];
            $feature = ['properties' => ['countrycode' => 'ID', 'osm_key' => 'building', 'street' => "Jalan Contoh {$code}", 'housenumber' => '12', 'district' => $code === 'WRONG' ? 'Bojong Gede' : 'Tamansari'], 'geometry' => ['type' => 'Point', 'coordinates' => [106.76, -6.68]]];
            $features = [$feature];
            if ($code === 'AMBIGUOUS') {
                $feature['geometry']['coordinates'] = [106.76, -6.69];
                $features[] = $feature;
            }
            return $http::response(['features' => $features]);
        });
        $this->artisan('sites:resolve-locations --geocode')->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'GOOD', 'status' => 'resolved', 'source' => 'photon', 'precision' => 'building', 'city' => 'Bogor', 'district' => 'Tamansari']);
        foreach (['WRONG', 'AMBIGUOUS'] as $code) $this->assertDatabaseHas('site_locations', ['site_code' => $code, 'status' => 'unresolved', 'latitude' => null]);
        $this->assertEquals($before, DB::table('site_owners')->orderBy('site_code')->get());
        $http::assertSentCount(5);
        $http::assertSent(fn ($request) => $request['countrycode'] === 'ID' && count(explode(',', $request['bbox'])) === 4);
        $this->artisan('sites:resolve-locations --geocode')->assertSuccessful();
        $http::assertSentCount(5);
    }

    public function test_a_clean_street_query_can_resolve_a_long_address_but_keeps_the_original_validation(): void
    {
        $http = \Illuminate\Support\Facades\Http::class;
        $http::preventStrayRequests();
        config(['site-map.geocoding_url' => 'https://geocoder.example.test/api', 'site-map.geocoding_delay_ms' => 1000]);
        DB::table('site_owners')->insert(['site_code' => 'GOOD', 'alamat' => 'Jl. Contoh Peta No.12, RT.03 RW.02, Kec. Tamansari, Kab. Bogor', 'city' => 'Bogor']);
        $http::fake(fn ($request) => $http::response(['features' => str_contains($request['q'], 'RT.03') ? [] : [
            ['properties' => ['countrycode' => 'ID', 'osm_key' => 'building', 'street' => 'Jalan Contoh Peta', 'housenumber' => '12', 'district' => 'Tamansari'], 'geometry' => ['type' => 'Point', 'coordinates' => [106.76, -6.68]]],
        ]]));
        $this->artisan('sites:resolve-locations --geocode')->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'GOOD', 'status' => 'resolved', 'precision' => 'building']);
        $http::assertSentCount(2);
        $http::assertSent(fn ($request) => $request['q'] === 'Jalan Contoh Peta No. 12, Bogor, Indonesia');
    }

    public function test_electricity_preserves_membership_of_the_same_site_in_both_masters(): void
    {
        DB::table('site_owners')->insert(['site_code' => 'BGR001', 'nop' => 'NOP BOGOR']);
        DB::table('listrik_pln')->insert(['id_pelanggan' => '123', 'site_id' => 'BGR001', 'nop' => 'NOP BOGOR']);
        DB::table('listrik_inbuilding')->insert(['site_id' => 'BGR001']);
        $response = $this->actingAs($this->userWithRole('viewer_electricity'))->getJson('/reports/site-map/electricity')->assertOk()->assertJsonPath('summary.total', 2);
        $this->assertNotSame($response->json('sites.0.key'), $response->json('sites.1.key'));
        $this->getJson('/reports/site-map/electricity?nop=BOGOR')->assertOk()->assertJsonPath('summary.total', 1)->assertJsonPath('sites.0.fields.0.value', 'Centralized');
        $this->getJson('/reports/site-map/electricity?scope=inbuilding&nop=BOGOR')->assertOk()->assertJsonPath('summary.total', 1)->assertJsonPath('sites.0.fields.0.value', 'Inbuilding');
    }
}
