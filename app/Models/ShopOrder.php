<?php

namespace App\Models;

use App\Enums\PaymentProvider;
use App\Enums\ShopOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    protected $fillable = [
        'shop_run_id',
        'user_id',
        'status',
        'ship_to_name',
        'ship_phone',
        'ship_line1',
        'ship_line2',
        'ship_city',
        'ship_province',
        'ship_postal_code',
        'ship_country',
        'subtotal_cents',
        'shipping_cents',
        'total_cents',
        'currency',
        'payment_provider',
        'paystack_reference',
        'eft_reference',
        'proof_path',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ShopOrderStatus::class,
            'subtotal_cents' => 'integer',
            'shipping_cents' => 'integer',
            'total_cents' => 'integer',
            'payment_provider' => PaymentProvider::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ShopRun::class, 'shop_run_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ShopOrderLine::class);
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->lines()->sum('line_total_cents');
        $this->subtotal_cents = $subtotal;
        $this->total_cents = $subtotal + (int) $this->shipping_cents;
        $this->save();
    }
}
