<?php

namespace App\Enums;

/**
 * The role a person holds inside a single course.
 *
 * `users.role` is the site-wide role. This one decides what someone may do
 * with the course they are a member of.
 */
enum CourseRole: string
{
    case Student = 'student';
    case Assistant = 'assistant';
    case Instructor = 'instructor';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Assistant => 'Teaching assistant',
            self::Instructor => 'Instructor',
        };
    }

    public function canTeach(): bool
    {
        return $this !== self::Student;
    }

    /** @return list<string> */
    public function permissions(): array
    {
        return match ($this) {
            self::Student => [
                'course.view',
                'activity.view',
                'submission.viewown',
                'quiz.attempt',
                'forum.post',
                'grade.viewown',
            ],
            self::Assistant => [
                'course.view',
                'activity.view',
                'submission.viewown',
                'submission.viewall',
                'submission.grade',
                'quiz.attempt',
                'forum.post',
                'forum.moderate',
                'grade.viewown',
                'grade.viewall',
            ],
            self::Instructor => [
                'course.view',
                'course.edit',
                'course.manageactivities',
                'course.viewparticipants',
                'course.viewcoursegrades',
                'course.managegrades',
                'activity.view',
                'activity.edit',
                'submission.viewown',
                'submission.viewall',
                'submission.grade',
                'quiz.attempt',
                'quiz.manage',
                'forum.post',
                'forum.moderate',
                'grade.viewown',
                'grade.viewall',
                'grade.edit',
                'grade.export',
            ],
        };
    }
}
