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
        'max_entries',
        'round_count',
        'registrations_open',
        'registrations_close_at',
        'status',
        'match_director_id',
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
        'max_entries' => 'integer',
        'round_count' => 'integer',
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
     * Public S3 URL for the banner, if one is attached.
     */
    public function bannerUrl(): ?string
    {
        if (empty($this->banner_path)) {
            return null;
        }

        return Storage::disk('s3')->url($this->banner_path);
    }

    /**
     * Resolve the fee (in cents) this member would pay for this event.
     * Precedence:
     *   1. ExCo / committee members -> 0 (2026 AGM rule).
     *   2. Active PPRC member -> member_price_cents.
     *   3. Everyone else (guest, expired, pending, suspended) -> non_member_price_cents.
     * Falls back to the legacy price_cents when the tiered fields are unset.
     * Returns null when the match has no price configured at all.
     */
    public function effectivePriceCentsFor(?Member $member): ?int
    {
        if ($member?->user?->hasFreeEventEntry()) {
            return 0;
        }

        $isActiveMember = $member?->status?->value === 'active';

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
