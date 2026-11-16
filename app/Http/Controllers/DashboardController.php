<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Grade;
use App\Models\Submission;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->admin($request);
        }

        if ($user->canTeach()) {
            return $this->instructor($request);
        }

        return $this->student($request);
    }

    private function student(Request $request): Response
    {
        $user = $request->user();

        $enrollments = $user->enrolledCourses()
            ->with('modules')
            ->orderByDesc('course_user.last_accessed_at')
            ->limit(6)
            ->get()
            ->map(function ($course) {
                // `progress` and `enrollment_status` are pivot values. They cannot
                // be assigned to the course itself: `status` is cast to the
                // CourseStatus enum on the model.
                $course->enrollment_progress = (int) ($course->pivot->progress ?? 0);
                $course->enrollment_status = (string) ($course->pivot->status ?? 'active');

                return $course;
            });

        $upcoming = Course::query()
            ->join('course_user', 'course_user.course_id', '=', 'courses.id')
            ->where('course_user.user_id', $user->id)
            ->join('modules', 'modules.course_id', '=', 'courses.id')
            ->join('lessons', 'lessons.module_id', '=', 'modules.id')
            ->whereNotNull('lessons.due_at')
            ->where('lessons.due_at', '>=', now())
            ->select('courses.title', 'courses.slug', 'lessons.title as lesson_title', 'lessons.due_at')
            ->orderBy('lessons.due_at')
            ->limit(5)
            ->get();

        $grades = Grade::query()
            ->where('user_id', $user->id)
            ->with('course:id,title,slug')
            ->orderByDesc('graded_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard/Student', [
            'enrollments' => $enrollments,
            'upcoming' => $upcoming,
            'grades' => $grades,
            'stats' => [
                'enrolled' => $user->enrolledCourses()->count(),
                'completed' => $user->enrolledCourses()->wherePivot('status', 'completed')->count(),
                'pendingSubmissions' => Submission::query()->where('status', 'submitted')->count(),
            ],
        ]);
    }

    private function instructor(Request $request): Response
    {
        $user = $request->user();

        $courses = Course::query()
            ->ownedBy($user)
            ->withCount('modules')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();

        $pendingReviews = Submission::query()
            ->where('status', 'submitted')
            ->whereHas('assignment.lesson.module.course', fn ($query) => $query->where('user_id', $user->id))
            ->count();

        return Inertia::render('Dashboard/Instructor', [
            'courses' => $courses,
            'stats' => [
                'courses' => Course::query()->ownedBy($user)->count(),
                'students' => $user->enrolledCourses()->count(),
                'pendingReviews' => $pendingReviews,
            ],
        ]);
    }

    private function admin(Request $request): Response
    {
        return Inertia::render('Dashboard/Admin', [
            'stats' => [
                'users' => User::query()->count(),
                'courses' => Course::query()->count(),
                'publishedCourses' => Course::query()->published()->count(),
                'enrollments' => DB::table('course_user')->count(),
            ],
            'siteName' => Setup::siteName(),
        ]);
    }
}
