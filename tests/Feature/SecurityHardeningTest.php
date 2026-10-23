<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Bapss;
use App\Models\UploadFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_user_can_login_and_logout_and_credentials_remain_hidden(): void
    {
        $user = User::factory()->create([
            'email' => 'approved@example.test', 'password' => 'Secure!Pass123',
            'account_status' => 'approved',
        ]);
        $this->post(route('login'), ['email' => $user->email, 'password' => 'Secure!Pass123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertOk();
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $this->post(route('logout'))->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_registration_requires_admin_approval_for_every_role(): void
    {
        Role::create(['name' => 'viewer', 'guard_name' => 'web']);

        $this->post(route('register'), [
            'name' => 'External User',
            'email' => 'external@example.test',
            'password' => 'Security!Pass123',
            'password_confirmation' => 'Security!Pass123',
            'requested_role' => 'viewer',
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'external@example.test')->firstOrFail();
        $this->assertSame('pending', $user->account_status);
        $this->assertTrue($user->getRoleNames()->isEmpty());

        $this->post(route('login'), [
            'email' => 'external@example.test',
            'password' => 'Security!Pass123',
        ])->assertSessionHasErrors('email');
    }

    public function test_viewer_cannot_import_financial_or_electricity_data(): void
    {
        $viewer = $this->approvedUserWithRole('viewer');

        $this->actingAs($viewer)->get(route('pnl.upload'))->assertForbidden();
        $this->actingAs($viewer)->post(route('pnl.upload.store'))->assertForbidden();
        $this->actingAs($viewer)->get(route('electricity.centralized.upload-flagging'))->assertForbidden();
        $this->actingAs($viewer)->post(route('electricity.centralized.upload-flagging.store'), [
            'file' => UploadedFile::fake()->create('listrik.xlsx'),
        ])->assertForbidden();
    }

    public function test_dashboard_api_requires_an_authenticated_web_session(): void
    {
        $this->getJson('/api/dashboard/periods')->assertUnauthorized();

        $viewer = $this->approvedUserWithRole('viewer');
        $this->actingAs($viewer)->getJson('/api/dashboard/periods')->assertOk();
    }

    public function test_site_map_permissions_apply_only_to_sewa_lahan_and_combat_pages(): void
    {
        $viewer = $this->approvedUserWithRole('viewer');

        foreach (['infrastruktur.sewa-lahan.index', 'infrastruktur.combat.index'] as $routeName) {
            $response = $this->actingAs($viewer)->get(route($routeName))->assertOk();
            $this->assertStringContainsString('geolocation=(self)', $response->headers->get('Permissions-Policy'));
            $this->assertStringContainsString('frame-src https://maps.google.com https://www.google.com', $response->headers->get('Content-Security-Policy'));
        }

        $dashboard = $this->get(route('dashboard'))->assertOk();
        $this->assertStringContainsString('geolocation=()', $dashboard->headers->get('Permissions-Policy'));
        $this->assertStringNotContainsString('frame-src https://maps.google.com', $dashboard->headers->get('Content-Security-Policy'));
    }

    public function test_presales_documents_are_stored_on_the_private_disk(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        $admin = $this->approvedUserWithRole('admin');

        $this->actingAs($admin)->post(route('presales.store'), [
            'document_title' => 'Dokumen rahasia',
            'document_number' => 'SEC-001',
            'document_file' => UploadedFile::fake()->create('rahasia.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $document = \App\Models\DocumentCirculation::firstOrFail();
        Storage::disk('private')->assertExists($document->file_path);
        Storage::disk('public')->assertMissing($document->file_path);
    }

    public function test_bapss_pdf_links_reject_unsafe_legacy_paths(): void
    {
        $viewer = $this->approvedUserWithRole('viewer');
        Bapss::factory()->create([
            'pdf_bapss' => 'bapss/document.pdf\" onclick=\"alert(1)',
            'pdf_ba_dismantle' => 'https://attacker.invalid/document.pdf',
        ]);

        $this->actingAs($viewer)
            ->getJson(route('infrastruktur.bapss.data'))
            ->assertOk()
            ->assertDontSee('alert(1)')
            ->assertDontSee('attacker.invalid');
    }

    public function test_general_uploads_are_not_written_to_public_storage(): void
    {
        Storage::fake('private');
        Storage::fake('public');
        $admin = $this->approvedUserWithRole('admin');

        $this->actingAs($admin)->post(route('infrastruktur.upload-file.store'), [
            'keterangan' => 'Dokumen internal',
            'file' => UploadedFile::fake()->create('internal.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $file = UploadFile::firstOrFail();
        Storage::disk('private')->assertExists($file->file_path);
        Storage::disk('public')->assertMissing($file->file_path);
        $this->actingAs($admin)->get(route('infrastruktur.upload-file.file', $file))->assertOk();
    }

    private function approvedUserWithRole(string $role): User
    {
        Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $user = User::factory()->create(['account_status' => 'approved']);
        $user->assignRole($role);

        return $user;
    }

    public function test_rejected_account_cannot_keep_using_a_preexisting_session(): void
    {
        $user = $this->approvedUserWithRole('admin');
        $this->actingAs($user);
        $user->update(['account_status' => 'rejected']);
        $this->get('/infrastruktur')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pending_account_session_cannot_access_financial_api(): void
    {
        $user = User::factory()->create(['account_status' => 'pending']);
        $this->actingAs($user)->getJson('/api/dashboard/periods')->assertForbidden();
        $this->assertGuest();
    }
}
