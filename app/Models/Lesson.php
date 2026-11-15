<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    /** @use HasFactory<\Database\Factories\LessonFactory> */
    use HasFactory;

    public const TYPE_VIDEO = 'video';
    public const TYPE_READING = 'reading';
    public const TYPE_QUIZ = 'quiz';
    public const TYPE_ASSIGNMENT = 'assignment';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'title',
        'type',
        'position',
        'duration_minutes',
        'body',
        'is_preview',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'duration_minutes' => 'integer',
            'is_preview' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Module, $this>
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
