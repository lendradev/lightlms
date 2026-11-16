<?php

namespace App\Enums;

/**
 * How a course decides that a learner has finished it.
 */
enum CompletionRuleType: string
{
    case AllLessons = 'all_lessons';
    case GradeMinimum = 'grade_minimum';
    case InstructorApproval = 'instructor_approval';

    public function label(): string
    {
        return match ($this) {
            self::AllLessons => 'Every activity completed',
            self::GradeMinimum => 'A minimum grade',
            self::InstructorApproval => 'Approved by the teacher',
        };
    }

    /**
     * Whether the course can issue a certificate on its own, without a person
     * pressing a button.
     */
    public function isAutomatic(): bool
    {
        return $this !== self::InstructorApproval;
    }
}
