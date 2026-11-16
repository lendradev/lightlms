<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\GradeGrade;
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
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));

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
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));

        $response = $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/gradebook/export");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_a_category_weight_changes_the_course_total(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $items = GradeItem::query()->where('course_id', $course->id)->orderBy('sort_order')->get();

        $exams = $gradebook->saveCategory($course, 'Exams', null, 'weighted_mean', 3);
        $homework = $gradebook->saveCategory($course, 'Homework', null, 'weighted_mean', 1);

        $items[0]->update(['category_id' => $exams->id]);
        $items[1]->update(['category_id' => $homework->id]);

        $student = User::factory()->create();
        $teacher = User::factory()->instructor()->create();

        $gradebook->saveGrade($items[0], $student, 100, null, $teacher);
        $gradebook->saveGrade($items[1], $student, 50, null, $teacher);

        $total = $gradebook->courseTotal($course, $student);

        // Categories roll up first, then the course rolls up the categories.
        $this->assertSame(87.5, $total['points']);
        $this->assertSame(87.5, $total['percent']);
    }

    public function test_a_category_total_uses_the_aggregation_of_that_category(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $items = GradeItem::query()->where('course_id', $course->id)->orderBy('sort_order')->get();

        $exams = $gradebook->saveCategory($course, 'Exams', null, 'weighted_mean', 3);
        $items[0]->update(['category_id' => $exams->id]);
        $items[1]->update(['category_id' => $exams->id]);

        $student = User::factory()->create();
        $teacher = User::factory()->instructor()->create();

        $gradebook->saveGrade($items[0], $student, 100, null, $teacher);
        $gradebook->saveGrade($items[1], $student, 50, null, $teacher);

        $subtotal = $gradebook->categoryTotal($course, $exams, $student);

        $this->assertSame(75.0, $subtotal['points'], 'The mean of 100 and 50.');
        $this->assertSame(100.0, $subtotal['max']);
        $this->assertSame(75.0, $subtotal['percent']);
    }

    public function test_a_manual_item_is_counted_in_the_total(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $item = GradeItem::query()->where('course_id', $course->id)->firstOrFail();
        $manual = $gradebook->createManualItem($course, 'Final project', 100);

        $student = User::factory()->create();
        $teacher = User::factory()->instructor()->create();

        $gradebook->saveGrade($item, $student, 60, null, $teacher);
        $gradebook->saveGrade($manual, $student, 90, null, $teacher);

        $total = $gradebook->courseTotal($course, $student);

        $this->assertSame(75.0, $total['points']);
        $this->assertSame(2, $total['graded']);
    }

    public function test_a_locked_item_is_left_out_of_the_total(): void
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
        $gradebook->saveGrade($items[1], $student, 20, null, $teacher);

        $items[1]->update(['locked' => true]);

        $total = $gradebook->courseTotal($course, $student);

        $this->assertSame(90.0, $total['points']);
        $this->assertSame(1, $total['graded']);
    }

    public function test_an_excluded_grade_is_left_out_of_the_total(): void
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
        $gradebook->saveGrade($items[1], $student, 20, null, $teacher);

        GradeGrade::query()
            ->where('item_id', $items[1]->id)
            ->where('user_id', $student->id)
            ->update(['status' => 'excluded']);

        $total = $gradebook->courseTotal($course, $student);

        $this->assertSame(90.0, $total['points']);
        $this->assertSame(1, $total['graded']);
    }

    public function test_the_letter_grade_follows_the_course_thresholds(): void
    {
        $course = Course::factory()->create();
        $gradebook = app(GradebookService::class);

        $this->assertSame('A', $gradebook->letterGrade($course, 95.0));
        $this->assertSame('B', $gradebook->letterGrade($course, 85.0));
        $this->assertSame('C', $gradebook->letterGrade($course, 75.0));
        $this->assertSame('D', $gradebook->letterGrade($course, 65.0));
        $this->assertSame('E', $gradebook->letterGrade($course, 42.0));

        DB::table('grade_settings')->insert([
            'course_id' => $course->id,
            'name' => 'letter_a',
            'value' => '80',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame('A', $gradebook->letterGrade($course, 85.0));
    }

    public function test_a_student_sees_only_their_own_transcript(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $student = User::factory()->create();
        $other = User::factory()->create();

        $this->enrol($course, $student, 'student');
        $this->enrol($course, $other, 'student');

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $item = GradeItem::query()->where('course_id', $course->id)->firstOrFail();
        $teacher = User::factory()->instructor()->create();

        $gradebook->saveGrade($item, $student, 80, null, $teacher);
        $gradebook->saveGrade($item, $other, 30, null, $teacher);

        $this->actingAs($student)
            ->get("/learning/{$course->slug}/grades")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Learning/Gradebook')
                ->where('total.points', 80)
                ->where('grades.0.final_points', 80)
                ->etc());
    }

    public function test_a_student_cannot_open_the_transcript_of_a_course_they_are_not_in(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $student = User::factory()->create();

        $this->actingAs($student)
            ->get("/learning/{$course->slug}/grades")
            ->assertForbidden();
    }

    public function test_an_instructor_can_create_a_category_and_it_is_audited(): void
    {
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/gradebook/categories", [
                'name' => 'Exams',
                'aggregation' => 'weighted_mean',
                'weight' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grade_categories', [
            'course_id' => $course->id,
            'name' => 'Exams',
            'aggregation' => 'weighted_mean',
            'weight' => 2,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'gradebook.category.create',
            'course_id' => $course->id,
            'actor_id' => $teacher->id,
        ]);
    }

    public function test_a_student_cannot_create_a_category(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post("/teaching/{$course->slug}/gradebook/categories", [
                'name' => 'Exams',
                'aggregation' => 'weighted_mean',
            ])
            ->assertForbidden();
    }

    public function test_an_instructor_can_create_a_manual_item_and_it_is_audited(): void
    {
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/gradebook/items", [
                'name' => 'Final project',
                'max_points' => 100,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grade_items', [
            'course_id' => $course->id,
            'name' => 'Final project',
            'source_type' => 'manual',
            'max_points' => 100,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'gradebook.item.create',
            'course_id' => $course->id,
            'actor_id' => $teacher->id,
        ]);
    }

    public function test_an_instructor_can_change_the_weight_of_an_item(): void
    {
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        app(GradebookService::class)->syncItems($course);
        $item = GradeItem::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($teacher)
            ->put("/teaching/{$course->slug}/gradebook/items/{$item->id}", ['weight' => 2.5])
            ->assertRedirect();

        $this->assertSame(2.5, (float) $item->fresh()->weight);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'gradebook.item.update',
            'course_id' => $course->id,
            'actor_id' => $teacher->id,
        ]);
    }

    public function test_an_instructor_can_save_the_course_settings(): void
    {
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));

        $this->actingAs($teacher)
            ->put("/teaching/{$course->slug}/gradebook/settings", [
                'aggregation' => 'mean',
                'letter_a' => 85,
                'letter_b' => 75,
                'letter_c' => 65,
                'letter_d' => 55,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('grade_settings', [
            'course_id' => $course->id,
            'name' => 'aggregation',
            'value' => 'mean',
        ]);

        $this->assertSame('mean', app(GradebookService::class)->courseAggregation($course));
    }

    public function test_the_gradebook_settings_page_needs_the_grade_edit_permission(): void
    {
        $teacher = $this->instructorFor($course = Course::factory()->create(['user_id' => null]));

        $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/gradebook/settings")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teaching/GradebookSettings'));
    }

    public function test_deleting_a_category_returns_its_items_to_the_top_level(): void
    {
        $course = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $item = GradeItem::query()->where('course_id', $course->id)->firstOrFail();
        $category = $gradebook->saveCategory($course, 'Exams', null, 'weighted_mean', 2);
        $item->update(['category_id' => $category->id]);

        $teacher = $this->instructorFor($course);

        $this->actingAs($teacher)
            ->delete("/teaching/{$course->slug}/gradebook/categories/{$category->id}")
            ->assertRedirect();

        $this->assertNull($item->fresh()->category_id);
        $this->assertDatabaseMissing('grade_categories', ['id' => $category->id]);
    }

    private function instructorFor(Course $course): User
    {
        $teacher = User::factory()->instructor()->create();

        $this->enrol($course, $teacher, 'instructor');

        return $teacher;
    }

    private function enrol(Course $course, User $user, string $role): void
    {
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
