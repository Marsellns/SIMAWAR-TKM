<?php

namespace Tests\Feature;

use App\Livewire\ReportNotifications;
use App\Models\User;
use App\Notifications\ElectricityAnomalyNotification;
use App\Notifications\SiteLossNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\TestCase;

class ElectricityViewerNotificationsTest extends TestCase
{
    use CreatesUsersWithRoles, RefreshDatabase;

    public function test_electricity_viewer_sees_only_their_electricity_alerts_in_the_inbox_and_bell(): void
    {
        $user = $this->userWithRole('viewer_electricity');
        $own = $this->electricityAlert($user, 'Centralized PLN');
        $user->notify(new SiteLossNotification(2, 9, 2026));
        $foreign = $this->electricityAlert($this->userWithRole('viewer_electricity'), 'Inbuilding');
        $this->actingAs($user);

        $this->get(route('electricity.dashboard'))->assertOk()
            ->assertSee('fi-topbar-database-notifications-btn', false);
        $this->get(route('notifications.index'))->assertOk()
            ->assertSee('Peringatan anomali tagihan listrik')
            ->assertSee(route('notifications.read', $own))
            ->assertDontSee('Peringatan site Loss')
            ->assertDontSee(route('notifications.read', $foreign))
            ->assertViewHas('notifications', fn ($notifications) => $notifications->total() === 1);

        Filament::setCurrentPanel(Filament::getPanel('report'));
        $component = Livewire::test(ReportNotifications::class)
            ->assertSee('Centralized PLN')
            ->assertDontSee('Peringatan site Loss')
            ->assertDontSee('Inbuilding')
            ->assertSee('Lihat semua notifikasi');
        $this->assertSame(1, $component->instance()->getUnreadNotificationsCount());
        $this->assertSame('30s', $component->instance()->getPollingInterval());
    }

    public static function electricitySources(): array
    {
        return [
            'centralized' => ['Centralized PLN', 'electricity.centralized.anomali.index'],
            'inbuilding' => ['Inbuilding', 'electricity.inbuilding.anomali.index'],
        ];
    }

    #[DataProvider('electricitySources')]
    public function test_electricity_viewer_can_read_alerts_and_open_their_anomaly_report(string $source, string $report): void
    {
        $user = $this->userWithRole('viewer_electricity');
        $notification = $this->electricityAlert($user, $source);
        $this->actingAs($user)->get(route('notifications.read', $notification))->assertRedirect(route($report));
        $this->assertNotNull($notification->fresh()->read_at);
        $this->get(route($report))->assertOk();
    }

    public function test_electricity_viewer_cannot_read_hidden_alerts_or_another_users_alerts(): void
    {
        $user = $this->userWithRole('viewer_electricity');
        $user->notify(new SiteLossNotification(2, 9, 2026));
        $hidden = $user->notifications()->firstOrFail();
        $foreign = $this->electricityAlert($this->userWithRole('viewer_electricity'), 'Centralized PLN');
        $this->actingAs($user);

        foreach ([$hidden, $foreign] as $notification) {
            $this->get(route('notifications.read', $notification))->assertNotFound();
            $this->assertNull($notification->fresh()->read_at);
        }
        $this->get(route('notifications.site-loss'))->assertForbidden();
        $this->get(route('notifications.site-loss.export'))->assertForbidden();
    }

    public function test_mark_all_read_only_changes_visible_alerts_and_does_not_grant_data_write_access(): void
    {
        $user = $this->userWithRole('viewer_electricity');
        $first = $this->electricityAlert($user, 'Centralized PLN');
        $second = $this->electricityAlert($user, 'Inbuilding');
        $user->notify(new SiteLossNotification(2, 9, 2026));
        $hidden = $user->notifications()->where('type', SiteLossNotification::class)->firstOrFail();
        $foreign = $this->electricityAlert($this->userWithRole('viewer_electricity'), 'Centralized PLN');
        $this->actingAs($user)->from(route('notifications.index'))
            ->post(route('notifications.read-all'))->assertRedirect(route('notifications.index'));

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNotNull($second->fresh()->read_at);
        $this->assertNull($hidden->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
        $this->post('/electricity/inbuilding/input-tagihan-ibc')->assertForbidden();
        $this->delete(route('notifications.read-all'))->assertMethodNotAllowed();
    }

    public function test_livewire_notification_actions_keep_the_same_user_and_module_scope(): void
    {
        $user = $this->userWithRole('viewer_electricity');
        $own = $this->electricityAlert($user, 'Centralized PLN');
        $user->notify(new SiteLossNotification(2, 9, 2026));
        $hidden = $user->notifications()->where('type', SiteLossNotification::class)->firstOrFail();
        $foreign = $this->electricityAlert($this->userWithRole('viewer_electricity'), 'Inbuilding');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('report'));

        $component = Livewire::test(ReportNotifications::class)
            ->call('markNotificationAsRead', $hidden->id)
            ->call('markNotificationAsRead', $foreign->id)
            ->call('markAllNotificationsAsRead');
        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($hidden->fresh()->read_at);
        $this->assertNull($foreign->fresh()->read_at);
        $this->assertSame(0, $component->instance()->getUnreadNotificationsCount());

        $component->call('markNotificationAsUnread', $own->id)->call('removeNotification', $own->id);
        $this->assertNotNull($own->fresh()->read_at);
        $user->syncRoles(['viewer_po_monitoring']);
        $component->call('$refresh')->assertForbidden();
    }

    private function electricityAlert(User $user, string $source): DatabaseNotification
    {
        $prefix = $source === 'Centralized PLN' ? 'centralized' : 'inbuilding';
        $user->notify(new ElectricityAnomalyNotification(
            $source, 3, route("electricity.{$prefix}.anomali.index"),
            route("electricity.{$prefix}.anomali.export-excel"),
        ));

        return $user->notifications()->where('type', ElectricityAnomalyNotification::class)
            ->where('data->source', $source)->firstOrFail();
    }
}
