<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeCategory extends Model
{
    /** @var list<string> */
    protected $fillable = ['course_id', 'parent_id', 'name', 'sort_order', 'hidden'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
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
}
