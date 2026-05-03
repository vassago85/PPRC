<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ShopProduct extends Model
{
    protected $fillable = [
        'shop_run_id',
        'name',
        'slug',
        'description',
        'price_cents',
        'currency',
        'image_path',
        'sort_order',
        'is_active',
        'max_per_order',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'max_per_order' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ShopRun::class, 'shop_run_id');
    }

    public function orderLines(): HasMany
    {
        return $this->hasMany(ShopOrderLine::class);
    }

    public function imageUrl(): ?string
    {
        if (empty($this->image_path)) {
            return null;
        }

        return \App\Support\MediaDisk::url($this->image_path);
    }
}
