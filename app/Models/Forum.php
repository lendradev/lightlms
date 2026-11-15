<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    /** @var list<string> */
    protected $fillable = ['lesson_id', 'name', 'forum_type', 'allow_new_threads', 'allow_attachments'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'allow_new_threads' => 'boolean',
            'allow_attachments' => 'boolean',
        ];
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** @return HasMany<ForumPost, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }
}
