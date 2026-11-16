<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationEvent extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'key',
        'label',
        'category',
        'channel',
        'default_enabled',
        'is_digestable',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'default_enabled' => 'boolean',
            'is_digestable' => 'boolean',
        ];
    }

    /** @return HasMany<NotificationPreference, $this> */
    public function preferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }
}
