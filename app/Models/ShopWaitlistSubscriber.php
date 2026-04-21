<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShopWaitlistSubscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'user_id',
        'confirm_token',
        'unsubscribe_token',
        'confirmed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateTokens(): array
    {
        return [
            'confirm_token' => Str::random(48),
            'unsubscribe_token' => Str::random(48),
        ];
    }

    public function scopeConfirmed($query)
    {
        return $query->whereNotNull('confirmed_at');
    }

    public function scopeSubscribed($query)
    {
        return $query->whereNull('unsubscribed_at');
    }

    public function receivesNotifications(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }
}
