<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret', 'group', 'label', 'description'];

    protected $casts = [
        'value' => AsArrayObject::class,
        'is_secret' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::rememberForever('site_settings:'.$key, function () use ($key) {
            $row = static::query()->where('key', $key)->first();

            return $row ? ['v' => $row->resolvedValue(), 'secret' => $row->is_secret] : null;
        });

        return $cached['v'] ?? $default;
    }

    public static function put(string $key, mixed $value, array $attributes = []): self
    {
        $isSecret = (bool) ($attributes['is_secret'] ?? false);

        $row = static::query()->updateOrCreate(
            ['key' => $key],
            array_merge($attributes, [
                'value' => $isSecret
                    ? ['cipher' => Crypt::encryptString(is_string($value) ? $value : json_encode($value))]
                    : $value,
                'is_secret' => $isSecret,
            ]),
        );

        Cache::forget('site_settings:'.$key);

        return $row;
    }

    public function resolvedValue(): mixed
    {
        if (! $this->is_secret) {
            return $this->value;
        }

        $cipher = $this->value['cipher'] ?? null;
        if (! $cipher) {
            return null;
        }

        $plain = Crypt::decryptString($cipher);
        $decoded = json_decode($plain, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $plain;
    }
}
