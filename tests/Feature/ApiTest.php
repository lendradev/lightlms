<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    /**
     * The headers for a request made as one person.
     *
     * The auth manager keeps its guards between requests inside one test, so
     * a second request in the same test would still see the first person.
     * Forgetting the guards lets each request carry its own token.
     *
     * @return array<string, string>
     */
    private function headers(User $user): array
    {
        Auth::forgetGuards();

        return ['Authorization' => 'Bearer '.$this->tokenFor($user)];
    }

    public function test_a_request_without_a_token_is_refused(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_a_request_with_a_wrong_token_is_refused(): void
    {
        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer not-a-real-token'])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'unauthenticated');
    }

    public function test_the_token_holder_can_read_their_own_account(): void
    {
        $user = User::factory()->create(['name' => 'Sam Student']);

        $this->getJson('/api/v1/me', $this->headers($user))
            ->assertOk()
            ->assertJsonPath('data.name', 'Sam Student')
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_the_catalogue_is_listed(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['title' => 'Systems Thinking']);

        $this->getJson('/api/v1/courses', $this->headers($user))
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Systems Thinking')
            ->assertJsonPath('data.0.slug', $course->slug);
    }

    public function test_a_draft_course_is_not_in_the_catalogue(): void
    {
        $user = User::factory()->create();
        Course::factory()->draft()->create(['title' => 'Hidden Draft']);

        $this->getJson('/api/v1/courses', $this->headers($user))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_person_can_enrol_themselves(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $this->postJson('/api/v1/enrollments', ['course' => $course->slug], $this->headers($user))
            ->assertOk()
            ->assertJsonPath('data.course.slug', $course->slug);

        $this->assertDatabaseHas('course_user', [
            'course_id' => $course->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);
    }

    public function test_a_course_without_self_enrolment_refuses_the_api(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create(['self_enroll' => false]);

        $this->postJson('/api/v1/enrollments', ['course' => $course->slug], $this->headers($user))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_enrolling_without_a_course_is_a_validation_error(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/enrollments', [], $this->headers($user))
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed')
            ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['course']]]);
    }

    public function test_a_person_can_leave_a_course(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $course->students()->attach($user->id, ['status' => 'active']);

        $this->deleteJson('/api/v1/enrollments/'.$course->slug, [], $this->headers($user))
            ->assertNoContent();

        $this->assertDatabaseMissing('course_user', [
            'course_id' => $course->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_only_the_teaching_staff_sees_the_participants(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->getJson('/api/v1/courses/'.$course->slug.'/participants', $this->headers($teacher))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id);

        $this->getJson('/api/v1/courses/'.$course->slug.'/participants', $this->headers($student))
            ->assertForbidden()
            ->assertJsonPath('error.code', 'forbidden');
    }

    public function test_a_learner_sees_only_their_own_marks(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $other = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $course->students()->attach($other->id, ['status' => 'active']);

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $item = GradeItem::query()->create([
            'course_id' => $course->id,
            'source_type' => 'activity',
            'source_id' => $lesson->id,
            'name' => 'Essay',
            'max_points' => 100,
        ]);

        GradeGrade::query()->create([
            'item_id' => $item->id,
            'user_id' => $student->id,
            'final_points' => 80,
            'status' => 'graded',
        ]);

        GradeGrade::query()->create([
            'item_id' => $item->id,
            'user_id' => $other->id,
            'final_points' => 60,
            'status' => 'graded',
        ]);

        $response = $this->getJson('/api/v1/courses/'.$course->slug.'/grades', $this->headers($student))
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(80.0, (float) $response->json('data.0.final_points'));
    }

    public function test_a_teacher_sees_every_mark(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $item = GradeItem::query()->create([
            'course_id' => $course->id,
            'source_type' => 'activity',
            'source_id' => $lesson->id,
            'name' => 'Essay',
            'max_points' => 100,
        ]);

        GradeGrade::query()->create([
            'item_id' => $item->id,
            'user_id' => $student->id,
            'final_points' => 80,
            'status' => 'graded',
        ]);

        $this->getJson('/api/v1/courses/'.$course->slug.'/grades', $this->headers($teacher))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_a_person_outside_the_course_cannot_read_the_gradebook(): void
    {
        $teacher = User::factory()->instructor()->create();
        $stranger = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);

        $this->getJson('/api/v1/courses/'.$course->slug.'/grades', $this->headers($stranger))
            ->assertForbidden();
    }

    public function test_a_learner_can_hand_in_an_assignment(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id]);
        $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->postJson(
            '/api/v1/assignments/'.$assignment->id.'/submissions',
            ['content' => 'My mapping of the bus route system.'],
            $this->headers($student),
        )
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted');

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
        ]);
    }

    public function test_a_short_submission_is_refused(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id]);
        $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->postJson(
            '/api/v1/assignments/'.$assignment->id.'/submissions',
            ['content' => 'short'],
            $this->headers($student),
        )
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');
    }

    public function test_a_person_outside_the_course_cannot_hand_in(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id]);
        $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);

        $this->postJson(
            '/api/v1/assignments/'.$assignment->id.'/submissions',
            ['content' => 'My mapping of the bus route system.'],
            $this->headers($student),
        )->assertForbidden();
    }

    public function test_the_inbox_is_readable_and_can_be_marked_read(): void
    {
        $user = User::factory()->create();

        $notification = Notification::query()->create([
            'user_id' => $user->id,
            'type' => 'submission.graded',
            'data' => ['message' => 'Your submission has been graded.'],
        ]);

        $this->getJson('/api/v1/notifications', $this->headers($user))
            ->assertOk()
            ->assertJsonPath('data.0.message', 'Your submission has been graded.');

        $this->postJson('/api/v1/notifications/'.$notification->id.'/read', [], $this->headers($user))
            ->assertOk()
            ->assertJsonPath('data.read_at', fn ($value) => $value !== null);
    }

    public function test_a_notification_of_somebody_else_is_not_readable(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $notification = Notification::query()->create([
            'user_id' => $other->id,
            'type' => 'submission.graded',
            'data' => ['message' => 'Private.'],
        ]);

        $this->postJson('/api/v1/notifications/'.$notification->id.'/read', [], $this->headers($user))
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    public function test_a_token_can_be_created_and_revoked_on_the_web(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/settings/api-tokens')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Settings/ApiTokens'));

        $this->actingAs($user)
            ->post('/settings/api-tokens', ['name' => 'Grading script'])
            ->assertRedirect(route('settings.api-tokens.index'))
            ->assertSessionHas('issued_token');

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'Grading script',
        ]);

        $tokenId = PersonalAccessToken::query()->firstOrFail()->id;

        $this->actingAs($user)
            ->delete('/settings/api-tokens/'.$tokenId)
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_a_revoked_token_stops_working(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test');
        $plain = $token->plainTextToken;

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$plain])->assertOk();

        $token->accessToken->delete();

        Auth::forgetGuards();

        $this->getJson('/api/v1/me', ['Authorization' => 'Bearer '.$plain])
            ->assertUnauthorized();
    }

    public function test_a_submission_made_through_the_api_is_audited(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id]);
        $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->postJson(
            '/api/v1/assignments/'.$assignment->id.'/submissions',
            ['content' => 'My mapping of the bus route system.'],
            $this->headers($student),
        )->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $student->id,
            'event' => 'submission.create',
            'course_id' => $course->id,
        ]);
    }

    public function test_the_api_answers_with_the_error_shape(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/v1/courses/does-not-exist', $this->headers($user));

        $response->assertNotFound()->assertJsonStructure([
            'error' => ['code', 'message', 'details'],
        ]);
    }
}
