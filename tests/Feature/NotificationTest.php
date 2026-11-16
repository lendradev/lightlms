<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
        app(NotificationService::class)->syncEvents();
    }

    public function test_a_notification_is_delivered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/notifications')->assertOk();

        $notification = app(NotificationService::class)->notify(
            $user,
            'submission.graded',
            'Your submission has been graded.',
            '/learning/example',
        );

        $this->assertNotNull($notification);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'submission.graded',
            'read_at' => null,
        ]);
    }

    public function test_a_person_who_switched_an_event_off_is_not_notified(): void
    {
        $user = User::factory()->create();
        $event = NotificationEvent::query()->where('key', 'submission.graded')->firstOrFail();

        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'notification_event_id' => $event->id,
            'channel' => 'inapp',
            'enabled' => false,
        ]);

        $result = app(NotificationService::class)->notify(
            $user,
            'submission.graded',
            'Your submission has been graded.',
        );

        $this->assertNull($result);
        $this->assertDatabaseMissing('notifications', ['user_id' => $user->id]);
    }

    public function test_an_unknown_event_sends_nothing(): void
    {
        $user = User::factory()->create();

        $result = app(NotificationService::class)->notify($user, 'not.a.real.event', 'Hello');

        $this->assertNull($result);
    }

    public function test_grading_a_submission_notifies_the_learner(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id]);
        $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $submission = Submission::query()->create([
            'assignment_id' => $assignment->id,
            'user_id' => $student->id,
            'content' => 'The hand-in body.',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/assignments/{$assignment->id}/{$submission->id}/grade", ['score' => 80])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => 'submission.graded',
        ]);
    }

    public function test_handing_in_notifies_the_teacher(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->assignment()->create(['module_id' => $module->id]);
        $assignment = Assignment::factory()->create(['lesson_id' => $lesson->id]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->post("/assignments/{$assignment->id}", ['content' => 'My written answer goes here.'])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher->id,
            'type' => 'submission.received',
        ]);
    }

    public function test_the_inbox_lists_notifications_newest_first(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);

        $service->notify($user, 'submission.graded', 'First message.');
        $service->notify($user, 'quiz.graded', 'Second message.');

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Notifications/Index')->has('items', 2));
    }

    public function test_a_guest_cannot_reach_the_inbox(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_opening_a_notification_marks_it_read(): void
    {
        $user = User::factory()->create();
        $notification = app(NotificationService::class)->notify(
            $user,
            'submission.graded',
            'Your submission has been graded.',
            '/learning/example',
        );

        $this->actingAs($user)
            ->get("/notifications/{$notification->id}/read")
            ->assertRedirect('/learning/example');

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_marking_all_read_clears_the_badge(): void
    {
        $user = User::factory()->create();
        $service = app(NotificationService::class);
        $service->notify($user, 'submission.graded', 'First message.');
        $service->notify($user, 'quiz.graded', 'Second message.');

        $this->assertSame(2, $service->unreadCount($user));

        $this->actingAs($user)->post('/notifications/read-all')->assertRedirect();

        $this->assertSame(0, $service->unreadCount($user));
    }

    public function test_a_person_cannot_read_someone_elses_notification(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $notification = app(NotificationService::class)->notify(
            $user,
            'submission.graded',
            'Your submission has been graded.',
        );

        $this->actingAs($stranger)
            ->get("/notifications/{$notification->id}/read")
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_the_preference_screen_lists_every_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/notifications/preferences')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Notifications/Preferences')->has('events', count(NotificationService::EVENTS)));
    }

    public function test_preferences_can_be_switched_off_and_saved(): void
    {
        $user = User::factory()->create();
        $event = NotificationEvent::query()->where('key', 'submission.graded')->firstOrFail();

        $this->actingAs($user)
            ->post('/notifications/preferences', [
                'preferences' => [
                    ['event_id' => $event->id, 'channel' => 'inapp', 'enabled' => false],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->id,
            'notification_event_id' => $event->id,
            'channel' => 'inapp',
            'enabled' => false,
        ]);

        $this->assertFalse(app(NotificationService::class)->wants($user, 'submission.graded'));
    }

    public function test_a_preference_for_one_channel_does_not_silence_the_other(): void
    {
        $user = User::factory()->create();
        $event = NotificationEvent::query()->where('key', 'submission.graded')->firstOrFail();

        NotificationPreference::query()->create([
            'user_id' => $user->id,
            'notification_event_id' => $event->id,
            'channel' => 'email',
            'enabled' => false,
        ]);

        $service = app(NotificationService::class);

        $this->assertFalse($service->wants($user, 'submission.graded', 'email'));
        $this->assertTrue($service->wants($user, 'submission.graded', 'inapp'));
    }

    public function test_the_unread_count_is_shared_with_the_page(): void
    {
        $user = User::factory()->create();
        app(NotificationService::class)->notify($user, 'submission.graded', 'A message.');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('notifications.unread', 1));
    }

    public function test_the_events_are_seeded_once(): void
    {
        app(NotificationService::class)->syncEvents();
        app(NotificationService::class)->syncEvents();

        $this->assertSame(
            count(NotificationService::EVENTS),
            NotificationEvent::query()->count(),
        );
    }

    public function test_a_notification_keeps_its_message_and_link(): void
    {
        $user = User::factory()->create();

        $notification = app(NotificationService::class)->notify(
            $user,
            'submission.received',
            'Sam Student handed in Map a system.',
            '/teaching/example/assignments/1',
            ['assignment_id' => 7],
        );

        $this->assertSame('Sam Student handed in Map a system.', $notification->data['message']);
        $this->assertSame('/teaching/example/assignments/1', $notification->link);
        $this->assertSame(7, $notification->data['assignment_id']);
    }

    public function test_a_notification_appears_in_the_inbox_of_its_owner_only(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        app(NotificationService::class)->notify($user, 'submission.graded', 'A message.');

        $this->actingAs($stranger)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('items', 0));

        $this->actingAs($user)
            ->get('/notifications')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('items', 1));

        $this->assertSame(1, Notification::query()->count());
    }
}
