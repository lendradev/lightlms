<?php

namespace App\Models;

use App\Enums\ActivityType;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'module_id',
        'title',
        'type',
        'position',
        'duration_minutes',
        'body',
        'is_preview',
        'completion_rule',
        'max_attempts',
        'grade_max',
        'available_from',
        'available_to',
        'due_at',
        'instructions',
        'completion_message',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'duration_minutes' => 'integer',
            'is_preview' => 'boolean',
            'completion_rule' => 'integer',
            'max_attempts' => 'integer',
            'grade_max' => 'integer',
            'available_from' => 'datetime',
            'available_to' => 'datetime',
            'due_at' => 'datetime',
            'type' => ActivityType::class,
        ];
    }

    /** @return BelongsTo<Module, $this> */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /** @return HasOneThrough<Course, Module, $this> */
    public function course(): HasOneThrough
    {
        return $this->hasOneThrough(
            Course::class,
            Module::class,
            'id',
            'id',
            'module_id',
            'course_id',
        );
    }

    /** @return HasOne<Assignment, $this> */
    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class);
    }

    /** @return HasOne<Quiz, $this> */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    /** @return HasOne<Forum, $this> */
    public function forum(): HasOne
    {
        return $this->hasOne(Forum::class);
    }

    /** @return HasMany<FileAsset, $this> */
    public function files(): HasMany
    {
        return $this->hasMany(FileAsset::class);
    }

    public function isAvailable(?\DateTimeInterface $at = null): bool
    {
        $at = $at ?? now();

        if ($this->available_from !== null && $this->available_from->isFuture()) {
            return false;
        }

        return ! ($this->available_to !== null && $this->available_to->isPast());
    }
}
