<?php

namespace App\Enums;

enum QuestionType: string
{
    case MultipleChoice = 'multiple_choice';
    case MultipleSelect = 'multiple_select';
    case TrueFalse = 'true_false';
    case ShortAnswer = 'short_answer';
    case Essay = 'essay';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoice => 'Multiple choice',
            self::MultipleSelect => 'Multiple select',
            self::TrueFalse => 'True / false',
            self::ShortAnswer => 'Short answer',
            self::Essay => 'Essay',
        };
    }

    /** Short answers and essays are graded by a human. */
    public function isAutoGraded(): bool
    {
        return $this !== self::ShortAnswer && $this !== self::Essay;
    }
}
