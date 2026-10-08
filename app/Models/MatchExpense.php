<?php

namespace App\Models;

use App\Enums\MatchExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line item the club has to pay for a specific match — separate from what
 * the club owes the match director. Typical examples: the range's per-shooter
 * fee, prize money bought on the day, catering the club is footing.
 *
 * The optional `payee_user_id` link lets us tie the expense back to the real
 * club member being reimbursed ("Dirk bought the trophies, pay him off the
 * top"). Free-text `payee_name` stays as the display label on the cash-up
 * slip either way, which keeps slips readable for outside suppliers that
 * don't have a user account.
 */
class MatchExpense extends Model
{
    protected $fillable = [
        'event_id',
        'description',
        'category',
        'payee_name',
        'payee_user_id',
        'amount_cents',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
        'category' => MatchExpenseCategory::class,
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function payeeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payee_user_id');
    }

    /**
     * Human-readable payee for the cash-up slip. Prefers the linked user's
     * stored name — users may edit their display name over time, so we want
     * the slip to reflect the current name instead of the snapshot captured
     * when the expense was logged. Falls back to the free-text label.
     */
    public function payeeDisplay(): string
    {
        if ($this->relationLoaded('payeeUser') && $this->payeeUser) {
            $name = trim((string) $this->payeeUser->name);

            if ($name !== '') {
                return $name;
            }
        }

        if ($this->payee_user_id) {
            $user = $this->payeeUser()->first();

            if ($user && trim((string) $user->name) !== '') {
                return trim((string) $user->name);
            }
        }

        return (string) ($this->payee_name ?? '');
    }
}
