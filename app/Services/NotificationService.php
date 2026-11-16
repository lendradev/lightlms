<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Delivers in-app notifications and honours each person's preferences.
 *
 * The events a person can switch off are rows in `notification_events`. A
 * preference row overrides the event default; when there is no row, the
 * default decides.
 */
class NotificationService
{
    /**
     * The events the application knows about, seeded into `notification_events`.
     *
     * @var array<string, array{label: string, category: string, channel: string, digestable: bool}>
     */
    public const EVENTS = [
        'submission.received' => ['label' => 'A learner hands in an assignment', 'category' => 'grading', 'channel' => 'both', 'digestable' => false],
        'submission.graded' => ['label' => 'Your assignment has been graded', 'category' => 'grading', 'channel' => 'both', 'digestable' => false],
        'quiz.graded' => ['label' => 'Your quiz attempt has been marked', 'category' => 'grading', 'channel' => 'both', 'digestable' => false],
        'enrolment.confirmed' => ['label' => 'You are enrolled in a course', 'category' => 'enrolment', 'channel' => 'both', 'digestable' => false],
        'course.published' => ['label' => 'A course you teach is published', 'category' => 'teaching', 'channel' => 'both', 'digestable' => false],
        'forum.reply' => ['label' => 'Someone replies to your discussion', 'category' => 'discussion', 'channel' => 'both', 'digestable' => true],
        'deadline.upcoming' => ['label' => 'An assignment deadline is near', 'category' => 'reminder', 'channel' => 'both', 'digestable' => true],
        'certificate.issued' => ['label' => 'Your certificate of completion is ready', 'category' => 'grading', 'channel' => 'both', 'digestable' => false],
    ];

    /**
     * Make sure every known event exists in the database.
     */
    public function syncEvents(): void
    {
        foreach (self::EVENTS as $key => $event) {
            NotificationEvent::query()->updateOrCreate(
                ['key' => $key],
                [
                    'label' => $event['label'],
                    'category' => $event['category'],
                    'channel' => $event['channel'],
                    'default_enabled' => true,
                    'is_digestable' => $event['digestable'],
                    'sort_order' => array_search($key, array_keys(self::EVENTS), true) + 1,
                ],
            );
        }
    }

    /**
     * Whether this person wants to hear about this event.
     */
    public function wants(User $user, string $eventKey, string $channel = 'inapp'): bool
    {
        $event = NotificationEvent::query()->where('key', $eventKey)->first();

        if ($event === null) {
            return false;
        }

        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_event_id', $event->id)
            ->where('channel', $channel)
            ->first();

        if ($preference !== null) {
            return (bool) $preference->enabled;
        }

        return (bool) $event->default_enabled;
    }

    /**
     * Send a notification, unless the person has switched it off.
     */
    public function notify(
        User $user,
        string $eventKey,
        string $message,
        ?string $link = null,
        array $data = [],
    ): ?Notification {
        if (! $this->wants($user, $eventKey)) {
            return null;
        }

        return Notification::query()->create([
            'user_id' => $user->id,
            'type' => $eventKey,
            'data' => array_merge(['message' => $message], $data),
            'link' => $link,
        ]);
    }

    /**
     * Send the same notification to many people, skipping those who opted out.
     *
     * @param  iterable<int, User>  $users
     */
    public function notifyMany(iterable $users, string $eventKey, string $message, ?string $link = null, array $data = []): int
    {
        $sent = 0;

        foreach ($users as $user) {
            if ($this->notify($user, $eventKey, $message, $link, $data) !== null) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * The inbox of one person, newest first.
     *
     * @return Collection<int, Notification>
     */
    public function inbox(User $user, int $limit = 50): Collection
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function unreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, Notification $notification): void
    {
        abort_unless($notification->user_id === $user->id, 403);

        $notification->update(['read_at' => now()]);
    }

    public function markAllRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
