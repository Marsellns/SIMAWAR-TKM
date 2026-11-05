<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class DashboardViewerAccessTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    public static function scopedRoles(): array
    {
        return [
            'electricity' => ['viewer_electricity', '/electricity', '/electricity/dashboard-data', 'Electricity Monitoring'],
            'po monitoring' => ['viewer_po_monitoring', '/po-monitoring', '/po-monitoring/details?dataset=hq', 'PO Monitoring'],
            'equipment relocation' => ['viewer_equipment_relocation', '/equipment-relocation', '/equipment-relocation/relocation-data', 'Equipment'],
            'profit and loss' => ['viewer_profit_loss', '/pnl', '/pnl/analytics?tahun=2026&bulan=all', 'Profit & Loss'],
        ];
    }

    #[DataProvider('scopedRoles')]
    public function test_scoped_user_only_sees_and_reads_their_assigned_module(string $role, string $home, string $dataUrl, string $group): void
    {
        $this->actingAs($this->userWithRole($role));

        $this->get('/')->assertRedirect($home);
        $this->get('/dashboard')->assertRedirect($home);
        $this->get('/report')->assertRedirect($home);

        $page = $this->get($home)->assertOk()
            ->assertSee('data-group-label="'.e($group).'"', false)
            ->assertDontSee('data-group-label="Dashboard"', false)
            ->assertDontSee('fi-topbar-database-notifications-btn', false);
        $this->getJson($dataUrl)->assertOk();
        $this->getJson('/reports/data-version')->assertOk();

        foreach (self::scopedRoles() as [$otherRole, $otherHome, $otherData, $otherGroup]) {
            if ($otherRole !== $role) {
                $page->assertDontSee('data-group-label="'.e($otherGroup).'"', false);
                $this->get($otherHome)->assertForbidden();
                $this->getJson($otherData)->assertForbidden();
            }
        }

        foreach (['/dashboard/chart-data', '/api/dashboard/pnl-summary', '/infrastruktur', '/data-potensi', '/notifications', '/report/user-management'] as $url) {
            $this->getJson($url)->assertForbidden();
        }

        $this->post('/report/logout')->assertRedirect();
        $this->assertGuest();
    }

    public function test_scoped_access_blocks_write_requests_and_write_forms_even_with_a_manager_role(): void
    {
        $manager = $this->userWithRole('manager_nop');
        $manager->assignRole('viewer_equipment_relocation');
        $this->actingAs($manager);
        $this->postJson('/equipment-relocation/relocation-data')->assertForbidden();
        $this->deleteJson('/equipment-relocation/relocation-data')->assertForbidden();
        $this->post('/equipment-relocation/import-excel')->assertForbidden();

        $this->actingAs($this->userWithRole('viewer_electricity'));
        $this->get('/electricity/inbuilding/input-tagihan-ibc/create')->assertForbidden();

        $this->actingAs($this->userWithRole('viewer_po_monitoring'));
        $this->get('/po-monitoring/presales/create')->assertForbidden();
        $this->post('/po-monitoring/presales')->assertForbidden();
    }

    public function test_registration_requests_a_scoped_role_and_login_uses_the_approved_dashboard(): void
    {
        $this->get('/register')->assertOk()->assertSee('Viewer Equipment Relocation');
        $email = 'equipment-viewer@example.test';
        $password = 'StrongPass!1234';
        $this->post('/register', [
            'name' => 'Equipment Viewer', 'email' => $email, 'password' => $password,
            'password_confirmation' => $password, 'requested_role' => 'viewer_equipment_relocation',
        ])->assertRedirect('/login');

        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame('pending', $user->account_status);
        $this->assertTrue($user->getRoleNames()->isEmpty());
        $this->actingAs($this->userWithRole('admin'))
            ->post(route('admin.user-approvals.approve', $user))->assertRedirect();
        $this->assertSame(['viewer_equipment_relocation'], $user->fresh()->getRoleNames()->all());

        $this->post('/logout');
        $this->post('/login', ['email' => $email, 'password' => $password])->assertRedirect('/equipment-relocation');
    }
}
