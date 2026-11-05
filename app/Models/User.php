<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\DashboardViewerRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'account_status', 'requested_role', 'approved_by', 'approved_at'];

    protected $hidden = ['password', 'remember_token'];

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'report' && $this->account_status === 'approved';
    }

    public function dashboardViewerRole(): ?DashboardViewerRole
    {
        $roles = $this->getRoleNames();
        foreach (DashboardViewerRole::cases() as $role) {
            if ($roles->contains($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function dashboardRoute(): string
    {
        return $this->dashboardViewerRole()?->dashboardRoute() ?? 'dashboard';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
        ];
    }

    public function approver()
    {
        return $this->belongsTo(self::class, 'approved_by');
    }
}
