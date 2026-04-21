<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MatchFormat extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'short_name',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
