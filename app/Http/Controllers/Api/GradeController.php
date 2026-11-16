<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GradeResource;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\GradeItem;
use App\Services\GradebookService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GradeController extends Controller
{
    public function __construct(
        protected readonly PermissionService $permissions,
        protected readonly GradebookService $gradebook,
    ) {}

    /**
     * The gradebook of one course.
     *
     * A learner sees their own marks. Someone who teaches the course sees the
     * marks of everybody in it.
     */
    public function index(Request $request, Course $course): AnonymousResourceCollection
    {
        $user = $request->user();

        $canSeeAll = $this->permissions->allows($user, 'grade.viewall', $course);

        abort_unless(
            $canSeeAll || $this->permissions->allows($user, 'grade.viewown', $course),
            403,
            'You do not have access to the gradebook of this course.',
        );

        $this->gradebook->syncItems($course);

        $grades = GradeGrade::query()
            ->whereIn('item_id', $this->itemIds($course))
            ->when(! $canSeeAll, fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('item_id')
            ->get();

        return GradeResource::collection($grades);
    }

    /**
     * The grade items of a course.
     *
     * @return list<int>
     */
    private function itemIds(Course $course): array
    {
        return GradeItem::query()
            ->where('course_id', $course->id)
            ->pluck('id')
            ->all();
    }
}
