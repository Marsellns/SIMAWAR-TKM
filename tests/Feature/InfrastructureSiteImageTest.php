<?php

namespace Tests\Feature;

use App\Models\SewaLahanRenewal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InfrastructureSiteImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_a_photo_shared_by_site_code(): void
    {
        Storage::fake('private');
        SewaLahanRenewal::create(['site_code' => 'KRW008', 'site_name' => 'Cikampek 3']);
        $this->actingAs($this->userWithRole('admin'));

        $showUrl = route('infrastruktur.site-image.show', ['site_code' => 'KRW008']);
        $this->get($showUrl)->assertNotFound();

        $this->post(route('infrastruktur.site-image.store'), [
            'site_code' => ' krw008 ',
            'photo' => UploadedFile::fake()->image('tower.jpg', 1200, 300),
        ])->assertOk();
        $jpg = 'infrastructure-site-images/'.hash('sha256', 'KRW008').'.jpg';
        Storage::disk('private')->assertExists($jpg);
        $cacheControl = $this->get($showUrl)->assertOk()->baseResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('private', $cacheControl);
        $this->assertStringContainsString('no-store', $cacheControl);

        $this->post(route('infrastruktur.site-image.store'), [
            'site_code' => 'KRW008',
            'photo' => UploadedFile::fake()->image('tower.png', 1200, 300),
        ])->assertOk();
        Storage::disk('private')->assertMissing($jpg);
        Storage::disk('private')->assertExists('infrastructure-site-images/'.hash('sha256', 'KRW008').'.png');

        $this->delete(route('infrastruktur.site-image.destroy', ['site_code' => 'KRW008']))->assertOk();
        $this->get($showUrl)->assertNotFound();
    }

    public function test_only_admin_can_change_photos_and_unknown_sites_are_rejected(): void
    {
        Storage::fake('private');
        SewaLahanRenewal::create(['site_code' => 'KRW008', 'site_name' => 'Cikampek 3']);
        $viewer = $this->userWithRole('viewer');
        $this->actingAs($viewer);

        $this->post(route('infrastruktur.site-image.store'), [
            'site_code' => 'KRW008',
            'photo' => UploadedFile::fake()->image('tower.jpg'),
        ])->assertForbidden();
        $this->delete(route('infrastruktur.site-image.destroy', ['site_code' => 'KRW008']))->assertForbidden();

        $this->actingAs($this->userWithRole('admin'));
        $this->post(route('infrastruktur.site-image.store'), [
            'site_code' => 'UNKNOWN',
            'photo' => UploadedFile::fake()->image('tower.jpg'),
        ])->assertNotFound();
        $this->assertSame([], Storage::disk('private')->allFiles('infrastructure-site-images'));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['account_status' => 'approved']);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }
}
