<?php

namespace Tests\Feature;

use App\Models\CombatSite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class CombatDetailModalTest extends TestCase
{
    use RefreshDatabase, CreatesUsersWithRoles;

    public function test_detail_uses_the_selected_combat_record_and_real_site_history(): void
    {
        CombatSite::create([
            'site_code' => 'COM-001', 'site_name' => 'Nama Lama', 'tahun_justi_dirnet' => 2024,
            'source_details' => ['status_justifikasi' => 'Belum Justifikasi'],
        ]);
        $current = CombatSite::create([
            'site_code' => 'COM-001', 'site_name' => 'Nama Baru', 'tahun_justi_dirnet' => 2025,
            'status_dokumen' => 'A. Negosiasi',
            'source_details' => [
                'database' => ['status' => 'Off Air', 'nop' => 'BEKASI', 'latitude' => -6.25, 'longitude' => 106.97],
                'database_revenue' => ['rev_jan_26' => 1500, 'cost_jan_26' => 500],
            ],
        ]);
        CombatSite::create(['site_code' => 'OTHER', 'site_name' => 'Site Lain', 'tahun_justi_dirnet' => 2025]);

        $this->actingAs($this->userWithRole('viewer'));
        $this->getJson(route('infrastruktur.combat.show', $current))
            ->assertOk()
            ->assertJsonPath('site.site_name', 'Nama Baru')
            ->assertJsonPath('site.source_details.status', 'Off Air')
            ->assertJsonPath('site.source_details.nop', 'BEKASI')
            ->assertJsonPath('site.performance.0.revenue', 1500)
            ->assertJsonPath('history.0.year', 2025)
            ->assertJsonPath('history.1.year', 2024)
            ->assertJsonPath('history.1.status_justifikasi', 'Belum Justifikasi')
            ->assertJsonMissingPath('site.edit_url')
            ->assertJsonCount(2, 'history');
    }

    public function test_popup_edit_keeps_existing_admin_permission_and_validation(): void
    {
        $combat = CombatSite::create(['site_code' => 'EDIT-COM', 'site_name' => 'Combat Lama']);
        $editUrl = route('infrastruktur.combat.edit', $combat);

        $this->actingAs($this->userWithRole('viewer'));
        $this->get($editUrl.'?modal=1')->assertForbidden();
        $this->putJson(route('infrastruktur.combat.update', $combat), ['site_code' => 'EDIT-COM'])->assertForbidden();

        $this->actingAs($this->userWithRole('admin'));
        $this->get($editUrl.'?modal=1')->assertOk()->assertSee('data-combat-edit-form', false);
        $this->getJson(route('infrastruktur.combat.show', $combat))
            ->assertOk()->assertJsonPath('site.edit_url', $editUrl);
        $this->putJson(route('infrastruktur.combat.update', $combat), [
            'site_code' => 'EDIT-COM', 'site_name' => 'Combat Baru', 'status_dokumen' => 'A. Negosiasi',
        ])->assertOk()->assertJsonPath('message', 'Combat site EDIT-COM berhasil diperbarui.');
        $this->assertDatabaseHas('combat_sites', ['id' => $combat->id, 'site_name' => 'Combat Baru']);
        $this->putJson(route('infrastruktur.combat.update', $combat), [
            'site_code' => 'EDIT-COM', 'harga_baru' => -1,
        ])->assertUnprocessable()->assertJsonValidationErrors('harga_baru');
    }
}
