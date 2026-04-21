<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClubBadge extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'accent_color',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'club_badge_member')
            ->withPivot(['awarded_at', 'notes'])
            ->withTimestamps();
    }
}
