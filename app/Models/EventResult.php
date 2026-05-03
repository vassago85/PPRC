<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventResult extends Model
{
    protected $fillable = [
        'event_id',
        'event_registration_id',
        'member_id',
        'shooter_name',
        'division',
        'category',
        'rank',
        'score_hits',
        'score_possible',
        'score_points',
        'score_percentage',
        'score_time_ms',
        'dnf',
        'dq',
        'notes',
    ];

    protected $casts = [
        'rank' => 'integer',
        'score_hits' => 'integer',
        'score_possible' => 'integer',
        'score_points' => 'integer',
        'score_percentage' => 'decimal:2',
        'score_time_ms' => 'integer',
        'dnf' => 'boolean',
        'dq' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    public function displayScore(): string
    {
        if ($this->dq) return 'DQ';
        if ($this->dnf) return 'DNF';

        if ($this->score_hits !== null && $this->score_possible !== null) {
            return "{$this->score_hits} / {$this->score_possible}";
        }
        if ($this->score_points !== null) {
            return (string) $this->score_points;
        }
        if ($this->score_percentage !== null) {
            return number_format((float) $this->score_percentage, 2).'%';
        }

        return '—';
    }
}
