<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeachingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_a_student_cannot_open_the_teaching_area(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/teaching')->assertForbidden();
    }

    public function test_an_instructor_can_list_their_courses(): void
    {
        $instructor = User::factory()->instructor()->create();
        Course::factory()->create(['user_id' => $instructor->id, 'title' => 'Owned course']);
        Course::factory()->create(['title' => 'Someone else course']);

        $this->actingAs($instructor)
            ->get('/teaching')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teaching/Index')->has('courses', 1));
    }

    public function test_an_instructor_can_create_a_draft_course(): void
    {
        $instructor = User::factory()->instructor()->create();

        $this->actingAs($instructor)
            ->post('/teaching/courses', [
                'title' => 'Systems Thinking 101',
                'summary' => 'A short course.',
                'level' => 'beginner',
                'language' => 'en',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', [
            'title' => 'Systems Thinking 101',
            'user_id' => $instructor->id,
            'status' => 'draft',
        ]);
    }

    public function test_an_instructor_can_add_a_section_and_an_activity(): void
    {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/teaching/courses/{$course->slug}/modules", ['title' => 'Foundations'])
            ->assertRedirect();

        $module = $course->modules()->firstOrFail();

        $this->actingAs($instructor)
            ->post("/teaching/courses/{$course->slug}/lessons", [
                'module_id' => $module->id,
                'title' => 'What is a system?',
                'type' => 'reading',
                'duration_minutes' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('lessons', [
            'module_id' => $module->id,
            'title' => 'What is a system?',
            'type' => 'reading',
        ]);
    }

    public function test_an_instructor_cannot_touch_a_course_they_do_not_own(): void
    {
        $instructor = User::factory()->instructor()->create();
        $other = Course::factory()->create();

        $this->actingAs($instructor)
            ->post("/teaching/courses/{$other->slug}/modules", ['title' => 'Hijacked'])
            ->assertForbidden();
    }

    public function test_publishing_sets_the_published_timestamp(): void
    {
        $instructor = User::factory()->instructor()->create();
        $course = Course::factory()->draft()->create(['user_id' => $instructor->id]);

        $this->actingAs($instructor)
            ->post("/teaching/courses/{$course->slug}/publish")
            ->assertRedirect();

        $this->assertNotNull($course->fresh()->published_at);
    }
}
