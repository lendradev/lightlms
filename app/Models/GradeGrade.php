<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeGrade extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'item_id',
        'user_id',
        'raw_points',
        'final_points',
        'feedback',
        'status',
        'is_overridden',
        'overridden_by',
        'overridden_at',
        'source',
        'graded_by',
        'graded_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'raw_points' => 'decimal:2',
            'final_points' => 'decimal:2',
            'is_overridden' => 'boolean',
            'overridden_at' => 'datetime',
            'graded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<GradeItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class, 'item_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
