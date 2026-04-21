<?php

namespace App\Models;

use App\Enums\EventRegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'member_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'squad_number',
        'firing_order',
        'status',
        'attended',
        'notes',
        'registered_at',
        'checked_in_by_user_id',
        'checked_in_at',
    ];

    protected $casts = [
        'attended' => 'boolean',
        'registered_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'status' => EventRegistrationStatus::class,
        'squad_number' => 'integer',
        'firing_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by_user_id');
    }

    public function shooterName(): string
    {
        if ($this->member) {
            return $this->member->fullName();
        }

        return trim((string) $this->guest_name) ?: 'Guest';
    }
}
