<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function put(string $key, ?string $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
    }

    /** @return array<string, string> */
    public static function allAsArray(): array
    {
        return static::query()->pluck('value', 'key')->all();
    }

    /** @return HasMany<self, $this> */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}
