<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\FileAsset;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
        Storage::fake('local');
    }

    private function assignmentFor(Course $course): Assignment
    {
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id, 'grade_max' => 100]);

        return Assignment::factory()->create(['lesson_id' => $lesson->id]);
    }

    public function test_a_learner_can_hand_in_an_attachment(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('map.pdf', 120, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
        ]);

        $file = FileAsset::query()->firstOrFail();

        $this->assertSame('map.pdf', $file->original_name);
        $this->assertSame('application/pdf', $file->mime_type);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_the_stored_name_does_not_come_from_the_upload(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('../../etc/passwd.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = FileAsset::query()->firstOrFail();

        $this->assertStringNotContainsString('..', $file->path);
        $this->assertStringNotContainsString('etc', $file->path);
        $this->assertStringNotContainsString('passwd', $file->path);
        $this->assertStringEndsWith('.pdf', $file->path);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_an_unsupported_type_is_refused(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('files', ['original_name' => 'payload.php']);
        $this->assertDatabaseMissing('submissions', ['assignment_id' => $assignment->id]);
    }

    public function test_a_file_that_is_too_large_is_refused(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('huge.pdf', 20 * 1024, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('files', ['original_name' => 'huge.pdf']);
    }

    public function test_a_student_outside_the_course_cannot_upload(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('map.pdf', 10, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('files', ['original_name' => 'map.pdf']);
    }

    public function test_an_instructor_can_attach_material_to_an_activity(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/lessons/{$lesson->id}/files", [
                'file' => UploadedFile::fake()->create('slides.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('files', [
            'lesson_id' => $lesson->id,
            'uploader_id' => $teacher->id,
            'original_name' => 'slides.pdf',
        ]);
    }

    public function test_an_instructor_cannot_attach_material_to_another_course(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $other = Course::factory()->create();
        $module = Module::factory()->create(['course_id' => $other->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/lessons/{$lesson->id}/files", [
                'file' => UploadedFile::fake()->create('slides.pdf', 200, 'application/pdf'),
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('files', ['original_name' => 'slides.pdf']);
    }

    public function test_the_owner_can_download_their_own_attachment(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('map.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = FileAsset::query()->firstOrFail();

        $this->actingAs($student)
            ->get("/files/{$file->id}")
            ->assertOk()
            ->assertDownload('map.pdf');
    }

    public function test_a_stranger_cannot_download_someone_elses_attachment(): void
    {
        $student = User::factory()->create();
        $stranger = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('map.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = FileAsset::query()->firstOrFail();

        $this->actingAs($stranger)
            ->get("/files/{$file->id}")
            ->assertForbidden();
    }

    public function test_an_instructor_can_download_a_hand_in_attachment(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", [
                'content' => 'My written answer goes here.',
                'file' => UploadedFile::fake()->create('map.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = FileAsset::query()->firstOrFail();

        $this->actingAs($teacher)
            ->get("/files/{$file->id}")
            ->assertOk()
            ->assertDownload('map.pdf');
    }

    public function test_the_upload_is_audited(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/lessons/{$lesson->id}/files", [
                'file' => UploadedFile::fake()->create('slides.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $teacher->id,
            'event' => 'file.create',
            'course_id' => $course->id,
        ]);
    }

    public function test_an_instructor_can_remove_material(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/lessons/{$lesson->id}/files", [
                'file' => UploadedFile::fake()->create('slides.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = FileAsset::query()->firstOrFail();
        $path = $file->path;

        $this->actingAs($teacher)
            ->delete("/teaching/{$course->slug}/files/{$file->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_a_submission_without_a_file_still_works(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $assignment = $this->assignmentFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", ['content' => 'Just text, no attachment.'])
            ->assertRedirect();

        $submission = Submission::query()->firstOrFail();

        $this->assertNull($submission->file_id);
    }
}
