<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\DeleteUserAccount;
use App\Support\DashboardViewerRole;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class UserManagement extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.user-management';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->account_status === 'approved' && $user->hasRole('admin') && $user->dashboardViewerRole() === null;
    }

    public function getTitle(): string
    {
        return 'Kelola Pengguna';
    }

    public function table(Table $table): Table
    {
        $accessOptions = ['viewer' => 'Viewer (semua modul)', ...DashboardViewerRole::options()];

        return $table
            ->query(User::query()->with('roles'))
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email / Login ID')->searchable(),
                TextColumn::make('roles.name')->label('Role')->badge()->placeholder('Belum ada'),
                TextColumn::make('account_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Disetujui',
                        'pending' => 'Menunggu',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (User $record): string => match ($record->account_status) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('changeDashboardAccess')
                    ->label('Ubah akses dashboard')
                    ->icon('heroicon-o-key')
                    ->visible(fn (User $record): bool => static::canAccess() && $this->canChangeDashboardAccess($record))
                    ->schema([
                        Select::make('role')
                            ->label('Role akses')
                            ->options($accessOptions)
                            ->in(array_keys($accessOptions))
                            ->default(fn (User $record) => $record->dashboardViewerRole()?->value
                                ?? ($record->hasRole('viewer') ? 'viewer' : null))
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        abort_unless(static::canAccess(), 403);

                        DB::transaction(function () use ($record, $data): void {
                            $user = User::query()->lockForUpdate()->findOrFail($record->getKey());
                            abort_unless($this->canChangeDashboardAccess($user), 403);
                            $user->syncRoles([$data['role']]);
                        });

                        Notification::make()->title('Akses dashboard berhasil diperbarui')->success()->send();
                    }),
                Action::make('deleteUser')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (User $record): bool => static::canAccess() && $record->getKey() !== auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => 'Hapus pengguna '.$record->email.'?')
                    ->modalDescription('Akun ini akan dihapus permanen dan tidak dapat digunakan untuk masuk lagi.')
                    ->modalSubmitActionLabel('Ya, hapus pengguna')
                    ->action(function (User $record): void {
                        abort_unless(static::canAccess(), 403);

                        $deleted = app(DeleteUserAccount::class)->delete(auth()->user(), $record);

                        $notification = Notification::make()
                            ->title($deleted ? 'Pengguna berhasil dihapus' : 'Pengguna tidak dapat dihapus');

                        if ($deleted) {
                            $notification->success();
                        } else {
                            $notification->danger();
                        }

                        $notification->send();
                    }),
            ]);
    }

    private function canChangeDashboardAccess(User $user): bool
    {
        return $user->account_status === 'approved' && ! $user->hasRole('admin');
    }
}
