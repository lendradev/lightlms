<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeCategory extends Model
{
    /** @var list<string> */
    protected $fillable = ['course_id', 'parent_id', 'name', 'aggregation', 'weight', 'sort_order', 'hidden'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'hidden' => 'boolean',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<GradeItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(GradeItem::class, 'category_id');
    }

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
