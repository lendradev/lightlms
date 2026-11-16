<?php

namespace App\Services;

use App\Enums\CourseRole;
use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Decides what a person may do with a course.
 *
 * The site-wide role gives the baseline; the course membership overrides it.
 * A course instructor keeps their site role for everything else, but inside
 * this course they are only a student.
 */
class PermissionService
{
    /**
     * Capabilities the site administrator always holds.
     *
     * @var list<string>
     */
    private const ADMIN_CAPABILITIES = [
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
    ];

    public function courseRole(User $user, Course $course): ?CourseRole
    {
        if ($user->isAdmin()) {
            return CourseRole::Instructor;
        }

        // The owner of a course always holds the instructor role there, even
        // before they appear in the roster.
        if ($course->user_id === $user->id) {
            return CourseRole::Instructor;
        }

        $role = DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->value('role');

        return $role === null ? null : CourseRole::from($role);
    }

    public function isEnrolled(User $user, Course $course): bool
    {
        if ($course->user_id === $user->id) {
            return true;
        }

        return DB::table('course_user')
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();
    }

    public function allows(User $user, string $capability, Course $course): bool
    {
        if ($user->isAdmin()) {
            return in_array($capability, self::ADMIN_CAPABILITIES, true);
        }

        $role = $this->courseRole($user, $course);

        if ($role === null) {
            return false;
        }

        return in_array($capability, $role->permissions(), true);
    }

    /** @return list<string> */
    public function capabilities(User $user, Course $course): array
    {
        if ($user->isAdmin()) {
            return self::ADMIN_CAPABILITIES;
        }

        $role = $this->courseRole($user, $course);

        return $role === null ? [] : $role->permissions();
    }
}
