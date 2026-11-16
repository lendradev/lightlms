<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\GradeGrade;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    private function assignmentFor(Course $course, array $attributes = []): Assignment
    {
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        return Assignment::factory()->create(array_merge(['lesson_id' => $lesson->id], $attributes));
    }

    public function test_an_enrolled_student_can_open_an_assignment(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->get("/assignments/{$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Learning/Assignment')->where('assignment.id', $assignment->id));
    }

    public function test_a_student_outside_the_course_cannot_open_an_assignment(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);

        $this->actingAs($student)->get("/assignments/{$assignment->id}")->assertForbidden();
    }

    public function test_a_student_can_hand_in_an_assignment(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", ['content' => 'My mapping of the bus route system.'])
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'status' => 'submitted',
        ]);
    }

    public function test_a_short_answer_is_rejected(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", ['content' => 'short'])
            ->assertSessionHasErrors('content');

        $this->assertDatabaseMissing('submissions', ['assignment_id' => $assignment->id]);
    }

    public function test_the_attempt_limit_is_enforced(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course, ['max_attempts' => 1]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", ['content' => 'First attempt of the answer.'])
            ->assertRedirect();

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", ['content' => 'Second attempt of the answer.'])
            ->assertSessionHas('error');

        $this->assertSame(1, Submission::query()->where('assignment_id', $assignment->id)->count());
    }

    public function test_a_grader_sees_the_hand_ins(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);
        Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/assignments/{$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teaching/Assignment')->has('submissions', 1));
    }

    public function test_a_grader_cannot_open_the_hand_ins_of_another_course(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $other = Course::factory()->create();
        $assignment = $this->assignmentFor($other);

        $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/assignments/{$assignment->id}")
            ->assertNotFound();
    }

    public function test_grading_a_submission_marks_it_and_mirrors_it_into_the_gradebook(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);
        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/assignments/{$assignment->id}/{$submission->id}/grade", [
                'score' => 78,
                'feedback' => 'Good structure, tighten the loop analysis.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'graded',
            'score' => 78,
            'graded_by' => $teacher->id,
        ]);

        $this->assertDatabaseHas('grade_grades', [
            'user_id' => $student->id,
            'final_points' => 78,
            'status' => 'graded',
        ]);
    }

    public function test_a_score_above_the_maximum_is_refused(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);
        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/assignments/{$assignment->id}/{$submission->id}/grade", ['score' => 500])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'submitted',
        ]);
    }

    public function test_a_student_cannot_grade(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($student)
            ->post("/teaching/{$course->slug}/assignments/{$assignment->id}/{$submission->id}/grade", ['score' => 100])
            ->assertForbidden();

        unset($teacher);
    }

    public function test_grading_is_audited(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);
        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/assignments/{$assignment->id}/{$submission->id}/grade", ['score' => 60])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $teacher->id,
            'event' => 'submission.grade',
            'course_id' => $course->id,
        ]);
    }

    public function test_a_graded_submission_shows_the_mark_to_the_student(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);
        Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'graded',
            'score' => 64,
            'feedback' => 'Nice work on the loops.',
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        $this->actingAs($student)
            ->get("/assignments/{$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('latest.score', 64)->where('latest.status', 'graded'));
    }

    public function test_adding_an_assignment_activity_creates_the_assignment_record(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)
            ->post("/teaching/courses/{$course->slug}/lessons", [
                'module_id' => $module->id,
                'title' => 'Write a reflection',
                'type' => 'assignment',
            ])
            ->assertRedirect();

        $lesson = Lesson::query()->where('title', 'Write a reflection')->firstOrFail();

        $this->assertDatabaseHas('assignments', ['lesson_id' => $lesson->id]);
    }

    public function test_the_grade_item_is_created_for_the_assignment(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);
        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/assignments/{$assignment->id}/{$submission->id}/grade", ['score' => 55])
            ->assertRedirect();

        $grade = GradeGrade::query()->where('user_id', $student->id)->firstOrFail();

        $this->assertSame(55.0, (float) $grade->final_points);
    }
}
