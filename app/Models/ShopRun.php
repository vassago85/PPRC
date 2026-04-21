<?php

namespace App\Models;

use App\Enums\ShopRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopRun extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'preview_visible',
        'orders_open_at',
        'orders_close_at',
        'announcement',
        'waitlist_last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShopRunStatus::class,
            'preview_visible' => 'boolean',
            'orders_open_at' => 'datetime',
            'orders_close_at' => 'datetime',
            'waitlist_last_notified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class)->orderBy('sort_order');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }

    public function isAcceptingOrders(): bool
    {
        if ($this->status !== ShopRunStatus::Open) {
            return false;
        }
        $now = now();
        if ($this->orders_open_at && $this->orders_open_at->isFuture()) {
            return false;
        }
        if ($this->orders_close_at && $this->orders_close_at->isPast()) {
            return false;
        }

        return true;
    }

    public function catalogVisibleToPublic(): bool
    {
        if ($this->status === ShopRunStatus::Draft) {
            return false;
        }
        if ($this->status === ShopRunStatus::Open || $this->status === ShopRunStatus::Closed) {
            return true;
        }

        return $this->status === ShopRunStatus::Preview && $this->preview_visible;
    }
}
