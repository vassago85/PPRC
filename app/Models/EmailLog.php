<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'user_id',
        'to_email',
        'to_name',
        'from_email',
        'from_name',
        'subject',
        'mailable_class',
        'status',
        'error',
        'context',
        'message_id',
        'sent_at',
    ];

    protected $casts = [
        'context' => AsArrayObject::class,
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
