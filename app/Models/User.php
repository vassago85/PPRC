<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Any committee role can access the Filament admin panel. Fine-grained
     * gating is done by Spatie permissions on individual resources/actions.
     */
    public const COMMITTEE_ROLES = [
        'developer', 'chairperson', 'treasurer', 'secretary',
        'membership_secretary', 'admin',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(self::COMMITTEE_ROLES) && $this->hasVerifiedEmail();
    }

    public function isDeveloper(): bool
    {
        return $this->hasRole('developer');
    }

    public function isChairperson(): bool
    {
        return $this->hasRole('chairperson');
    }

    public function isCommittee(): bool
    {
        return $this->hasAnyRole(self::COMMITTEE_ROLES);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['developer', 'chairperson', 'admin']);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }
}
