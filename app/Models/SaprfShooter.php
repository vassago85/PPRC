<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaprfShooter extends Model
{
    protected $fillable = [
        'membership_number',
        'first_name',
        'last_name',
        'email',
        'verified_on',
        'notes',
        'imported_by_user_id',
        'imported_at',
    ];

    protected $casts = [
        'verified_on' => 'date',
        'imported_at' => 'datetime',
    ];

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by_user_id');
    }
}
