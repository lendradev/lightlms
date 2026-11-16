<?php

namespace App\Http\Controllers\Teaching;

use App\Enums\CompletionRuleType;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCompletionRule;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CertificateService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The teacher side of certificates: the rule of the course, who is eligible,
 * and issuing or revoking a certificate.
 */
class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
        private readonly PermissionService $permissions,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request, Course $course): Response
    {
        abort_unless($this->permissions->allows($request->user(), 'course.viewparticipants', $course), 403);

        $rule = $this->certificates->ruleFor($course);
        $students = $course->students()->orderBy('name')->get();

        $issued = Certificate::query()
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('user_id');

        return Inertia::render('Teaching/Certificates', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'rule' => [
                'type' => $rule->rule_type->value,
                'label' => $rule->rule_type->label(),
                'minimum_percent' => $rule->minimum_percent,
                'enabled' => (bool) $rule->enabled,
            ],
            'ruleTypes' => array_map(
                fn (CompletionRuleType $type) => ['value' => $type->value, 'label' => $type->label()],
                CompletionRuleType::cases(),
            ),
            'learners' => $students->map(function (User $student) use ($course, $issued) {
                $evaluation = $this->certificates->evaluate($course, $student);
                $certificate = $issued->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'progress' => $evaluation['progress'],
                    'grade' => $evaluation['grade'],
                    'eligible' => $evaluation['eligible'],
                    'reason' => $evaluation['reason'],
                    'certificate' => $certificate === null ? null : [
                        'id' => $certificate->id,
                        'code' => $certificate->code,
                        'issued_at' => $certificate->issued_at?->toIso8601String(),
                        'revoked_at' => $certificate->revoked_at?->toIso8601String(),
                    ],
                ];
            })->values(),
        ]);
    }

    /**
     * Change the rule of the course.
     */
    public function updateRule(Request $request, Course $course): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'course.edit', $course), 403);

        $validated = $request->validate([
            'rule_type' => ['required', 'in:all_lessons,grade_minimum,instructor_approval'],
            'minimum_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'enabled' => ['required', 'boolean'],
        ]);

        $rule = $this->certificates->ruleFor($course);

        $rule->update([
            'rule_type' => $validated['rule_type'],
            'minimum_percent' => $validated['rule_type'] === 'grade_minimum'
                ? $validated['minimum_percent']
                : null,
            'enabled' => (bool) $validated['enabled'],
        ]);

        $this->audit->record(
            'certificate.rule.update',
            $rule,
            CourseCompletionRule::class,
            $rule->id,
            $course->id,
            [
                'rule_type' => $rule->getOriginal('rule_type'),
                'minimum_percent' => $rule->getOriginal('minimum_percent'),
                'enabled' => $rule->getOriginal('enabled'),
            ],
            [
                'rule_type' => $rule->rule_type->value,
                'minimum_percent' => $rule->minimum_percent,
                'enabled' => $rule->enabled,
            ],
        );

        return back()->with('success', 'Completion rule saved.');
    }

    /**
     * Issue a certificate to one learner.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'course.managegrades', $course), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $learner = User::query()->findOrFail($validated['user_id']);

        abort_unless(
            $course->students()->where('users.id', $learner->id)->exists(),
            404,
            'That person is not in this course.',
        );

        $certificate = $this->certificates->issue($course, $learner, $request->user());

        $this->audit->record(
            'certificate.issue',
            $certificate,
            Certificate::class,
            $certificate->id,
            $course->id,
            [],
            ['user_id' => $learner->id, 'code' => $certificate->code],
        );

        return back()->with('success', 'Certificate issued to '.$learner->name.'.');
    }

    /**
     * Revoke a certificate.
     */
    public function destroy(Request $request, Course $course, Certificate $certificate): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'course.managegrades', $course), 403);
        abort_unless($certificate->course_id === $course->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->certificates->revoke($certificate, $validated['reason']);

        $this->audit->record(
            'certificate.revoke',
            $certificate,
            Certificate::class,
            $certificate->id,
            $course->id,
            ['revoked_at' => null],
            ['revoked_at' => now()->toIso8601String(), 'reason' => $validated['reason']],
        );

        return back()->with('success', 'Certificate revoked.');
    }
}
