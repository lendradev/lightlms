<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EnrollmentService
{
    public function isEnrolled(int $userId, int $courseId): bool
    {
        return DB::table('course_user')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('status', 'active')
            ->exists();
    }

    public function enroll(User $user, Course $course): bool
    {
        if ($this->isEnrolled($user->id, $course->id)) {
            return false;
        }

        if ($course->capacity !== null) {
            $taken = DB::table('course_user')->where('course_id', $course->id)->count();

            if ($taken >= $course->capacity) {
                return false;
            }
        }

        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function unenroll(int $userId, int $courseId): void
    {
        DB::table('course_user')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->delete();
    }

    public function markLessonComplete(int $userId, int $lessonId): void
    {
        DB::table('lesson_user')->updateOrInsert(
            ['lesson_id' => $lessonId, 'user_id' => $userId],
            [
                'completed' => true,
                'completed_at' => now(),
                'last_viewed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $this->recalculateProgress($userId, $lessonId);
    }

    /**
     * Progress is the share of graded activities the learner has completed.
     * Only activities that can be completed count, so a preview never skews it.
     */
    private function recalculateProgress(int $userId, int $lessonId): void
    {
        $lesson = Lesson::query()->with('module')->find($lessonId);

        if ($lesson === null) {
            return;
        }

        $courseId = $lesson->module->course_id;

        $total = DB::table('lessons')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $courseId)
            ->where('lessons.is_preview', false)
            ->count();

        if ($total === 0) {
            return;
        }

        $done = DB::table('lesson_user')
            ->join('lessons', 'lessons.id', '=', 'lesson_user.lesson_id')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('lesson_user.user_id', $userId)
            ->where('modules.course_id', $courseId)
            ->where('lesson_user.completed', true)
            ->where('lessons.is_preview', false)
            ->count();

        $progress = (int) round(($done / $total) * 100);

        DB::table('course_user')
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->update([
                'progress' => $progress,
                'last_accessed_at' => now(),
                'status' => $progress >= 100 ? 'completed' : 'active',
                'updated_at' => now(),
            ]);
    }
}
