<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_a_student_sees_their_enrolled_courses(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id, ['status' => 'active', 'progress' => 25]);

        $this->actingAs($student)
            ->get('/learning')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Learning/Index')->has('courses', 1));
    }

    public function test_a_student_cannot_open_a_course_they_are_not_enrolled_in(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();

        $this->actingAs($student)->get("/learning/{$course->slug}")->assertForbidden();
    }

    public function test_an_enrolled_student_can_open_the_course_player(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['module_id' => $module->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->get("/learning/{$course->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Learning/Course')->has('course.modules', 1));
    }

    public function test_completing_an_activity_updates_the_enrollment_progress(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/learning/lessons/{$lesson->id}/complete")
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_user', [
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
        ]);

        $this->assertDatabaseHas('course_user', [
            'user_id' => $student->id,
            'course_id' => $course->id,
            'progress' => 100,
            'status' => 'completed',
        ]);
    }

    public function test_a_guest_cannot_reach_the_learning_area(): void
    {
        $this->get('/learning')->assertRedirect(route('login'));
    }
}
