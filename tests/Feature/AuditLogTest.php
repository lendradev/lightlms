<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditService;
use App\Services\GradebookService;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_the_roster_records_who_added_whom(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();

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
            ->post("/teaching/{$course->slug}/roster", [
                'email' => $student->email,
                'role' => 'student',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $teacher->id,
            'event' => 'roster.assign',
            'course_id' => $course->id,
        ]);
    }

    public function test_saving_a_grade_records_the_old_and_new_value(): void
    {
        $course = Course::factory()->create(['user_id' => null]);
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();

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

        // A mark belongs to a participant, so the student has to be enrolled.
        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => 'student',
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Module::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->assignment()->create([
            'module_id' => Module::query()->where('course_id', $course->id)->value('id'),
        ]);

        app(GradebookService::class)->syncItems($course);

        $item = GradeItem::query()->where('course_id', $course->id)->firstOrFail();

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/gradebook/{$item->id}", [
                'user_id' => $student->id,
                'points' => 75,
            ])
            ->assertRedirect();

        $log = AuditLog::query()->where('event', 'grade.update')->firstOrFail();

        $this->assertSame($teacher->id, $log->actor_id);
        $this->assertSame(75.0, (float) $log->new_values['points']);
    }

    public function test_an_audit_entry_stores_the_actor_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        app(AuditService::class)->record('site.test', null, 'App\\Models\\User', $admin->id);

        $log = AuditLog::query()->where('event', 'site.test')->firstOrFail();

        $this->assertSame('admin', $log->actor_role);
        $this->assertSame($admin->id, $log->actor_id);
    }
}
