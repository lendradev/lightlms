<?php

namespace Tests\Feature;

use App\Enums\CourseRole;
use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use App\Services\PermissionService;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CourseRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    public function test_a_course_membership_carries_its_own_role(): void
    {
        $course = Course::factory()->create();
        $teacher = User::factory()->instructor()->create();

        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $teacher->id,
            'role' => CourseRole::Instructor->value,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = app(PermissionService::class);

        $this->assertSame(
            CourseRole::Instructor,
            $permissions->courseRole($teacher, $course),
        );
        $this->assertTrue($permissions->allows($teacher, 'course.edit', $course));
        $this->assertTrue($permissions->allows($teacher, 'grade.edit', $course));
    }

    public function test_a_student_holds_no_teaching_capabilities(): void
    {
        $course = Course::factory()->create();
        $student = User::factory()->create();

        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'role' => CourseRole::Student->value,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = app(PermissionService::class);

        $this->assertTrue($permissions->allows($student, 'course.view', $course));
        $this->assertFalse($permissions->allows($student, 'course.edit', $course));
        $this->assertFalse($permissions->allows($student, 'grade.edit', $course));
    }

    public function test_someone_outside_the_course_has_no_capabilities(): void
    {
        $course = Course::factory()->create();
        $stranger = User::factory()->create();

        $permissions = app(PermissionService::class);

        $this->assertFalse($permissions->allows($stranger, 'course.view', $course));
        $this->assertNull($permissions->courseRole($stranger, $course));
    }

    public function test_an_assistant_can_grade_but_not_edit_the_course(): void
    {
        $course = Course::factory()->create();
        $assistant = User::factory()->instructor()->create();

        DB::table('course_user')->insert([
            'course_id' => $course->id,
            'user_id' => $assistant->id,
            'role' => CourseRole::Assistant->value,
            'status' => 'active',
            'progress' => 0,
            'enrolled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = app(PermissionService::class);

        $this->assertTrue($permissions->allows($assistant, 'submission.grade', $course));
        $this->assertFalse($permissions->allows($assistant, 'course.edit', $course));
        $this->assertFalse($permissions->allows($assistant, 'grade.manage', $course));
    }

    public function test_an_administrator_holds_every_capability(): void
    {
        $course = Course::factory()->create();
        $admin = User::factory()->admin()->create();

        $permissions = app(PermissionService::class);

        $this->assertTrue($permissions->allows($admin, 'course.edit', $course));
        $this->assertTrue($permissions->allows($admin, 'grade.export', $course));
    }
}
