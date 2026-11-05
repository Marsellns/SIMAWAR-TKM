<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ElectricityAnomalyNotification;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesUsersWithRoles;
use Tests\Concerns\ReadsEmailAttachments;
use Tests\TestCase;

class ScheduledNotificationsTest extends TestCase
{
    use CreatesUsersWithRoles, ReadsEmailAttachments, RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        Cache::flush();
        parent::tearDown();
    }

    public function test_schedule_is_due_at_eight_and_seventeen_jakarta_time(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(
            fn ($event) => str_contains($event->command ?? '', 'notifications:send-scheduled')
        );
        $this->assertCount(2, $events);
        foreach (['08:00:00' => 1, '16:59:00' => 0, '17:00:00' => 1] as $time => $count) {
            CarbonImmutable::setTestNow('2026-10-02 '.$time.' Asia/Jakarta');
            $this->assertCount($count, $events->filter(fn ($event) => $event->isDue(app())));
        }
    }

    public function test_site_loss_and_electricity_send_both_channels_twice_without_duplicates(): void
    {
        Cache::flush();
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'simaster@example.test');
        config()->set('mail.site_loss_alert_to', 'loss@example.test');
        config()->set('mail.electricity_alert_to', 'electricity@example.test');
        app('mail.manager')->purge();
        $user = User::factory()->create(['account_status' => 'approved']);
        $electricityViewer = $this->userWithRole('viewer_electricity');
        $site = DB::table('sites')->insertGetId(['site_id' => 'LOSS-1', 'site_name' => 'Site Loss']);
        DB::table('site_monthly_metrics')->insert([
            'site_id' => $site, 'bulan' => 8, 'tahun' => 2026,
            'revenue' => 100, 'cost' => 200, 'profit_loss' => -100,
        ]);
        DB::table('anomali_tagihan_pln')->insert([
            'id_pelanggan' => 'ACCOUNT-1', 'site_id' => 'LOSS-1', 'site_name' => 'Site Loss',
            'bulan' => 8, 'tahun' => 2026, 'kenaikan_persen' => 75,
        ]);
        DB::table('anomali_tagihan_pln')->insert([
            'id_pelanggan' => 'ACCOUNT-NORMAL', 'site_id' => 'PLN-NORMAL',
            'bulan' => 8, 'tahun' => 2026, 'kenaikan_persen' => 50,
        ]);
        DB::table('anomali_tagihan_inbuilding')->insert([
            ['site_id' => 'IBC-2025', 'periode_saat_ini' => '2025-12', 'kenaikan_persen' => 75],
            ['site_id' => 'IBC-2026', 'periode_saat_ini' => '2026-08', 'kenaikan_persen' => 80],
            ['site_id' => 'IBC-NORMAL', 'periode_saat_ini' => '2026-08', 'kenaikan_persen' => 50],
        ]);
        $safeSite = DB::table('sites')->insertGetId(['site_id' => 'SAFE-1']);
        DB::table('site_monthly_metrics')->insert([
            ['site_id' => $safeSite, 'bulan' => 8, 'tahun' => 2026, 'revenue' => 200, 'cost' => 100, 'profit_loss' => 100],
            ['site_id' => $safeSite, 'bulan' => 7, 'tahun' => 2026, 'revenue' => 100, 'cost' => 200, 'profit_loss' => -100],
        ]);

        CarbonImmutable::setTestNow('2026-10-02 08:00:00 Asia/Jakarta');
        $this->artisan('notifications:send-scheduled')->assertSuccessful();
        $transport = Mail::mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);
        $this->assertCount(3, $transport->messages());
        $this->assertSame(3, $user->fresh()->notifications()->count());
        $this->assertSame(2, $electricityViewer->visibleNotifications()->count());
        $this->assertSame([ElectricityAnomalyNotification::class], $electricityViewer->visibleNotifications()->pluck('type')->unique()->all());
        $this->artisan('notifications:send-scheduled')->assertSuccessful();
        $this->assertCount(3, $transport->messages());
        $this->assertSame(2, $electricityViewer->visibleNotifications()->count());

        CarbonImmutable::setTestNow('2026-10-02 17:00:00 Asia/Jakarta');
        $this->artisan('notifications:send-scheduled')->assertSuccessful();
        $this->assertCount(6, $transport->messages());
        $this->assertSame(6, $user->fresh()->notifications()->count());
        $this->assertSame(4, $electricityViewer->visibleNotifications()->count());
        $this->artisan('notifications:send-scheduled')->assertSuccessful();
        $this->assertCount(6, $transport->messages());
        $this->assertSame(4, $electricityViewer->visibleNotifications()->count());
        foreach ($transport->messages() as $message) {
            $email = $message->getOriginalMessage();
            $rows = $this->attachmentRows($email);
            $this->assertCount(1, $email->getTo());
            if (str_contains($email->getSubject(), 'site Loss')) {
                $this->assertSame('loss@example.test', $email->getTo()[0]->getAddress());
                $this->assertSame(['LOSS-1'], array_column($rows, 1));
                $this->assertSame(8, $rows[0][4]);
                $this->assertSame(2026, $rows[0][5]);
                $this->assertStringContainsString('1 site dengan status Loss pada periode 08/2026', $email->getTextBody());
                $this->assertStringContainsString(route('notifications.site-loss', ['bulan' => 8, 'tahun' => 2026]), $email->getTextBody());
            } else {
                $this->assertSame('electricity@example.test', $email->getTo()[0]->getAddress());
                $centralized = str_contains($email->getSubject(), 'Centralized PLN');
                $this->assertEqualsCanonicalizing(
                    $centralized ? ['LOSS-1'] : ['IBC-2025', 'IBC-2026'],
                    array_column($rows, $centralized ? 2 : 1),
                );
                $this->assertStringContainsString('Jumlah anomali: '.count($rows), $email->getTextBody());
                $this->assertStringContainsString('seluruh periode', $email->getTextBody());
                $this->assertStringContainsString(route($centralized
                    ? 'electricity.centralized.anomali.index'
                    : 'electricity.inbuilding.anomali.index'), $email->getTextBody());
                foreach ($rows as $row) {
                    $this->assertGreaterThan(50, $row[$centralized ? 9 : 7]);
                }
            }
        }
    }

    public function test_failed_electricity_email_can_retry_without_duplicating_bell_notifications(): void
    {
        CarbonImmutable::setTestNow('2026-10-02 08:00:00 Asia/Jakarta');
        config()->set('mail.default', 'array');
        config()->set('mail.from.address', 'simaster@example.test');
        config()->set('mail.electricity_alert_to', 'electricity@example.test');
        app('mail.manager')->purge();
        $mailManager = Mail::getFacadeRoot();
        $user = User::factory()->create(['account_status' => 'approved']);
        $anomaly = \App\Models\AnomaliTagihanPln::create([
            'id_pelanggan' => 'RETRY', 'site_id' => 'RETRY',
            'bulan' => 8, 'tahun' => 2026, 'kenaikan_persen' => 75,
        ]);
        $service = app(\App\Services\ElectricityAnomalyNotificationService::class);

        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('Simulated SMTP failure'));
        $service->send('Centralized PLN', [$anomaly->toArray()]);
        $this->assertSame(1, $user->notifications()->count());

        Mail::swap($mailManager);
        $this->assertTrue($service->send('Centralized PLN', [$anomaly->toArray()]));
        $service->send('Centralized PLN', [$anomaly->toArray()]);
        $this->assertCount(1, Mail::mailer()->getSymfonyTransport()->messages());
        $this->assertSame(1, $user->notifications()->count());
    }

    public function test_anomalous_financial_rows_do_not_generate_loss_alerts_or_exports(): void
    {
        config()->set('mail.site_loss_alert_to', 'loss@example.test');
        Mail::fake();
        $user = User::factory()->create(['account_status' => 'approved']);
        $site = DB::table('sites')->insertGetId(['site_id' => 'SENTINEL-1']);
        DB::table('site_monthly_metrics')->insert([
            'site_id' => $site, 'bulan' => 8, 'tahun' => 2026,
            'revenue' => 100, 'cost' => 2147483647, 'profit_loss' => -2147483547,
            'is_anomaly' => true,
        ]);
        CarbonImmutable::setTestNow('2026-10-02 08:00:00 Asia/Jakarta');
        $this->artisan('notifications:send-scheduled')->assertSuccessful();
        $this->assertSame(0, $user->fresh()->notifications()->count());
        Mail::assertNothingSent();
        $this->assertSame(0, (new \App\Exports\SiteLossExport(8, 2026))->query()->count());
        $this->actingAs($user)->get(route('notifications.site-loss', ['bulan' => 8, 'tahun' => 2026]))
            ->assertOk()->assertViewHas('sites', fn ($sites): bool => $sites->isEmpty());
    }
}
