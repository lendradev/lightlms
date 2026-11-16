<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubmissionResource;
use App\Models\Assignment;
use App\Models\Submission;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Services\SubmissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubmissionController extends Controller
{
    public function __construct(
        protected readonly SubmissionService $submissions,
        protected readonly PermissionService $permissions,
        protected readonly AuditService $audit,
    ) {}

    /**
     * The attempts of the caller on one assignment.
     */
    public function index(Request $request, Assignment $assignment): AnonymousResourceCollection
    {
        $user = $request->user();
        $course = $assignment->lesson?->module?->course;

        abort_if($course === null, 404, 'This assignment is not attached to a course.');
        abort_unless($this->permissions->allows($user, 'submission.viewown', $course), 403);

        $attempts = $this->submissions->attempts($assignment, $user);

        return SubmissionResource::collection($attempts);
    }

    /**
     * Hand in an attempt.
     */
    public function store(Request $request, Assignment $assignment): SubmissionResource
    {
        $user = $request->user();
        $course = $assignment->lesson?->module?->course;

        abort_if($course === null, 404, 'This assignment is not attached to a course.');
        abort_unless($this->permissions->allows($user, 'submission.viewown', $course), 403);

        $lesson = $assignment->lesson;

        if ($lesson?->due_at !== null && $lesson->due_at->isPast() && ! $assignment->allow_late) {
            abort(422, 'The deadline for this assignment has passed.');
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'min:10', 'max:20000'],
        ]);

        try {
            $submission = $this->submissions->submit($assignment, $user, $validated['content']);
        } catch (\RuntimeException $exception) {
            abort(422, $exception->getMessage());
        }

        $this->submissions->announceSubmission($assignment, $submission);

        $this->audit->record(
            'submission.create',
            $submission,
            Submission::class,
            $submission->id,
            $course->id,
            [],
            ['assignment_id' => $assignment->id, 'via' => 'api'],
        );

        return new SubmissionResource($submission);
    }
}
