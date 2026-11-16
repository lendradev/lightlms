<?php

namespace Tests\Unit;

use App\Enums\CourseRole;
use PHPUnit\Framework\TestCase;

class CourseRoleTest extends TestCase
{
    public function test_only_the_instructor_can_edit_the_course(): void
    {
        $this->assertTrue(CourseRole::Instructor->canTeach());
        $this->assertTrue(CourseRole::Assistant->canTeach());
        $this->assertFalse(CourseRole::Student->canTeach());
    }

    public function test_the_instructor_holds_every_permission_the_assistant_holds(): void
    {
        $assistant = CourseRole::Assistant->permissions();
        $instructor = CourseRole::Instructor->permissions();

        foreach ($assistant as $permission) {
            $this->assertContains($permission, $instructor);
        }
    }

    public function test_a_student_cannot_grade_or_manage_a_course(): void
    {
        $permissions = CourseRole::Student->permissions();

        $this->assertNotContains('submission.grade', $permissions);
        $this->assertNotContains('grade.edit', $permissions);
        $this->assertNotContains('course.edit', $permissions);
        $this->assertContains('course.view', $permissions);
    }
}
