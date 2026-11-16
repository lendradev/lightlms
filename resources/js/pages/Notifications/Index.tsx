import { Head, Link, router } from '@inertiajs/react';
import { Bell, CheckCheck } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Item = {
    id: number;
    type: string;
    message: string;
    link: string | null;
    read_at: string | null;
    created_at: string | null;
};

type Props = {
    items: Item[];
    unread: number;
};

function formatDate(value: string | null) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

export default function NotificationsIndex({ items, unread }: Props) {
    return (
        <>
            <Head title="Notifications" />

            <AppShell
                title="Notifications"
                actions={
                    unread > 0 ? (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                router.post('/notifications/read-all', {}, { preserveScroll: true })
                            }
                        >
                            <CheckCheck className="size-4" aria-hidden />
                            Mark all read
                        </Button>
                    ) : undefined
                }
            >
                <div className="mx-auto max-w-3xl space-y-6">
                    {items.length === 0 ? (
                        <Card>
                            <CardContent className="grid place-items-center py-16 text-center">
                                <Bell
                                    className="size-8 text-muted-foreground"
                                    aria-hidden
                                />
                                <p className="mt-3 font-serif text-lg font-semibold">
                                    Nothing here yet
                                </p>
                                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                    When a submission is graded, a course is published, or someone
                                    replies to you, it shows up here.
                                </p>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardContent className="p-0">
                                <ul className="divide-y">
                                    {items.map((item) => (
                                        <li key={item.id}>
                                            <Link
                                                href={`/notifications/${item.id}/read`}
                                                className="flex items-start gap-3 px-5 py-4 transition hover:bg-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                            >
                                                <span
                                                    className={`mt-1.5 size-2 shrink-0 rounded-full ${
                                                        item.read_at ? 'bg-border' : 'bg-primary'
                                                    }`}
                                                    aria-hidden
                                                />
                                                <span className="min-w-0 flex-1">
                                                    <span className="block text-sm">
                                                        {item.message}
                                                    </span>
                                                    <span className="mt-0.5 block text-xs text-muted-foreground">
                                                        {formatDate(item.created_at)}
                                                    </span>
                                                </span>
                                                {!item.read_at && (
                                                    <Badge variant="secondary">New</Badge>
                                                )}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AppShell>
        </>
    );
}
