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
     *
     * Order matches the 2026 AGM minutes (elected ExCo positions first, then
     * operational roles) so the list is easy to cross-check against a seat.
     */
    public const COMMITTEE_ROLES = [
        'developer',
        'chairperson',
        'vice_chair',
        'treasurer',
        'secretary',
        'marketing',
        'club_captain',
        'membership_secretary',
        'match_director',
        'admin',
    ];

    /**
     * Roles that qualify for free entry at PPRC-hosted events. Per the 2026
     * AGM, ExCo and other committee / operational position holders don't pay
     * for club events. Developer is excluded because it's a technical account
     * (Charsley Digital), not a shooting member.
     */
    public const FREE_EVENT_ENTRY_ROLES = [
        'chairperson',
        'vice_chair',
        'treasurer',
        'secretary',
        'marketing',
        'club_captain',
        'membership_secretary',
        'match_director',
        'admin',
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
        return $this->hasAnyRole(['developer', 'chairperson', 'vice_chair', 'admin']);
    }

    /**
     * Whether this user's event entries should be waived (fee = 0) automatically.
     * See FREE_EVENT_ENTRY_ROLES for the role list and rationale.
     */
    public function hasFreeEventEntry(): bool
    {
        return $this->hasAnyRole(self::FREE_EVENT_ENTRY_ROLES);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }
}
