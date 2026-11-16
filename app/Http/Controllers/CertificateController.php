<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\EnrollmentService;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The certificates of the signed-in person, and the public verification page.
 */
class CertificateController extends Controller
{
    public function __construct(
        private readonly CertificateService $certificates,
        private readonly EnrollmentService $enrollments,
        private readonly PermissionService $permissions,
    ) {}

    /**
     * The certificates of the person who is signed in.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $certificates = Certificate::query()
            ->where('user_id', $user->id)
            ->with('course')
            ->orderByDesc('issued_at')
            ->get();

        return Inertia::render('Learning/Certificates', [
            'certificates' => $certificates->map(fn (Certificate $certificate) => [
                'id' => $certificate->id,
                'code' => $certificate->code,
                'course' => [
                    'id' => $certificate->course->id,
                    'title' => $certificate->course->title,
                    'slug' => $certificate->course->slug,
                ],
                'issued_at' => $certificate->issued_at?->toIso8601String(),
                'revoked_at' => $certificate->revoked_at?->toIso8601String(),
            ])->values(),
            'pending' => $this->pendingCourses($user),
        ]);
    }

    /**
     * The courses the person is enrolled in but has not finished yet.
     */
    private function pendingCourses(User $user): array
    {
        $courses = $user->enrolledCourses()->get();

        return $courses->map(function (Course $course) use ($user) {
            $evaluation = $this->certificates->evaluate($course, $user);

            return [
                'course' => [
                    'id' => $course->id,
                    'title' => $course->title,
                    'slug' => $course->slug,
                ],
                'progress' => $evaluation['progress'],
                'grade' => $evaluation['grade'],
                'eligible' => $evaluation['eligible'],
                'reason' => $evaluation['reason'],
            ];
        })->values()->all();
    }

    /**
     * One certificate of the person who is signed in.
     */
    public function show(Request $request, string $code): Response
    {
        $certificate = $this->certificates->findByCode($code);

        abort_if($certificate === null, 404);
        abort_unless(
            $certificate->user_id === $request->user()->id
                || $this->permissions->allows($request->user(), 'course.viewparticipants', $certificate->course),
            403,
        );

        return Inertia::render('Learning/Certificate', [
            'certificate' => [
                'code' => $certificate->code,
                'recipient_name' => $certificate->recipient_name,
                'course_title' => $certificate->course->title,
                'course_slug' => $certificate->course->slug,
                'issued_at' => $certificate->issued_at?->toIso8601String(),
                'revoked_at' => $certificate->revoked_at?->toIso8601String(),
                'revocation_reason' => $certificate->revocation_reason,
            ],
        ]);
    }

    /**
     * The public verification page.
     *
     * Anyone with the code may check it. The page never names the learner
     * unless the certificate is valid.
     */
    public function verify(Request $request, string $code): Response
    {
        $certificate = $this->certificates->findByCode($code);

        abort_if($certificate === null, 404);

        return Inertia::render('Certificates/Verify', [
            'certificate' => [
                'code' => $certificate->code,
                'course_title' => $certificate->course->title,
                'issued_at' => $certificate->issued_at?->toIso8601String(),
                'revoked_at' => $certificate->revoked_at?->toIso8601String(),
                'revocation_reason' => $certificate->revocation_reason,
                'recipient_name' => $certificate->isRevoked() ? null : $certificate->recipient_name,
                'valid' => ! $certificate->isRevoked(),
            ],
        ]);
    }
}
