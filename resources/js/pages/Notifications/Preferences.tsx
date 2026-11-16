import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

type Event = {
    id: number;
    key: string;
    label: string;
    category: string;
    default_enabled: boolean;
    inapp: boolean;
    email: boolean;
};

type Props = {
    events: Event[];
};

type Preference = {
    event_id: number;
    channel: 'inapp' | 'email';
    enabled: boolean;
};

const CATEGORY_LABELS: Record<string, string> = {
    grading: 'Grading',
    enrolment: 'Enrolment',
    teaching: 'Teaching',
    discussion: 'Discussion',
    reminder: 'Reminders',
    system: 'System',
};

export default function NotificationPreferences({ events }: Props) {
    const [preferences, setPreferences] = useState<Record<string, boolean>>(
        Object.fromEntries(
            events.flatMap((event) => [
                [`${event.id}|inapp`, event.inapp],
                [`${event.id}|email`, event.email],
            ]),
        ),
    );

    const form = useForm<{ preferences: Preference[] }>({ preferences: [] });

    const toggle = (eventId: number, channel: 'inapp' | 'email', enabled: boolean) => {
        setPreferences((previous) => ({ ...previous, [`${eventId}|${channel}`]: enabled }));
    };

    const save = () => {
        form.setData(
            'preferences',
            events.flatMap((event) =>
                (['inapp', 'email'] as const).map((channel) => ({
                    event_id: event.id,
                    channel,
                    enabled: preferences[`${event.id}|${channel}`] ?? false,
                })),
            ),
        );

        form.post('/notifications/preferences', { preserveScroll: true });
    };

    const grouped = events.reduce<Record<string, Event[]>>((groups, event) => {
        groups[event.category] = [...(groups[event.category] ?? []), event];
        return groups;
    }, {});

    return (
        <>
            <Head title="Notification preferences" />

            <AppShell title="Notification preferences">
                <div className="mx-auto max-w-3xl space-y-6">
                    <p className="text-sm text-muted-foreground">
                        Choose what you want to hear about. In-app notifications appear in the bell
                        menu. Email notifications are sent to your address.
                    </p>

                    {Object.entries(grouped).map(([category, categoryEvents]) => (
                        <Card key={category}>
                            <CardHeader>
                                <CardTitle className="text-sm">
                                    {CATEGORY_LABELS[category] ?? category}
                                </CardTitle>
                                <CardDescription>
                                    {categoryEvents.length} event
                                    {categoryEvents.length === 1 ? '' : 's'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y">
                                    {categoryEvents.map((event) => (
                                        <li
                                            key={event.id}
                                            className="flex flex-wrap items-center justify-between gap-4 py-3"
                                        >
                                            <div className="min-w-0">
                                                <Label
                                                    htmlFor={`event-${event.id}-inapp`}
                                                    className="text-sm font-medium"
                                                >
                                                    {event.label}
                                                </Label>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    {event.key}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-6">
                                                <div className="flex items-center gap-2">
                                                    <Switch
                                                        id={`event-${event.id}-inapp`}
                                                        checked={
                                                            preferences[`${event.id}|inapp`] ?? false
                                                        }
                                                        onCheckedChange={(checked) =>
                                                            toggle(event.id, 'inapp', checked)
                                                        }
                                                    />
                                                    <span className="text-xs text-muted-foreground">
                                                        In app
                                                    </span>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Switch
                                                        id={`event-${event.id}-email`}
                                                        checked={
                                                            preferences[`${event.id}|email`] ?? false
                                                        }
                                                        onCheckedChange={(checked) =>
                                                            toggle(event.id, 'email', checked)
                                                        }
                                                    />
                                                    <span className="text-xs text-muted-foreground">
                                                        Email
                                                    </span>
                                                </div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    ))}

                    <Button onClick={save} disabled={form.processing}>
                        <Save className="size-4" aria-hidden />
                        Save preferences
                    </Button>
                </div>
            </AppShell>
        </>
    );
}
