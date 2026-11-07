<?php

namespace Tests\Feature;

use App\Models\SiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class TracyCoordinateImportTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    private function csv(string $rows): string
    {
        Storage::fake('local');
        Storage::disk('local')->put('coordinates.csv', "yearmonth,siteid,latitude,longitude,kabupaten,kecamatan\n".$rows);
        return Storage::disk('local')->path('coordinates.csv');
    }

    public function test_import_fills_missing_sites_and_preserves_operational_master_and_existing_locations(): void
    {
        SiteOwner::create(['site_code' => 'BGR001', 'alamat' => 'Jl. Contoh 1', 'city' => 'Bogor']);
        SiteOwner::create(['site_code' => 'BGR002', 'alamat' => 'Jl. Contoh 2', 'city' => 'Bogor']);
        DB::table('site_locations')->insert(['site_code' => 'BGR002', 'latitude' => -6.7, 'longitude' => 106.8, 'source' => 'master', 'status' => 'resolved']);
        $before = DB::table('site_owners')->orderBy('site_code')->get()->toJson();
        $file = $this->csv("202606,BGR001,-6.5,106.8,Bogor,Ciampea\n202607,BGR001,-6.6,106.9,Bogor,Ciampea\n202607,BGR002,-6.4,106.7,Bogor,Cibinong\n202607,UNKNOWN,-6.4,106.7,Bogor,Ciampea\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'BGR001', 'source' => 'tracy_csv:202607:BGR001', 'latitude' => -6.6, 'longitude' => 106.9]);
        $this->assertDatabaseHas('site_locations', ['site_code' => 'BGR002', 'source' => 'master', 'latitude' => -6.7]);
        $this->assertDatabaseMissing('site_locations', ['site_code' => 'UNKNOWN']);
        $this->assertSame($before, DB::table('site_owners')->orderBy('site_code')->get()->toJson());
        $this->actingAs($this->userWithRole('viewer'))->getJson('/reports/site-map/feasibility')->assertOk()
            ->assertJsonPath('summary.mapped', 2)->assertJsonPath('sites.0.coordinate_source', 'Koordinat dataset Tracy · 2026-07 · BGR001')
            ->assertJsonPath('sites.0.district', 'Ciampea')->assertJsonPath('sites.1.district', 'Cibinong');
        DB::table('site_owners')->where('site_code', 'BGR001')->update(['alamat' => 'Jl. Contoh diperbaiki']);
        $this->getJson('/reports/site-map/feasibility')->assertOk()->assertJsonPath('summary.mapped', 2);
    }

    public function test_dry_run_never_writes_and_an_invalid_csv_does_not_create_locations(): void
    {
        SiteOwner::create(['site_code' => 'BGR001']);
        $file = $this->csv("202607,BGR001,-6.5,106.8,Bogor\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file, '--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('site_locations', 0);
        file_put_contents($file, "siteid,latitude\nBGR001,-6.5\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertFailed();
        $this->assertDatabaseCount('site_locations', 0);
    }

    public function test_explicit_physical_site_alias_requires_the_same_municipality(): void
    {
        SiteOwner::create(['site_code' => 'ALS101', 'site_name' => 'PHY101 CONTOH SITE', 'city' => 'BOGOR']);
        SiteOwner::create(['site_code' => 'ALS999', 'site_name' => 'PHY101 NAMA LAIN', 'city' => 'Kota Bekasi']);
        SiteOwner::create(['site_code' => 'ALS998', 'site_name' => 'SEPERTI PHY101', 'city' => 'BOGOR']);
        $file = $this->csv("202607,PHY101,-6.6,106.7,Bogor\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'ALS101', 'source' => 'tracy_csv:202607:PHY101']);
        $this->assertDatabaseMissing('site_locations', ['site_code' => 'ALS999']);
        $this->assertDatabaseMissing('site_locations', ['site_code' => 'ALS998']);
    }

    public function test_local_resolver_preserves_imported_coordinates_and_only_a_newer_csv_updates_them(): void
    {
        SiteOwner::create(['site_code' => 'BGR001', 'alamat' => 'Jl. Contoh', 'city' => 'Bogor']);
        $file = $this->csv("202607,BGR001,-6.5,106.8,Bogor\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertSuccessful();
        $this->artisan('sites:resolve-locations', ['--retry' => true])->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'BGR001', 'source' => 'tracy_csv:202607:BGR001']);
        file_put_contents($file, "yearmonth,siteid,latitude,longitude,kabupaten\n202606,BGR001,-6.4,106.7,Bogor\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'BGR001', 'source' => 'tracy_csv:202607:BGR001']);
        file_put_contents($file, "yearmonth,siteid,latitude,longitude,kabupaten\n202608,BGR001,-6.6,106.9,Bogor\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'BGR001', 'source' => 'tracy_csv:202608:BGR001', 'latitude' => -6.6]);
    }

    public function test_rru_coordinates_outside_the_current_municipality_are_not_imported(): void
    {
        SiteOwner::create(['site_code' => 'TST826', 'city' => 'Sukabumi']);
        $file = $this->csv("202608,TST826,-6.3,107.3,Karawang,\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file, '--source' => 'rru_inventory'])->assertSuccessful();
        $this->assertDatabaseMissing('site_locations', ['site_code' => 'TST826']);
    }

    public function test_explicit_current_and_former_site_notation_uses_the_current_identifier(): void
    {
        \App\Models\SewaLahanRenewal::create(['site_code' => 'PHY001 EX PHY002', 'site_name' => 'CONTOH IDENTITAS']);
        $file = $this->csv("202607,PHY001,-6.6,106.8,Kota Bogor,Bogor Tengah\n202607,PHY002,-6.7,106.8,Bogor,Ciampea\n");
        $this->artisan('sites:import-tracy-coordinates', ['file' => $file])->assertSuccessful();
        $this->assertDatabaseHas('site_locations', ['site_code' => 'PHY001 EX PHY002', 'source' => 'tracy_csv:202607:PHY001', 'latitude' => -6.6]);
    }
}
