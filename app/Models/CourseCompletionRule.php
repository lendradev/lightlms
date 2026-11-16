<?php

namespace App\Models;

use App\Enums\CompletionRuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseCompletionRule extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'course_id',
        'rule_type',
        'minimum_percent',
        'enabled',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'rule_type' => CompletionRuleType::class,
            'minimum_percent' => 'integer',
            'enabled' => 'boolean',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
