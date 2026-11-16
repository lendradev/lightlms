<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeItem extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'course_id',
        'category_id',
        'source_type',
        'source_id',
        'name',
        'weight',
        'max_points',
        'pass_points',
        'aggregation',
        'sort_order',
        'hidden',
        'locked',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'max_points' => 'decimal:2',
            'pass_points' => 'decimal:2',
            'hidden' => 'boolean',
            'locked' => 'boolean',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<GradeCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(GradeCategory::class);
    }

    /** @return HasMany<GradeGrade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(GradeGrade::class, 'item_id');
    }
}
