<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    /** @var list<string> */
    protected $fillable = ['quiz_id', 'type', 'prompt', 'options', 'answer', 'points', 'position', 'explanation'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'options' => 'array',
            'answer' => 'array',
            'points' => 'decimal:2',
            'position' => 'integer',
            'type' => QuestionType::class,
        ];
    }

    /** @return BelongsTo<Quiz, $this> */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
