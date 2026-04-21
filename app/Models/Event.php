<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'start_date',
        'start_time',
        'end_date',
        'location_name',
        'location_address',
        'location_lat',
        'location_lng',
        'price_cents',
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
        return $this->price_cents !== null ? $this->price_cents / 100 : null;
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
