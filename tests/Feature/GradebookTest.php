<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
use App\Models\User;
use App\Services\GradebookService;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GradebookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_graded_activities_become_grade_items(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 50]);
        Lesson::factory()->reading()->create(['module_id' => $module->id]);

        app(GradebookService::class)->syncItems($course);

        $items = GradeItem::query()->where('course_id', $course->id)->get();

        $this->assertCount(1, $items, 'Only gradable activities become grade items.');
        $this->assertSame(50.0, (float) $items->first()->max_points);
    }

    public function test_a_saved_grade_is_stored_against_the_item_and_student(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $item = GradeItem::query()->where('course_id', $course->id)->firstOrFail();
        $student = User::factory()->create();
        $teacher = User::factory()->instructor()->create();

        $gradebook->saveGrade($item, $student, 42.5, 'Well done', $teacher);

        $this->assertDatabaseHas('grade_grades', [
            'item_id' => $item->id,
            'user_id' => $student->id,
            'raw_points' => 42.5,
            'status' => 'graded',
            'source' => 'manual',
        ]);
    }

    public function test_the_course_total_is_a_weighted_mean_of_graded_items(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $items = GradeItem::query()->where('course_id', $course->id)->orderBy('sort_order')->get();

        $student = User::factory()->create();
        $teacher = User::factory()->instructor()->create();

        // The first item counts triple.
        $items[0]->update(['weight' => 3]);
        $items[1]->update(['weight' => 1]);

        $gradebook->saveGrade($items[0], $student, 80, null, $teacher);
        $gradebook->saveGrade($items[1], $student, 40, null, $teacher);

        $total = $gradebook->courseTotal($course, $student);

        $this->assertSame(70.0, $total['points'], 'Weighted mean of 80 (weight 3) and 40 (weight 1).');
        $this->assertSame(70.0, $total['percent']);
    }

    public function test_ungraded_items_are_left_out_of_the_total(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $items = GradeItem::query()->where('course_id', $course->id)->orderBy('sort_order')->get();

        $student = User::factory()->create();
        $teacher = User::factory()->instructor()->create();

        $gradebook->saveGrade($items[0], $student, 90, null, $teacher);

        $total = $gradebook->courseTotal($course, $student);

        $this->assertSame(90.0, $total['points']);
        $this->assertSame(1, $total['graded']);
    }

    public function test_an_instructor_can_open_the_gradebook_of_a_course_they_teach(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $teacher = User::factory()->instructor()->create();

        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'role' => 'instructor',
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/gradebook")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teaching/Gradebook'));
    }

    public function test_a_student_cannot_open_the_gradebook(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get("/teaching/{$course->slug}/gradebook")
            ->assertForbidden();
    }

    public function test_an_instructor_can_export_the_gradebook_as_csv(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $teacher = User::factory()->instructor()->create();

        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'role' => 'instructor',
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/gradebook/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
