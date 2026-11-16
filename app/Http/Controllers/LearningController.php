<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\GradeCategory;
use App\Models\GradeGrade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Services\EnrollmentService;
use App\Services\GradebookService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly PermissionService $permissions,
        private readonly GradebookService $gradebook,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $courses = $user->enrolledCourses()
            ->withCount('modules')
            ->orderByDesc('course_user.last_accessed_at')
            ->get()
            ->map(function ($course) {
                $course->enrollment_progress = (int) ($course->pivot->progress ?? 0);
                $course->enrollment_status = (string) ($course->pivot->status ?? 'active');

                return $course;
            });

        return Inertia::render('Learning/Index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        $user = $request->user();

        abort_unless($this->enrollments->isEnrolled($user->id, $course->id), 403, 'You are not enrolled in this course.');

        $course->load(['modules' => fn ($query) => $query->where('visible', true), 'modules.lessons.assignment', 'modules.lessons.quiz']);

        $completed = DB::table('lesson_user')
            ->where('user_id', $user->id)
            ->whereIn('lesson_id', $course->modules->flatMap(fn ($module) => $module->lessons)->pluck('id'))
            ->where('completed', true)
            ->pluck('lesson_id')
            ->all();

        return Inertia::render('Learning/Course', [
            'course' => $course,
            'completedLessonIds' => $completed,
            'canManage' => $this->permissions->allows($user, 'course.edit', $course),
        ]);
    }

    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();
        $course = $lesson->module?->course;

        abort_unless(
            $course !== null && $this->enrollments->isEnrolled($user->id, $course->id),
            403,
        );

        $this->enrollments->markLessonComplete($user->id, $lesson->id);

        return back()->with('success', 'Activity marked as complete.');
    }

    /**
     * The learner's transcript for one course.
     */
    public function grades(Request $request, Course $course): Response
    {
        $user = $request->user();

        abort_unless($this->permissions->allows($user, 'grade.viewown', $course), 403);

        $view = $this->gradebook->reportView($course, $user);

        return Inertia::render('Learning/Gradebook', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'items' => $view['items']->map(fn (GradeItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'max_points' => (float) ($item->max_points ?? 0),
                'category_id' => $item->category_id,
                'sort_order' => $item->sort_order,
            ]),
            'categories' => $view['categories']->map(fn (GradeCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'weight' => $category->weight === null ? null : (float) $category->weight,
            ]),
            'grades' => $view['grades']->values()->map(fn (GradeGrade $grade) => [
                'item_id' => $grade->item_id,
                'final_points' => $grade->final_points === null ? null : (float) $grade->final_points,
                'feedback' => $grade->feedback,
                'status' => $grade->status,
            ]),
            'categoryTotals' => $view['categoryTotals'],
            'total' => $view['total'],
        ]);
    }
}
