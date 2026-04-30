<?php

namespace App\Models;

use App\Services\Auth\EmailVerificationPinService;
use App\Support\NameCase;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'created_via_import', 'email_verification_pin_hash', 'email_verification_pin_expires_at'])]
#[Hidden(['password', 'remember_token', 'email_verification_pin_hash'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_verification_pin_expires_at' => 'datetime',
            'created_via_import' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Normalise the user's display name on save (matches Member name mutators).
     */
    protected function name(): Attribute
    {
        return Attribute::set(fn ($value) => NameCase::normalize($value));
    }

    /**
     * Always store login emails lowercased so password reset tokens (keyed by
     * the email column verbatim) can be looked up reliably regardless of how
     * the user typed their address. Also matches Fortify's lowercase_usernames
     * setting for sign-in.
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn ($value) => $value === null ? null : strtolower(trim((string) $value)));
    }

    /**
     * PIN-based verification (replaces the default signed URL notification).
     */
    public function sendEmailVerificationNotification(): void
    {
        if ($this->hasVerifiedEmail()) {
            return;
        }

        if ($this->created_via_import) {
            return;
        }

        app(EmailVerificationPinService::class)->issueAndSend($this);
    }

    public function markEmailAsVerified(): bool
    {
        $saved = $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
            'email_verification_pin_hash' => null,
            'email_verification_pin_expires_at' => null,
        ])->save();

        if ($saved && $this->member) {
            app(\App\Services\Membership\MemberService::class)->markVerified($this->member);
        }

        return $saved;
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
     * Slug => human label for committee roles. Single source of truth used by
     * the ExCo roster select and any admin role-assignment UI.
     */
    public const COMMITTEE_ROLE_LABELS = [
        'developer' => 'Developer',
        'chairperson' => 'Chairperson',
        'vice_chair' => 'Vice Chair',
        'treasurer' => 'Treasurer',
        'secretary' => 'Secretary',
        'marketing' => 'Marketing',
        'club_captain' => 'Club Captain',
        'membership_secretary' => 'Membership Secretary',
        'match_director' => 'Match Director',
        'admin' => 'Admin',
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

    /**
     * Broad “committee admin” flag — any Filament committee role qualifies.
     * Fine-grained UI is still driven by Spatie permissions where it matters
     * (e.g. chairperson and vice_chair hold `settings.roles.assign`).
     */
    public function isAdmin(): bool
    {
        return $this->hasAnyRole(self::COMMITTEE_ROLES);
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

    public function shopOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }
}
