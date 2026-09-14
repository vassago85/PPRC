<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item the club has to pay for a specific match — separate from what
 * the club owes the match director. Typical examples: the range's per-shooter
 * fee, prize money bought on the day, catering the club is footing.
 */
class MatchExpense extends Model
{
    protected $fillable = [
        'event_id',
        'description',
        'payee_name',
        'amount_cents',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
