<?php

namespace App\Services;

use App\Enums\CompletionRuleType;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCompletionRule;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Decides when a course is finished and issues the certificate that follows.
 *
 * The rule of a course says what "finished" means. A course without a rule
 * uses every activity completed, which is the safest default: it never issues
 * a certificate earlier than the learner has done the work.
 */
class CertificateService
{
    public function __construct(
        private readonly GradebookService $gradebook,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * The rule of a course, or the default when it has none.
     */
    public function ruleFor(Course $course): CourseCompletionRule
    {
        return CourseCompletionRule::query()->firstOrCreate(
            ['course_id' => $course->id],
            ['rule_type' => CompletionRuleType::AllLessons, 'enabled' => true],
        );
    }

    /**
     * Whether the course may issue certificates at all.
     */
    public function isEnabled(Course $course): bool
    {
        return $this->ruleFor($course)->enabled;
    }

    /**
     * Why a learner is or is not finished.
     *
     * The reason is written for a person, not for a machine.
     *
     * @return array{eligible: bool, reason: string, progress: int, grade: float|null}
     */
    public function evaluate(Course $course, User $learner): array
    {
        $rule = $this->ruleFor($course);
        $progress = $this->progress($course, $learner);
        $grade = $this->gradebook->courseTotal($course, $learner);
        $percent = $grade['percent'];

        if (! $rule->enabled) {
            return ['eligible' => false, 'reason' => 'This course does not issue certificates.', 'progress' => $progress, 'grade' => $percent];
        }

        $eligible = match ($rule->rule_type) {
            CompletionRuleType::AllLessons => $progress >= 100,
            CompletionRuleType::GradeMinimum => $percent !== null && $percent >= (float) $rule->minimum_percent,
            CompletionRuleType::InstructorApproval => false,
        };

        $reason = match ($rule->rule_type) {
            CompletionRuleType::AllLessons => $eligible
                ? 'Every activity is complete.'
                : "{$progress}% of the activities are complete.",
            CompletionRuleType::GradeMinimum => $eligible
                ? 'The required grade has been reached.'
                : 'The current grade is '.($percent === null ? 'not set' : $percent.'%').'. The course asks for '.$rule->minimum_percent.'%.',
            CompletionRuleType::InstructorApproval => 'A teacher has to approve this completion.',
        };

        return [
            'eligible' => $eligible,
            'reason' => $reason,
            'progress' => $progress,
            'grade' => $percent,
        ];
    }

    /**
     * The certificate of one learner in one course, if there is one.
     */
    public function certificateFor(Course $course, User $learner): ?Certificate
    {
        return Certificate::query()
            ->where('course_id', $course->id)
            ->where('user_id', $learner->id)
            ->first();
    }

    /**
     * Issue a certificate, or return the one that already exists.
     *
     * The name is copied onto the certificate, so a later change to the
     * account does not rewrite history.
     */
    public function issue(Course $course, User $learner, ?User $issuer = null): Certificate
    {
        $existing = $this->certificateFor($course, $learner);

        if ($existing !== null) {
            return $existing;
        }

        return Certificate::query()->create([
            'course_id' => $course->id,
            'user_id' => $learner->id,
            'code' => $this->newCode(),
            'recipient_name' => $learner->name,
            'issued_at' => now(),
            'issued_by' => $issuer?->id,
        ]);
    }

    /**
     * Revoke a certificate.
     */
    public function revoke(Certificate $certificate, string $reason): Certificate
    {
        $certificate->update([
            'revoked_at' => now(),
            'revocation_reason' => $reason,
        ]);

        return $certificate;
    }

    /**
     * Find a certificate by its public code.
     */
    public function findByCode(string $code): ?Certificate
    {
        return Certificate::query()->where('code', $code)->first();
    }

    /**
     * The certificate of a learner, issued now when they have earned one.
     *
     * Returns the existing certificate when there already is one, so a second
     * call never issues a second one.
     */
    public function issueIfEligible(Course $course, User $learner): ?Certificate
    {
        $rule = $this->ruleFor($course);

        if (! $rule->rule_type->isAutomatic()) {
            return null;
        }

        $existing = $this->certificateFor($course, $learner);

        if ($existing !== null) {
            return $existing;
        }

        $evaluation = $this->evaluate($course, $learner);

        if (! $evaluation['eligible']) {
            return null;
        }

        $certificate = $this->issue($course, $learner);

        $this->notifications->notify(
            $learner,
            'certificate.issued',
            'Your certificate for '.$course->title.' is ready.',
            '/certificates/'.$certificate->code,
            ['course_id' => $course->id, 'certificate_id' => $certificate->id],
        );

        return $certificate;
    }

    /**
     * The share of activities a learner has completed.
     */
    private function progress(Course $course, User $learner): int
    {
        $row = DB::table('course_user')
            ->where('course_id', $course->id)
            ->where('user_id', $learner->id)
            ->first();

        return (int) ($row->progress ?? 0);
    }

    /**
     * A public code for one certificate.
     */
    private function newCode(): string
    {
        do {
            $code = Str::upper(Str::random(4).'-'.Str::upper(Str::random(4)));
        } while (Certificate::query()->where('code', $code)->exists());

        return $code;
    }
}
