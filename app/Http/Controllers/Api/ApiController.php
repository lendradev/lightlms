<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\EnrollmentResource;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Services\EnrollmentService;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    public function __construct(
        protected readonly PermissionService $permissions,
        protected readonly EnrollmentService $enrollments,
    ) {}

    /**
     * The person the token belongs to.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * The published catalogue.
     */
    public function courses(Request $request): AnonymousResourceCollection
    {
        $courses = Course::query()
            ->published()
            ->withCount('modules')
            ->search($request->string('q')->toString())
            ->when($request->string('level')->toString() !== '', fn ($query) => $query->where('level', $request->string('level')->toString()))
            ->orderBy('published_at', 'desc')
            ->paginate((int) $request->integer('per_page', 15));

        return CourseResource::collection($courses);
    }

    /**
     * One course with its structure.
     */
    public function course(Request $request, Course $course): CourseResource
    {
        abort_unless($course->isPublished(), 404);

        $course->load(['modules.lessons']);

        return new CourseResource($course);
    }

    /**
     * The courses the caller is enrolled in.
     */
    public function enrollments(Request $request): AnonymousResourceCollection
    {
        $courses = $request->user()->enrolledCourses()->get();

        return EnrollmentResource::collection($courses);
    }

    /**
     * Enrol the caller in a course.
     */
    public function enroll(Request $request): EnrollmentResource
    {
        $validated = $request->validate([
            'course' => ['required', 'string', 'max:255'],
        ]);

        $course = Course::query()->where('slug', $validated['course'])->first();

        abort_if($course === null, 404, 'No course with that slug.');
        abort_unless($course->isPublished(), 404);
        abort_unless($course->self_enroll, 403, 'This course does not accept self enrolment.');

        $this->enrollments->enroll($request->user(), $course);

        $enrolled = $request->user()->enrolledCourses()->where('courses.id', $course->id)->firstOrFail();

        return new EnrollmentResource($enrolled);
    }

    /**
     * Leave a course.
     */
    public function unenroll(Request $request, Course $course): JsonResponse
    {
        abort_unless(
            $this->enrollments->isEnrolled($request->user()->id, $course->id),
            403,
            'You are not enrolled in this course.',
        );

        $this->enrollments->unenroll($request->user()->id, $course->id);

        return response()->json(null, 204);
    }

    /**
     * The students of a course, for the people who teach it.
     */
    public function participants(Request $request, Course $course): AnonymousResourceCollection
    {
        abort_unless($this->permissions->allows($request->user(), 'course.viewparticipants', $course), 403);

        $students = $course->students()->orderBy('name')->get();

        return UserResource::collection($students);
    }

    /**
     * The progress of one participant in one course.
     */
    public function progress(Request $request, Course $course, int $user): Response
    {
        abort_unless(
            $this->permissions->allows($request->user(), 'course.viewparticipants', $course)
                || $request->user()->id === $user,
            403,
        );

        $row = DB::table('course_user')
            ->where('course_id', $course->id)
            ->where('user_id', $user)
            ->first();

        abort_if($row === null, 404);

        return response()->json([
            'data' => [
                'course_id' => $course->id,
                'user_id' => (int) $row->user_id,
                'role' => $row->role,
                'status' => $row->status,
                'progress' => (int) $row->progress,
                'enrolled_at' => $row->enrolled_at,
                'completed_at' => $row->completed_at,
                'last_accessed_at' => $row->last_accessed_at,
            ],
        ]);
    }
}
