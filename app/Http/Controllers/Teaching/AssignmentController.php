<?php

namespace App\Http\Controllers\Teaching;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\FileAsset;
use App\Models\Submission;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The grader's side of an assignment: read the hand-ins and mark them.
 */
class AssignmentController extends Controller
{
    public function __construct(
        private readonly SubmissionService $submissions,
        private readonly PermissionService $permissions,
        private readonly AuditService $audit,
    ) {}

    public function show(Request $request, Course $course, Assignment $assignment): Response
    {
        abort_unless($this->permissions->allows($request->user(), 'submission.viewall', $course), 403);
        abort_unless($this->belongsToCourse($assignment, $course), 404);

        $lesson = $assignment->lesson;
        $latest = $this->submissions->latestByLearner($assignment);
        $graded = $latest->firstWhere('status', 'graded');

        return Inertia::render('Teaching/Assignment', [
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
            ],
            'submissions' => $latest->map(fn (Submission $submission) => [
                'id' => $submission->id,
                'student' => [
                    'id' => $submission->student->id,
                    'name' => $submission->student->name,
                    'email' => $submission->student->email,
                ],
                'content' => $submission->content,
                'status' => $submission->status,
                'score' => $submission->score === null ? null : (float) $submission->score,
                'feedback' => $submission->feedback,
                'submitted_at' => $submission->submitted_at?->toIso8601String(),
                'graded_at' => $submission->graded_at?->toIso8601String(),
                'grader' => $submission->grader?->name,
                'file' => $this->fileProps($submission->file),
            ])->values(),
            'stats' => [
                'submitted' => $latest->count(),
                'graded' => $latest->filter(fn (Submission $submission) => $submission->status === 'graded')->count(),
                'awaiting' => $latest->filter(fn (Submission $submission) => $submission->status !== 'graded')->count(),
            ],
            'highlight' => $graded?->id,
        ]);
    }

    public function grade(Request $request, Course $course, Assignment $assignment, Submission $submission): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'submission.grade', $course), 403);
        abort_unless($this->belongsToCourse($assignment, $course), 404);
        abort_unless($submission->assignment_id === $assignment->id, 404);

        $validated = $request->validate([
            'score' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $score = $validated['score'] === null ? null : (float) $validated['score'];
        $max = (int) ($assignment->lesson?->grade_max ?? 100);

        if ($score !== null && $score > $max) {
            return back()->with('error', "The score cannot be higher than {$max}.");
        }

        $previous = $submission->score === null ? null : (float) $submission->score;

        $this->submissions->grade(
            $submission,
            $request->user(),
            $score,
            $validated['feedback'] ?? null,
        );

        $this->audit->record(
            'submission.grade',
            $submission,
            Submission::class,
            $submission->id,
            $course->id,
            ['score' => $previous],
            ['score' => $score],
        );

        return back()->with('success', 'Grade saved.');
    }

    private function belongsToCourse(Assignment $assignment, Course $course): bool
    {
        return $assignment->lesson?->module?->course_id === $course->id;
    }

    /**
     * The parts of an attachment that are safe to send to the browser.
     *
     * @return array<string, mixed>|null
     */
    private function fileProps(?FileAsset $file): ?array
    {
        if ($file === null) {
            return null;
        }

        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'url' => route('files.show', ['file' => $file->id]),
        ];
    }
}
