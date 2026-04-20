<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'slug', 'title', 'subtitle', 'excerpt', 'body',
        'hero_image_path', 'meta_title', 'meta_description',
        'is_published', 'published_at',
        'show_in_nav', 'nav_sort_order',
        'author_id',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_nav' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeInNav(Builder $q): Builder
    {
        return $q->where('show_in_nav', true)->orderBy('nav_sort_order');
    }
}
