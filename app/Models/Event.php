<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'match_format_id',
        'slug',
        'title',
        'summary',
        'description',
        'banner_path',
        'start_date',
        'start_time',
        'end_date',
        'location_name',
        'location_address',
        'location_lat',
        'location_lng',
        'price_cents',
        'member_price_cents',
        'non_member_price_cents',
        'junior_price_cents',
        'is_saprf_match',
        'saprf_url',
        'max_entries',
        'round_count',
        'club_round_count',
        'registration_division_options',
        'registration_category_options',
        'registration_require_division',
        'registration_require_category',
        'registrations_open',
        'registrations_close_at',
        'status',
        'match_director_id',
        'match_director_name',
        'created_by_user_id',
        'published_at',
        'results_published_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'registrations_close_at' => 'datetime',
        'published_at' => 'datetime',
        'results_published_at' => 'datetime',
        'registrations_open' => 'boolean',
        'status' => EventStatus::class,
        'price_cents' => 'integer',
        'member_price_cents' => 'integer',
        'non_member_price_cents' => 'integer',
        'junior_price_cents' => 'integer',
        'is_saprf_match' => 'boolean',
        'max_entries' => 'integer',
        'round_count' => 'integer',
        'club_round_count' => 'integer',
        'registration_division_options' => 'array',
        'registration_category_options' => 'array',
        'registration_require_division' => 'boolean',
        'registration_require_category' => 'boolean',
        'location_lat' => 'float',
        'location_lng' => 'float',
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = self::uniqueSlugFrom($event->title);
            }
        });

        // Public listings use scopePublished(), which requires published_at.
        // Filament often sets status to Published via the form without touching
        // this timestamp — ensure it is never missing for published/completed.
        static::saving(function (Event $event) {
            $status = $event->status instanceof EventStatus
                ? $event->status
                : EventStatus::tryFrom((string) $event->status);

            if ($status === null) {
                return;
            }

            if (in_array($status, [EventStatus::Published, EventStatus::Completed], true)
                && $event->published_at === null) {
                $event->published_at = now();
            }
        });
    }

    /**
     * Label shown in admin and on the public match page. Prefer the free-text
     * field; fall back to the linked user account when present (legacy data).
     */
    public function matchDirectorDisplay(): string
    {
        $n = trim((string) ($this->match_director_name ?? ''));

        if ($n !== '') {
            return $n;
        }

        return $this->matchDirector?->name ?? '';
    }

    public function isPubliclyVisible(): bool
    {
        if ($this->published_at === null) {
            return false;
        }

        $status = $this->status instanceof EventStatus
            ? $this->status
            : EventStatus::tryFrom((string) $this->status);

        return $status !== null
            && in_array($status, [EventStatus::Published, EventStatus::Completed], true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function matchFormat(): BelongsTo
    {
        return $this->belongsTo(MatchFormat::class);
    }

    public function matchDirector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'match_director_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(EventResult::class);
    }

    public function galleryPhotos(): HasMany
    {
        return $this->hasMany(EventGalleryPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [EventStatus::Published->value, EventStatus::Completed->value])
            ->whereNotNull('published_at');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->published()
            ->where('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->orderBy('start_time');
    }

    public function scopeWithResults(Builder $query): Builder
    {
        return $query->whereNotNull('results_published_at')
            ->orderByDesc('start_date');
    }

    public function priceInRands(): ?float
    {
        $cents = $this->member_price_cents ?? $this->price_cents;

        return $cents !== null ? $cents / 100 : null;
    }

    public function memberPriceCents(): ?int
    {
        return $this->member_price_cents ?? $this->price_cents;
    }

    public function nonMemberPriceCents(): ?int
    {
        return $this->non_member_price_cents ?? $this->price_cents;
    }

    /**
     * Junior shooters fall back to the member price when no junior-specific
     * price is set, so adding the field is opt-in per match.
     */
    public function juniorPriceCents(): ?int
    {
        return $this->junior_price_cents ?? $this->memberPriceCents();
    }

    /**
     * Public S3 URL for the banner, if one is attached.
     */
    public function bannerUrl(): ?string
    {
        if (empty($this->banner_path)) {
            return null;
        }

        return Storage::disk(\App\Support\MediaDisk::name())->url($this->banner_path);
    }

    /**
     * Resolve the fee (in cents) this member would pay for this event.
     * Precedence:
     *   1. ExCo / committee members -> 0 (2026 AGM rule).
     *   2. Active PPRC junior -> junior_price_cents (falls back to member).
     *   3. Active PPRC member -> member_price_cents.
     *   4. Everyone else (guest, expired, pending, suspended) -> non_member_price_cents.
     * Falls back to the legacy price_cents when the tiered fields are unset.
     * Returns null when the match has no price configured at all.
     */
    public function effectivePriceCentsFor(?Member $member): ?int
    {
        if ($member?->user?->hasFreeEventEntry()) {
            return 0;
        }

        $isActiveMember = $member?->status?->value === 'active';

        if ($isActiveMember && $member?->isJunior()) {
            return $this->juniorPriceCents();
        }

        return $isActiveMember ? $this->memberPriceCents() : $this->nonMemberPriceCents();
    }

    public function isRegistrationOpen(): bool
    {
        if (! $this->registrations_open) {
            return false;
        }

        if ($this->registrations_close_at && $this->registrations_close_at->isPast()) {
            return false;
        }

        if ($this->max_entries !== null && $this->registrations()->count() >= $this->max_entries) {
            return false;
        }

        return true;
    }

    /**
     * Division labels offered on the public registration form (SAPRF-style
     * defaults unless the match director sets a custom list, e.g. "Club Open").
     *
     * @return list<string>
     */
    public function registrationDivisionChoices(): array
    {
        $opts = $this->registration_division_options;
        if (is_array($opts) && $opts !== []) {
            return array_values(array_filter(array_map(fn ($v) => (string) $v, $opts), fn (string $v) => $v !== ''));
        }

        return config('saprf_registration.equipment_divisions', []);
    }

    /**
     * Category labels (Open sub-tracks, etc.) unless overridden per event.
     *
     * @return list<string>
     */
    public function registrationCategoryChoices(): array
    {
        $opts = $this->registration_category_options;
        if (is_array($opts) && $opts !== []) {
            return array_values(array_filter(array_map(fn ($v) => (string) $v, $opts), fn (string $v) => $v !== ''));
        }

        return config('saprf_registration.registration_categories', []);
    }

    public function collectsDivisionAtRegistration(): bool
    {
        return (bool) $this->registration_require_division;
    }

    public function collectsCategoryAtRegistration(): bool
    {
        return (bool) $this->registration_require_category;
    }

    private static function uniqueSlugFrom(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'match';
        }

        $slug = $base;
        $i = 2;
        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
