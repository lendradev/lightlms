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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_the_dashboard_lists_the_lessons_that_are_due(): void
    {
        $user = User::factory()->create();
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'title' => 'The due lesson',
            'due_at' => now()->addDay(),
        ]);

        $user->enrolledCourses()->attach($course->id, [
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard/Student')
                ->where('upcoming', fn ($upcoming) => $upcoming->count() === 1)
                ->where('upcoming.0.lesson_title', $lesson->title)
                ->where('upcoming.0.slug', $course->slug)
            );
    }
}
