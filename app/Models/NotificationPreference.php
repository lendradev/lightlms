<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    /** @var list<string> */
    protected $fillable = ['user_id', 'notification_event_id', 'channel', 'enabled', 'digest'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<NotificationEvent, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(NotificationEvent::class, 'notification_event_id');
    }
}
