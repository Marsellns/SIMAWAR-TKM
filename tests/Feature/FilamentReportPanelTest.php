<?php

namespace Tests\Feature;

use App\Filament\Pages\AccountApprovals;
use App\Filament\Modules\ReportModuleRegistry;
use App\Models\PoHq;
use App\Models\User;
use App\Livewire\ReportNotifications;
use App\Support\ReportModules;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class FilamentReportPanelTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    public function test_guest_must_login_to_access_the_filament_panel(): void
    {
        $this->get('/report')->assertRedirect('/report/login');
        $this->get('/report/login')->assertOk()->assertSee('wire:snapshot', false);
    }

    public function test_approved_report_user_can_access_the_panel_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.force_https' => false]);
        $user = $this->userWithRole('report');

        $this->actingAs($user)->get('https://localhost/report')->assertRedirect(route('dashboard'));
        $this->get('https://localhost/dashboard')->assertOk()->assertSee('fi-sidebar', false)
            ->assertDontSee('Ringkasan Laporan');
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('report')));
        $user->update(['account_status' => 'rejected']);
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('report')));
    }

    public function test_all_navigation_modules_render_inside_filament(): void
    {
        $this->actingAs($this->userWithRole('viewer'));
        foreach (ReportModules::navigation() as $module) {
            $response = $this->get(route($module[2], $module[5] ?? []));
            $response->assertOk()->assertSee('fi-sidebar', false)
                ->assertSee('simaster-report', false)->assertSee('wire:snapshot', false);
            $sourceRoute = app('router')->getRoutes()->getByName($module[2]);
            $page = ReportModuleRegistry::pageForRoute($sourceRoute, $module[5] ?? []);
            $this->assertNotNull($page);
            $response->assertSee($page::reportComponentName(), false);
        }
    }

    public function test_dashboard_group_headings_link_to_their_modules(): void
    {
        $this->actingAs($this->userWithRole('viewer'));
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Infrastruktur Management')
            ->assertSee('class="fi-sidebar-group-label simaster-sidebar-group-link simaster-module-trigger"', false)
            ->assertSee('data-simaster-dashboard-url="'.route('infrastruktur.index').'"', false)
            ->assertSee('data-simaster-dashboard-url="'.route('electricity.dashboard').'"', false)
            ->assertDontSee('Dashboard Infrastruktur')
            ->assertDontSee('Dashboard Electricity');
        $this->get(route('infrastruktur.index'))->assertOk()->assertSee('Infrastruktur Performance');
        $this->get(route('electricity.dashboard'))->assertOk()->assertSee('Electricity Monitoring');
        $this->assertSame('Feasibility Site', collect(Filament::getNavigation())->last()->getLabel());
        $this->get(route('dashboard'))->assertSee('assets/modules/infrastructure.svg', false)
            ->assertSee('assets/modules/feasibility.svg', false)->assertDontSee('data-group-label="Data Potensi"', false);
        $descendants = function ($items) use (&$descendants) {
            return collect($items)->flatMap(fn ($item) => [$item, ...$descendants($item->getChildItems())]);
        };
        foreach (ReportModules::dashboardGroups() as $label => $dashboardRoute) {
            $group = collect(Filament::getNavigation())->first(fn ($item) => $item->getLabel() === $label);
            $this->assertNotNull($group);
            $this->assertSame(route($dashboardRoute), $group->getExtraSidebarAttributes()['data-simaster-dashboard-url']);
            $expected = collect(ReportModules::navigation())->where('0', $label);
            $items = $group->getItems();
            $nested = $descendants($items);
            $this->assertCount($expected->count(), $nested);
            $this->assertNotContains('Ringkasan', $nested->map->getLabel()->all());
            $this->get(route($dashboardRoute))->assertOk()
                ->assertSee('href="'.route($dashboardRoute).'" class="fi-sidebar-group-label simaster-sidebar-group-link simaster-module-trigger"', false)
                ->assertSee('aria-label="Buka grafik '.e($label).'"', false);
            foreach ($expected as $module) {
                $this->assertTrue($nested->contains(fn ($item) => $item->getUrl() === route($module[2], $module[5] ?? [])));
            }
            if ($label === 'Infrastruktur Management') {
                $sewa = collect($items)->first(fn ($item) => $item->getLabel() === 'Sewa Lahan');
                $this->assertEqualsCanonicalizing(['Site TP', 'Site Telkomsel'], collect($sewa->getChildItems())->map->getLabel()->all());
            } elseif ($label === 'Electricity Monitoring') {
                $this->assertEqualsCanonicalizing(['Listrik Centralized', 'Listrik Inbuilding'], collect($items)->map->getLabel()->all());
                foreach ($items as $scope) {
                    $this->assertCount(5, $scope->getChildItems());
                }
            }
        }
    }

    public function test_approval_link_is_in_the_admin_account_menu_only(): void
    {
        $this->actingAs($this->userWithRole('viewer'))->get(route('dashboard'))->assertOk()
            ->assertDontSee('Persetujuan Akun');
        $this->actingAs($this->userWithRole('admin'))->get(route('dashboard'))->assertOk()
            ->assertSee('Persetujuan Akun')->assertSee('fi-topbar-database-notifications-btn', false);
        $menu = Filament::getUserMenuItems();
        $this->assertSame(AccountApprovals::getUrl(), $menu['accountApprovals']->getUrl());
        $labels = collect(Filament::getNavigation())->flatMap(fn ($group) => collect($group->getItems())->map(fn ($item) => $item->getLabel()));
        $this->assertNotContains('Persetujuan Akun', $labels);
        $this->assertNotContains('Ringkasan Laporan', $labels);
    }

    public function test_profit_loss_has_its_own_module_and_livewire_component(): void
    {
        $this->actingAs($this->userWithRole('viewer'))->get(route('pnl.index'))->assertOk()
            ->assertSee('simaster.modules.profit-loss.pages.profit-loss-page', false)
            ->assertSee('assets/modules/profit-loss.svg', false);
        $groups = collect(Filament::getNavigation())->keyBy->getLabel();
        $this->assertSame(['Dashboard Utama'], collect($groups['Dashboard']->getItems())->map->getLabel()->all());
        $this->assertSame(['Analisis Profit & Loss'], collect($groups['Profit & Loss']->getItems())->map->getLabel()->all());
        $this->assertSame(['Dashboard', 'Profit & Loss', 'Infrastruktur Management', 'Electricity Monitoring', 'PO Monitoring', 'Equipment', 'Feasibility Site'], $groups->keys()->all());
    }

    public function test_header_notifications_read_existing_data_and_preserve_account_isolation(): void
    {
        $user = $this->userWithRole('viewer');
        $other = $this->userWithRole('viewer');
        $create = fn (User $owner, string $title) => $owner->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'infrastructure-alert',
            'data' => ['title' => $title, 'message' => 'Peringatan masa sewa', 'url' => 'https://old-installation.ngrok-free.app/infrastruktur/sewa-lahan'],
        ]);
        $own = $create($user, 'Notifikasi akun aktif');
        $foreign = $create($other, 'Notifikasi rahasia akun lain');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('report'));
        Livewire::test(ReportNotifications::class)
            ->assertSee('Notifikasi akun aktif')->assertSee('Peringatan masa sewa')
            ->assertDontSee('Notifikasi rahasia akun lain')->assertDontSee('old-installation.ngrok-free.app')
            ->assertSee(route('notifications.read', $own))
            ->assertSee('simaster-notification-card-link')
            ->assertDontSee('Lihat data')
            ->assertDontSee('Download Excel')
            ->call('markNotificationAsRead', $foreign->id)
            ->call('markAllNotificationsAsRead');
        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
        $own->update(['read_at' => null]);
        Livewire::test(ReportNotifications::class)->call('removeNotification', $own->id);
        $this->assertNotNull($own->fresh()->read_at);
    }

    public function test_bound_edit_records_and_admin_restrictions_are_preserved(): void
    {
        $admin = $this->userWithRole('admin');
        $po = PoHq::create(['po_number' => 'FILAMENT-PO-001', 'vendor_name' => 'QA Vendor', 'status' => 'Draft']);
        $this->actingAs($admin)->get(route('po-hq.edit', $po))->assertOk()
            ->assertSee('FILAMENT-PO-001')->assertSee('fi-sidebar', false);
        $this->actingAs($this->userWithRole('viewer'))->get(route('po-hq.edit', $po))->assertForbidden();
    }

    public function test_json_endpoints_remain_json_and_do_not_render_a_panel(): void
    {
        $this->actingAs($this->userWithRole('viewer'));
        $this->getJson(route('infrastruktur.dashboard.data'))->assertOk()
            ->assertHeader('Content-Type', 'application/json')->assertDontSee('wire:snapshot');
        $this->getJson(route('data-potensi.search-all-resource.data'))->assertOk()
            ->assertHeader('Content-Type', 'application/json')->assertDontSee('fi-sidebar');
    }

    public function test_only_an_admin_can_open_native_account_approvals(): void
    {
        $this->actingAs($this->userWithRole('viewer'))->get('/report/account-approvals')->assertForbidden();
        $this->actingAs($this->userWithRole('admin'))->get('/report/account-approvals')->assertOk()
            ->assertSee('Persetujuan Akun');
    }

    public function test_native_approval_actions_write_the_same_account_and_role_data(): void
    {
        $admin = $this->userWithRole('admin');
        $this->userWithRole('viewer');
        $pending = User::factory()->create(['account_status' => 'pending', 'requested_role' => 'viewer']);
        $reject = User::factory()->create(['account_status' => 'pending', 'requested_role' => 'viewer']);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('report'));

        Livewire::test(AccountApprovals::class)
            ->callAction(TestAction::make('approve')->table($pending))
            ->assertHasNoActionErrors();
        $this->assertSame('approved', $pending->fresh()->account_status);
        $this->assertSame($admin->id, $pending->fresh()->approved_by);
        $this->assertTrue($pending->fresh()->hasRole('viewer'));
        Livewire::test(AccountApprovals::class)
            ->callAction(TestAction::make('reject')->table($reject))
            ->assertHasNoActionErrors();
        $this->assertSame('rejected', $reject->fresh()->account_status);
        $this->assertTrue($reject->fresh()->getRoleNames()->isEmpty());
    }

    public function test_operational_page_can_refresh_with_bound_records_and_period_filters(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $po = PoHq::create(['po_number' => 'REFRESH-PO-001', 'vendor_name' => 'QA Vendor', 'status' => 'Draft']);
        $snapshot = $this->reportSnapshot(route('po-hq.edit', $po));
        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $this->refreshPayload($snapshot))
            ->assertOk()->assertSee('REFRESH-PO-001');

        $siteId = \Illuminate\Support\Facades\DB::table('sites')->insertGetId(['site_id' => 'RPT001', 'site_name' => 'Period QA']);
        foreach ([1, 2] as $month) {
            \Illuminate\Support\Facades\DB::table('site_monthly_metrics')->insert([
                'site_id' => $siteId, 'bulan' => $month, 'tahun' => 2026,
                'revenue' => 100, 'cost' => 50, 'profit_loss' => 50, 'is_anomaly' => false,
            ]);
        }
        $snapshot = $this->reportSnapshot(route('pnl.index', ['bulan' => 1, 'tahun' => 2026]));
        $refresh = $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $this->refreshPayload($snapshot))->assertOk();
        $html = $refresh->json('components.0.effects.html');
        $this->assertStringContainsString('const initialPeriod = "1-2026".split(\'-\').map(Number);', $html);
        $this->assertStringContainsString('const initialAllMonths = false;', $html);
    }

    public function test_role_revocation_applies_to_livewire_requests_from_an_admin_form(): void
    {
        $admin = $this->userWithRole('admin');
        $this->userWithRole('viewer');
        $this->actingAs($admin);
        $snapshot = $this->reportSnapshot(route('po-hq.create'));
        $admin->syncRoles(['viewer']);
        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $this->refreshPayload($snapshot))->assertForbidden();
    }

    public function test_client_cannot_change_the_operational_controller_route(): void
    {
        $this->actingAs($this->userWithRole('viewer'));
        $snapshot = $this->reportSnapshot(route('infrastruktur.index'));
        $payload = $this->refreshPayload($snapshot);
        $payload['components'][0]['updates'] = ['sourceRoute' => 'admin.user-approvals.approve'];
        $this->withoutExceptionHandling();
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $payload);
    }

    public function test_dashboard_scope_rechecks_access_when_an_old_livewire_page_refreshes(): void
    {
        $user = $this->userWithRole('viewer');
        $this->actingAs($user);
        $pnl = $this->reportSnapshot(route('pnl.index'));
        $electricity = $this->reportSnapshot(route('electricity.dashboard'));

        $user->syncRoles(['viewer_electricity']);

        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $this->refreshPayload($pnl))
            ->assertForbidden();
        $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $this->refreshPayload($electricity))
            ->assertOk();
    }

    public function test_each_module_registers_its_pages_on_the_existing_panel(): void
    {
        $panel = Filament::getPanel('report');
        $this->assertSame([
            'simaster-dashboard', 'simaster-profit-loss', 'simaster-infrastructure', 'simaster-electricity',
            'simaster-po-monitoring', 'simaster-equipment-relocation',
            'simaster-accounts', 'simaster-data-potensi',
        ], array_keys($panel->getPlugins()));
        $aliases = [];
        foreach (ReportModuleRegistry::pages() as $page) {
            $this->assertContains($page, $panel->getPages());
            $aliases[] = $page::reportComponentName();
        }
        $this->assertCount(count($aliases), array_unique($aliases));
        $this->assertContains(AccountApprovals::class, $panel->getPages());
    }

    public function test_every_existing_controller_view_route_has_a_module_page(): void
    {
        foreach (app('router')->getRoutes() as $route) {
            if (! ReportModules::isReportRoute($route) || in_array($route->getName(), ['infrastruktur.upload', 'operations.create'], true)) {
                continue;
            }
            $this->assertNotNull(ReportModuleRegistry::pageForRoute($route), $route->getName());
        }
    }

    public function test_operational_entry_forms_keep_their_source_module_page_on_livewire_refresh(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        foreach (\App\Support\OperationalDatasets::DEFINITIONS as $dataset => $definition) {
            $snapshot = $this->reportSnapshot(route('operations.create', $dataset));
            $decoded = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);
            $source = app('router')->getRoutes()->getByName($definition[2]);
            $page = ReportModuleRegistry::pageForRoute($source);
            $this->assertSame($page::reportComponentName(), $decoded['memo']['name']);
            $this->withHeader('X-Livewire', 'true')->postJson(Livewire::getUpdateUri(), $this->refreshPayload($snapshot))
                ->assertOk()->assertJsonStructure(['components' => [['effects' => ['html']]]]);
        }
    }

    public function test_infrastructure_uploads_keep_their_module_page_on_livewire_refresh(): void
    {
        $this->actingAs($this->userWithRole('admin'));
        $pages = [
            'sewa-lahan' => \App\Filament\Modules\Infrastructure\Pages\SewaLahanPage::class,
            'combat' => \App\Filament\Modules\Infrastructure\Pages\CombatPage::class,
            'recurring-ipas' => \App\Filament\Modules\Infrastructure\Pages\RecurringIpasPage::class,
            'recurring-tagihan-ipas' => \App\Filament\Modules\Infrastructure\Pages\RecurringTagihanIpasPage::class,
            'jaknet' => \App\Filament\Modules\Infrastructure\Pages\JaknetPage::class,
            'site-unlock' => \App\Filament\Modules\Infrastructure\Pages\SiteUnlockPage::class,
            'bapss' => \App\Filament\Modules\Infrastructure\Pages\BapssPage::class,
        ];
        foreach ($pages as $dataset => $page) {
            $snapshot = $this->reportSnapshot(route('infrastruktur.upload', ['dataset' => $dataset]));
            $decoded = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($page::reportComponentName(), $decoded['memo']['name']);
            $response = $this->withHeader('X-Livewire', 'true')
                ->postJson(Livewire::getUpdateUri(), $this->refreshPayload($snapshot))->assertOk();
            $this->assertStringContainsString(
                route('infrastruktur.upload.store', ['dataset' => $dataset]),
                $response->json('components.0.effects.html'),
            );
        }
    }
    private function reportSnapshot(string $url): string
    {
        $response = $this->get($url)->assertOk();
        preg_match_all('/wire:snapshot="([^"]+)"/', $response->getContent(), $matches);
        foreach ($matches[1] as $encoded) {
            $snapshot = html_entity_decode($encoded, ENT_QUOTES);
            $decoded = json_decode($snapshot, true, flags: JSON_THROW_ON_ERROR);
            if (($decoded['memo']['name'] ?? '') === 'simaster.operational-report'
                || str_starts_with($decoded['memo']['name'] ?? '', 'simaster.modules.')) {
                return $snapshot;
            }
        }
        $this->fail('Operational Filament page snapshot was not rendered.');
    }

    private function refreshPayload(string $snapshot): array
    {
        return ['components' => [[
            'snapshot' => $snapshot, 'updates' => [],
            'calls' => [['path' => '', 'method' => 'refresh', 'params' => []]],
        ]]];
    }
}
