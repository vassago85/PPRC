<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class EventGalleryPhoto extends Model
{
    protected $fillable = [
        'event_id',
        'path',
        'caption',
        'sort_order',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function publicUrl(): string
    {
        return \App\Support\MediaDisk::url($this->path);
    }
}
