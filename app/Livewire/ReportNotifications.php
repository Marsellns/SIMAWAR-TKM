<?php

namespace App\Livewire;

use Filament\Actions\Action;
use Filament\Livewire\DatabaseNotifications;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Notifications\DatabaseNotification as StoredNotification;

/** Display existing operational alerts in Filament without rewriting their data. */
class ReportNotifications extends DatabaseNotifications
{
    public function getNotificationsQuery(): Builder | Relation
    {
        $user = $this->getUser();
        abort_unless($user?->account_status === 'approved' && $user->dashboardViewerRole() === null, 403);

        return $user->notifications()->latest();
    }

    public function getNotification(StoredNotification $notification): Notification
    {
        $data = $notification->data;

        return Notification::make($notification->getKey())
            ->title(e($data['title'] ?? 'Notifikasi'))
            ->body(e($data['message'] ?? $data['body'] ?? ''))
            ->icon('heroicon-o-bell')
            ->date($this->formatNotificationDate($notification->created_at));
    }

    public function getTrigger(): ?View
    {
        return view('filament.partials.notifications-trigger');
    }

    public function clearNotificationsAction(): Action
    {
        return Action::make('allNotifications')->label('Lihat semua notifikasi')
            ->url(route('notifications.index'))->link();
    }

    public function markAllNotificationsAsReadAction(): Action
    {
        return parent::markAllNotificationsAsReadAction()->label('Tandai semua sudah dibaca');
    }

    public function removeNotification(string $id): void
    {
        $this->markNotificationAsRead($id);
    }

    public function render(): View
    {
        return view('filament.partials.database-notifications');
    }
}
