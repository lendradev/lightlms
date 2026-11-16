<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use App\Services\AuditService;
use App\Services\EnrollmentService;
use App\Services\PermissionService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The learner's side of an assignment: read it, hand it in, see the mark.
 */
class AssignmentController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissions,
        private readonly EnrollmentService $enrollments,
        private readonly PermissionService $permissions,
        private readonly AuditService $audit,
    ) {}

    public function show(Request $request, Assignment $assignment): Response
    {
        $user = $request->user();
        $course = $this->courseFor($assignment);

        abort_unless(
            $this->permissions->allows($user, 'submission.viewown', $course),
            403,
            'You do not have access to this assignment.',
        );

        $lesson = $assignment->lesson;
        $attempts = $this->submissions->attempts($assignment, $user);
        $latest = $attempts->last();

        return Inertia::render('Learning/Assignment', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'assignment' => [
                'id' => $assignment->id,
                'title' => $lesson?->title ?? 'Assignment',
                'instructions' => $lesson?->instructions,
                'due_at' => $lesson?->due_at?->toIso8601String(),
                'grade_max' => (int) ($lesson?->grade_max ?? 100),
                'submission_type' => $assignment->submission_type,
                'max_attempts' => (int) $assignment->max_attempts,
                'allow_late' => (bool) $assignment->allow_late,
            ],
            'attemptsUsed' => $attempts->count(),
            'attemptsLeft' => $this->submissions->attemptsLeft($assignment, $user),
            'submissions' => $attempts->map(fn (Submission $submission) => [
                'id' => $submission->id,
                'content' => $submission->content,
                'status' => $submission->status,
                'score' => $submission->score === null ? null : (float) $submission->score,
                'feedback' => $submission->feedback,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
                'graded_at' => $submission->graded_at?->toIso8601String(),
            ])->values(),
            'latest' => $latest === null ? null : [
                'id' => $latest->id,
                'content' => $latest->content,
                'status' => $latest->status,
                'score' => $latest->score === null ? null : (float) $latest->score,
                'feedback' => $latest->feedback,
                'submitted_at' => $latest->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        $user = $request->user();
        $course = $this->courseFor($assignment);

        abort_unless(
            $this->permissions->allows($user, 'submission.viewown', $course),
            403,
            'You do not have access to this assignment.',
        );

        $lesson = $assignment->lesson;

        if ($lesson?->due_at !== null && $lesson->due_at->isPast() && ! $assignment->allow_late) {
            return back()->with('error', 'The deadline for this assignment has passed.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:10', 'max:20000'],
        ]);

        try {
            $submission = $this->submissions->submit($assignment, $user, $validated['content']);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->audit->record(
            'submission.create',
            $submission,
            Submission::class,
            $submission->id,
            $course->id,
            [],
            ['assignment_id' => $assignment->id],
        );

        return back()->with('success', 'Assignment submitted.');
    }

    /**
     * The course the assignment belongs to. Fail closed when the chain is broken.
     */
    private function courseFor(Assignment $assignment): Course
    {
        $course = $assignment->lesson?->module?->course;

        abort_if($course === null, 404, 'This assignment is not attached to a course.');

        return $course;
    }
}
