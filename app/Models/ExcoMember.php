<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExcoMember extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name', 'position', 'bio',
        'email', 'phone', 'photo_path',
        'sort_order', 'term_started_on', 'term_ends_on',
        'is_current', 'linked_user_id',
    ];

    protected $casts = [
        'term_started_on' => 'date',
        'term_ends_on' => 'date',
        'is_current' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function scopeCurrent(Builder $q): Builder
    {
        return $q->where('is_current', true)->orderBy('sort_order');
    }
}
