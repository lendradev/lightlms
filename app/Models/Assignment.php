<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    /** @var list<string> */
    protected $fillable = ['lesson_id', 'submission_type', 'max_attempts', 'allow_late', 'require_approval'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'max_attempts' => 'integer',
            'allow_late' => 'boolean',
            'require_approval' => 'boolean',
        ];
    }

    /** @return BelongsTo<Lesson, $this> */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** @return HasMany<Submission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }
}
