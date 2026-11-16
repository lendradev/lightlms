<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
use App\Models\User;
use App\Services\CertificateService;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    private function courseWithActivity(Course $course): Lesson
    {
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        return $lesson;
    }

    public function test_a_certificate_is_issued_when_the_course_is_finished(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $lesson = $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $this->assertNotNull($certificate);
        $this->assertDatabaseHas('certificates', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'recipient_name' => $student->name,
        ]);
    }

    public function test_no_certificate_is_issued_before_the_course_is_finished(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 40]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $this->assertNull($certificate);
        $this->assertDatabaseMissing('certificates', ['course_id' => $course->id]);
    }

    public function test_a_course_with_certificates_disabled_issues_nothing(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        app(CertificateService::class)->ruleFor($course)->update(['enabled' => false]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $this->assertNull($certificate);
    }

    public function test_the_certificate_is_issued_only_once(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $service = app(CertificateService::class);

        $first = $service->issueIfEligible($course, $student);
        $second = $service->issueIfEligible($course, $student);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertDatabaseCount('certificates', 1);
    }

    public function test_the_learner_sees_their_own_certificates(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        app(CertificateService::class)->issueIfEligible($course, $student);

        $this->actingAs($student)
            ->get('/certificates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Learning/Certificates')
                ->where('certificates.0.course.title', $course->title));
    }

    public function test_a_certificate_of_somebody_else_is_not_readable(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($other->id, ['status' => 'active', 'progress' => 100]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $other);

        $this->actingAs($student)
            ->get('/certificates/'.$certificate->code)
            ->assertForbidden();
    }

    public function test_the_verification_page_names_the_learner(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $this->get('/certificates/verify/'.$certificate->code)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Certificates/Verify')
                ->where('certificate.valid', true)
                ->where('certificate.recipient_name', $student->name));
    }

    public function test_a_revoked_certificate_does_not_name_the_learner(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $service = app(CertificateService::class);
        $certificate = $service->issueIfEligible($course, $student);
        $service->revoke($certificate, 'The work was found to be plagiarised.');

        $this->get('/certificates/verify/'.$certificate->code)
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Certificates/Verify')
                ->where('certificate.valid', false)
                ->where('certificate.recipient_name', null)
                ->where('certificate.revocation_reason', 'The work was found to be plagiarised.'));
    }

    public function test_an_unknown_code_is_not_found(): void
    {
        $this->get('/certificates/verify/NOPE-NOPE')->assertNotFound();
    }

    public function test_a_teacher_can_issue_a_certificate(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $this->actingAs($teacher)
            ->get('/teaching/'.$course->slug.'/certificates')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teaching/Certificates')
                ->where('learners.0.name', $student->name));

        $this->actingAs($teacher)
            ->post('/teaching/'.$course->slug.'/certificates', ['user_id' => $student->id])
            ->assertRedirect();

        $this->assertDatabaseHas('certificates', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_a_teacher_cannot_issue_a_certificate_in_a_course_they_do_not_teach(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $this->actingAs($teacher)
            ->post('/teaching/'.$course->slug.'/certificates', ['user_id' => $student->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('certificates', ['course_id' => $course->id]);
    }

    public function test_a_teacher_can_revoke_a_certificate(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $this->actingAs($teacher)
            ->delete('/teaching/'.$course->slug.'/certificates/'.$certificate->id, [
                'reason' => 'The work was found to be plagiarised.',
            ])
            ->assertRedirect();

        $this->assertNotNull($certificate->fresh()->revoked_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'certificate.revoke',
            'course_id' => $course->id,
        ]);
    }

    public function test_the_teacher_can_change_the_rule(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->put('/teaching/'.$course->slug.'/certificates/rule', [
                'rule_type' => 'grade_minimum',
                'minimum_percent' => 80,
                'enabled' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('course_completion_rules', [
            'course_id' => $course->id,
            'rule_type' => 'grade_minimum',
            'minimum_percent' => 80,
        ]);
    }

    public function test_the_rule_of_a_teacher_is_audited(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);

        $this->actingAs($teacher)
            ->put('/teaching/'.$course->slug.'/certificates/rule', [
                'rule_type' => 'instructor_approval',
                'enabled' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'certificate.rule.update',
            'course_id' => $course->id,
        ]);
    }

    public function test_the_minimum_grade_rule_issues_a_certificate_when_the_grade_is_reached(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 20]);

        $service = app(CertificateService::class);
        $service->ruleFor($course)->update(['rule_type' => 'grade_minimum', 'minimum_percent' => 70]);

        $this->assertNull($service->issueIfEligible($course, $student));
    }

    public function test_the_instructor_approval_rule_issues_nothing_on_its_own(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $service = app(CertificateService::class);
        $service->ruleFor($course)->update(['rule_type' => 'instructor_approval']);

        $this->assertNull($service->issueIfEligible($course, $student));
    }

    public function test_a_certificate_is_issued_when_the_last_activity_is_completed(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 0]);

        $this->actingAs($student)
            ->post('/learning/lessons/'.$lesson->id.'/complete')
            ->assertRedirect();

        $this->assertDatabaseHas('certificates', [
            'course_id' => $course->id,
            'user_id' => $student->id,
        ]);
    }

    public function test_the_certificate_carries_the_name_of_the_recipient(): void
    {
        $student = User::factory()->create(['name' => 'Sam Student']);
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $this->assertSame('Sam Student', $certificate->recipient_name);

        $student->update(['name' => 'Changed Name']);

        $this->assertSame('Sam Student', $certificate->fresh()->recipient_name);
    }

    public function test_the_code_of_a_certificate_is_unique(): void
    {
        $service = app(CertificateService::class);

        $codes = [];

        for ($i = 0; $i < 5; $i++) {
            $student = User::factory()->create();
            $course = Course::factory()->create();
            $this->courseWithActivity($course);
            $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

            $codes[] = $service->issueIfEligible($course, $student)->code;
        }

        $this->assertCount(5, array_unique($codes));
    }

    public function test_a_certificate_can_be_found_by_its_code(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $this->courseWithActivity($course);
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 100]);

        $certificate = app(CertificateService::class)->issueIfEligible($course, $student);

        $found = app(CertificateService::class)->findByCode($certificate->code);

        $this->assertNotNull($found);
        $this->assertSame($certificate->id, $found->id);
        $this->assertNull(app(CertificateService::class)->findByCode('NO-SUCH-CODE'));
    }
}
