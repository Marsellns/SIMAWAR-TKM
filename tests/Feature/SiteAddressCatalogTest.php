<?php

namespace Tests\Feature;

use App\Models\SiteOwner;
use App\Support\SiteCoordinates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class SiteAddressCatalogTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    public function test_placeholder_master_uses_full_address_from_electricity_without_editing_the_master(): void
    {
        SiteOwner::create(['site_code' => 'TST826', 'alamat' => 'tbd', 'city' => 'Sukabumi']);
        $address = 'Jl. Contoh Cendana, Cikembar, Kabupaten Sukabumi';
        DB::table('listrik_pln')->insert(['id_pelanggan' => '123', 'site_id' => 'TST826', 'alamat' => $address]);
        DB::table('site_locations')->insert(['site_code' => 'TST826', 'address_hash' => SiteCoordinates::addressHash($address, 'Sukabumi'), 'latitude' => -6.95, 'longitude' => 106.8, 'source' => 'photon', 'precision' => 'street', 'status' => 'resolved']);
        $this->actingAs($this->userWithRole('viewer'))->getJson('/reports/site-map/feasibility')->assertOk()
            ->assertJsonPath('sites.0.address', $address)->assertJsonPath('summary.mapped', 1);
        $this->assertDatabaseHas('site_owners', ['site_code' => 'TST826', 'alamat' => 'tbd']);
        DB::table('listrik_pln')->where('site_id', 'TST826')->update(['alamat' => 'Jl. Alamat Baru, Cikembar']);
        $this->getJson('/reports/site-map/feasibility')->assertOk()->assertJsonPath('summary.mapped', 0);
    }

    public function test_a_real_master_address_has_precedence_over_a_different_legacy_address(): void
    {
        SiteOwner::create(['site_code' => 'BGR001', 'alamat' => 'Jl. Alamat Baru', 'city' => 'Bogor']);
        DB::table('listrik_pln')->insert(['id_pelanggan' => '123', 'site_id' => 'BGR001', 'alamat' => 'Jl. Alamat Lama']);
        DB::table('site_locations')->insert(['site_code' => 'BGR001', 'address_hash' => SiteCoordinates::addressHash('Jl. Alamat Lama', 'Bogor'), 'latitude' => -6.5, 'longitude' => 106.8, 'source' => 'photon', 'status' => 'resolved']);
        $this->actingAs($this->userWithRole('viewer'))->getJson('/reports/site-map/feasibility')->assertOk()
            ->assertJsonPath('sites.0.address', 'Jl. Alamat Baru')->assertJsonPath('summary.mapped', 0);
    }

    public function test_locality_in_a_fallback_address_is_not_confused_with_a_village_name(): void
    {
        DB::table('recurring_ipas')->insert(['site_code' => 'BDG700', 'alamat' => 'Desa Depok, Kecamatan Darangdan, Purwakarta']);
        DB::table('recurring_ipas')->insert(['site_code' => 'TST212', 'alamat' => 'DESACONTOHRT/RW05/03NO.13KECBUNGURSARIKABPURWAKARTA']);
        SiteOwner::create(['site_code' => 'TST829', 'alamat' => 'ogc', 'city' => 'Kota Bogor']);
        DB::table('listrik_pln')->insert(['id_pelanggan' => '123', 'site_id' => 'TST829', 'alamat' => 'Kelurahan Contoh, Kota Tangerang Selatan']);
        $catalog = app(\App\Services\SiteAddressCatalog::class)->rows();
        $this->assertSame('Purwakarta', $catalog->get('BDG700')->city);
        $this->assertSame('Purwakarta', $catalog->get('TST212')->city);
        $this->assertSame('Kota Tangerang Selatan', $catalog->get('TST829')->city);
    }

    public function test_a_short_valid_plus_code_is_preserved_as_the_master_address(): void
    {
        SiteOwner::create(['site_code' => 'BGR001', 'alamat' => 'G222+22', 'city' => 'Bogor']);
        DB::table('listrik_pln')->insert(['id_pelanggan' => '123', 'site_id' => 'BGR001', 'alamat' => 'Jl. Alamat Lain, Bogor']);
        $this->assertSame('G222+22', app(\App\Services\SiteAddressCatalog::class)->rows()->get('BGR001')->alamat);
    }

    public function test_explicit_localities_and_jakarta_plus_codes_resolve_locally(): void
    {
        \Illuminate\Support\Facades\Http::preventStrayRequests();
        foreach ([
            ['TST001', 'RW22+22, Kota Jakarta Timur'],
            ['TST002', 'RR22+22, Kota Jakarta'],
            ['CJR001', 'Jl. Contoh, Kabupaten Cianjur, Jawa Barat'],
        ] as [$code, $address]) DB::table('recurring_ipas')->insert(['site_code' => $code, 'alamat' => $address]);
        $catalog = app(\App\Services\SiteAddressCatalog::class)->rows();
        $this->assertSame('Cianjur', $catalog->get('CJR001')->city);
        $this->assertSame('Kota Jakarta Timur', $catalog->get('TST001')->city);
        $this->assertSame('Jakarta', $catalog->get('TST002')->city);
        $this->artisan('sites:resolve-locations')->assertSuccessful();
        foreach (['TST001', 'TST002'] as $code) $this->assertDatabaseHas('site_locations', ['site_code' => $code, 'status' => 'resolved', 'source' => 'plus_code']);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }
}
