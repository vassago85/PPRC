<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    protected $fillable = [
        'key', 'type', 'eyebrow', 'title', 'subtitle', 'body',
        'image_path', 'cta_label', 'cta_url',
        'meta', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'meta' => AsArrayObject::class,
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order');
    }
}
