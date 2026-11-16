<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationEvent;
use App\Models\NotificationPreference;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The in-app inbox and the preference screen.
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Notifications/Index', [
            'items' => $this->notifications->inbox($user)->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $notification->data['message'] ?? $notification->type,
                'link' => $notification->link,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ])->values(),
            'unread' => $this->notifications->unreadCount($user),
        ]);
    }

    public function read(Request $request, Notification $notification): RedirectResponse
    {
        $this->notifications->markRead($request->user(), $notification);

        return redirect($notification->link ?? route('notifications.index'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * The preference screen: one switch per event, per channel.
     */
    public function preferences(Request $request): Response
    {
        $user = $request->user();

        $this->notifications->syncEvents();

        $events = NotificationEvent::query()->orderBy('sort_order')->get();
        $preferences = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (NotificationPreference $preference) => $preference->notification_event_id.'|'.$preference->channel);

        return Inertia::render('Notifications/Preferences', [
            'events' => $events->map(fn (NotificationEvent $event) => [
                'id' => $event->id,
                'key' => $event->key,
                'label' => $event->label,
                'category' => $event->category,
                'default_enabled' => (bool) $event->default_enabled,
                'inapp' => $this->enabledFor($preferences, $event->id, 'inapp', (bool) $event->default_enabled),
                'email' => $this->enabledFor($preferences, $event->id, 'email', (bool) $event->default_enabled),
            ])->values(),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.event_id' => ['required', 'integer', 'exists:notification_events,id'],
            'preferences.*.channel' => ['required', 'in:inapp,email'],
            'preferences.*.enabled' => ['required', 'boolean'],
        ]);

        foreach ($validated['preferences'] as $preference) {
            NotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $request->user()->id,
                    'notification_event_id' => $preference['event_id'],
                    'channel' => $preference['channel'],
                ],
                ['enabled' => (bool) $preference['enabled']],
            );
        }

        return back()->with('success', 'Notification preferences saved.');
    }

    /**
     * @param  Collection<string, NotificationPreference>  $preferences
     */
    private function enabledFor($preferences, int $eventId, string $channel, bool $default): bool
    {
        $preference = $preferences->get($eventId.'|'.$channel);

        return $preference === null ? $default : (bool) $preference->enabled;
    }
}
