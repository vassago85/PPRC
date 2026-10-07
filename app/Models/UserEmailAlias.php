<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An additional email address a user can sign in with. The primary address
 * still lives on users.email; everything in this table is a secondary alias
 * that both login and password reset lookups accept via AliasAwareUserProvider.
 *
 * Outbound club mail (payment requests, confirmations, newsletters) uses the
 * primary address only — aliases exist so a merged member can log in with an
 * email they still remember, not to multiply the addresses the club has to
 * copy on everything.
 */
class UserEmailAlias extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    /**
     * Always store alias emails lowercased — matches how users.email is
     * normalised, so a case-insensitive login lookup never has to deal with
     * mixed-case data creeping in from the admin merge modal.
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn ($value) => $value === null
            ? null
            : strtolower(trim((string) $value)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
