<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_home_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Welcome')->has('courses'));
    }

    public function test_guest_can_browse_the_course_catalogue(): void
    {
        Course::factory()->create(['title' => 'Systems Thinking']);

        $this->get('/courses')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Courses/Index')->has('courses.data', 1));
    }

    public function test_draft_courses_are_hidden_from_the_catalogue(): void
    {
        Course::factory()->draft()->create(['title' => 'Hidden Draft']);

        $this->get('/courses')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('courses.data', 0));
    }

    public function test_guest_can_open_a_published_course(): void
    {
        $course = Course::factory()->create(['title' => 'Accessible Interfaces']);

        $module = Module::factory()->create(['course_id' => $course->id, 'title' => 'Foundations']);
        Lesson::factory()->create(['module_id' => $module->id, 'title' => 'Contrast and Colour']);

        $this->get("/courses/{$course->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Courses/Show')
                ->has('course.modules', 1)
                ->has('course.modules.0.lessons', 1));
    }

    public function test_draft_course_returns_not_found(): void
    {
        $course = Course::factory()->draft()->create();

        $this->get("/courses/{$course->slug}")->assertNotFound();
    }

    public function test_course_progress_is_tracked_on_the_pivot_table(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();

        $course->students()->attach($user->id, ['progress' => 40]);

        $this->assertDatabaseHas('course_user', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'progress' => 40,
        ]);
    }
}
