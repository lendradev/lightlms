<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');

        File::deleteDirectory(storage_path('app/backups'));
    }

    public function test_a_student_cannot_reach_the_admin_area(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/admin/users')->assertForbidden();
    }

    public function test_an_admin_can_list_and_create_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Users/Index'));

        $this->actingAs($admin)
            ->post('/admin/users', [
                'name' => 'New Instructor',
                'email' => 'teacher@example.test',
                'password' => 'a-very-long-password',
                'role' => 'instructor',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'teacher@example.test',
            'role' => 'instructor',
        ]);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete("/admin/users/{$admin->id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_an_admin_can_change_the_registration_mode(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put('/admin/settings', [
                'site_name' => 'Acme Academy',
                'site_url' => 'https://lms.test',
                'registration' => Setup::REGISTRATION_OPEN,
                'invite_code' => '',
            ])
            ->assertRedirect();

        $this->assertSame(Setup::REGISTRATION_OPEN, Setup::registrationMode());
        $this->assertSame('Acme Academy', Setting::get(Setup::KEY_SITE_NAME));
    }

    public function test_an_admin_can_create_an_invitation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post('/admin/invitations', [
                'email' => 'guest@example.test',
                'role' => 'student',
                'expires_in_days' => 7,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invitations', ['email' => 'guest@example.test']);
    }

    public function test_a_backup_can_be_created_and_lists_in_the_admin_area(): void
    {
        $admin = User::factory()->admin()->create();
        Course::factory()->create(['title' => 'Backed up course']);

        $this->actingAs($admin)
            ->post('/admin/backup')
            ->assertRedirect();

        $this->actingAs($admin)
            ->get('/admin/backup')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Backup/Index')->has('backups', 1));
    }

    public function test_a_backup_round_trip_restores_the_data(): void
    {
        $admin = User::factory()->admin()->create();
        $course = Course::factory()->create(['title' => 'Survives the round trip']);

        $this->actingAs($admin)->post('/admin/backup')->assertRedirect();

        $archives = glob(storage_path('app/backups/lightlms-*'));
        $this->assertNotEmpty($archives);

        Course::query()->where('title', 'Survives the round trip')->delete();

        $this->actingAs($admin)
            ->post('/admin/backup/restore', ['archive' => $archives[0]])
            ->assertRedirect();

        $this->assertDatabaseHas('courses', ['title' => 'Survives the round trip']);
    }
}
