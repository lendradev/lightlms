<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\FileAsset;
use App\Models\GradeItem;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles the submission life cycle of an assignment.
 *
 * A submission is one learner's answer to one assignment. A learner may hand
 * in more than once when the assignment allows several attempts; the grader
 * always looks at the latest one.
 */
class SubmissionService
{
    public function __construct(
        private readonly GradebookService $gradebook,
        private readonly NotificationService $notifications,
        private readonly CertificateService $certificates,
    ) {}

    /**
     * The attempts a learner has already handed in, oldest first.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Submission>
     */
    public function attempts(Assignment $assignment, User $learner)
    {
        return Submission::query()
            ->where('assignment_id', $assignment->id)
            ->where('user_id', $learner->id)
            ->orderBy('created_at')
            ->get();
    }

    public function latest(Assignment $assignment, User $learner): ?Submission
    {
        return $this->attempts($assignment, $learner)->last();
    }

    /**
     * How many more times this learner may hand in.
     */
    public function attemptsLeft(Assignment $assignment, User $learner): int
    {
        $used = $this->attempts($assignment, $learner)->count();
        $max = (int) $assignment->max_attempts;

        return $max === 0 ? PHP_INT_MAX : max(0, $max - $used);
    }

    /**
     * Hand in an attempt.
     *
     * @throws \RuntimeException when the learner has used every attempt
     */
    public function submit(
        Assignment $assignment,
        User $learner,
        string $content,
        ?FileAsset $file = null,
    ): Submission {
        if ($this->attemptsLeft($assignment, $learner) === 0) {
            throw new \RuntimeException('You have used every attempt for this assignment.');
        }

        return Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $learner->id,
            'file_id' => $file?->id,
            'content' => $content,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * The latest submission of every learner who handed something in.
     *
     * @return Collection<int, Submission>
     */
    public function latestByLearner(Assignment $assignment)
    {
        $latest = DB::table('submissions')
            ->where('assignment_id', $assignment->id)
            ->selectRaw('MAX(id) as id')
            ->groupBy('user_id')
            ->pluck('id');

        return Submission::query()
            ->with(['student', 'grader'])
            ->whereIn('id', $latest)
            ->orderBy('submitted_at')
            ->get();
    }

    /**
     * Tell the teaching staff that a learner handed something in.
     */
    public function announceSubmission(Assignment $assignment, Submission $submission): int
    {
        $course = $assignment->lesson?->module?->course;

        if ($course === null) {
            return 0;
        }

        $staff = User::query()
            ->where('id', $course->user_id)
            ->get();

        $staff = $staff->merge(
            $course->students()
                ->wherePivot('role', '!=', 'student')
                ->get(),
        );

        return $this->notifications->notifyMany(
            $staff,
            'submission.received',
            $submission->student->name.' handed in '.($assignment->lesson?->title ?? 'an assignment').'.',
            '/teaching/'.$course->slug.'/assignments/'.$assignment->id,
            ['assignment_id' => $assignment->id, 'submission_id' => $submission->id],
        );
    }

    /**
     * Grade a submission and mirror the mark into the course gradebook.
     */
    public function grade(
        Submission $submission,
        User $grader,
        ?float $points,
        ?string $feedback,
    ): Submission {
        $assignment = $submission->assignment;
        $course = $assignment->lesson?->module?->course;

        $submission->update([
            'score' => $points,
            'feedback' => $feedback,
            'status' => $points === null ? 'submitted' : 'graded',
            'graded_by' => $grader->id,
            'graded_at' => now(),
        ]);

        if ($course !== null) {
            $this->gradebook->syncItems($course);

            $item = GradeItem::query()
                ->where('course_id', $course->id)
                ->where('source_type', 'activity')
                ->where('source_id', $assignment->lesson_id)
                ->first();

            if ($item !== null) {
                $this->gradebook->saveGrade($item, $submission->student, $points, $feedback, $grader);
            }

            $this->notifications->notify(
                $submission->student,
                'submission.graded',
                'Your submission has been graded.',
                $course !== null ? '/learning/'.$course->slug : null,
                ['assignment_id' => $assignment->id, 'score' => $points],
            );

            $this->certificates->issueIfEligible($course, $submission->student);
        }

        return $submission;
    }
}
