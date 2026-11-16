import { Head, useForm } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type RegistrationMode = { value: string; label: string };

type Props = {
    settings: Record<string, string>;
    registrationModes: RegistrationMode[];
};

export default function AdminSettings({ settings, registrationModes }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        site_name: settings.site_name ?? '',
        site_url: settings.site_url ?? '',
        registration: settings['auth.registration'] ?? 'invite',
        invite_code: settings['auth.invite_code'] ?? '',
        mail_from_address: settings['mail.from_address'] ?? '',
    });

    return (
        <>
            <Head title="Settings" />

            <AppShell title="Settings">
                <Card className="max-w-2xl">
                    <CardHeader>
                        <CardTitle>General</CardTitle>
                        <CardDescription>
                            These values apply across the site. Registration mode decides whether visitors can
                            sign up on their own.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={(event) => {
                                event.preventDefault();
                                put('/admin/settings', { preserveScroll: true });
                            }}
                            className="grid gap-5"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="site_name">Site name</Label>
                                <Input
                                    id="site_name"
                                    value={data.site_name}
                                    onChange={(event) => setData('site_name', event.target.value)}
                                />
                                {errors.site_name && (
                                    <p className="text-xs text-destructive">{errors.site_name}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="site_url">Site URL</Label>
                                <Input
                                    id="site_url"
                                    value={data.site_url}
                                    onChange={(event) => setData('site_url', event.target.value)}
                                />
                                {errors.site_url && (
                                    <p className="text-xs text-destructive">{errors.site_url}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="registration">Registration</Label>
                                <Select
                                    value={data.registration}
                                    onValueChange={(value) => setData('registration', value)}
                                >
                                    <SelectTrigger id="registration">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {registrationModes.map((mode) => (
                                            <SelectItem key={mode.value} value={mode.value}>
                                                {mode.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="invite_code">Shared invite code</Label>
                                <Input
                                    id="invite_code"
                                    value={data.invite_code}
                                    onChange={(event) => setData('invite_code', event.target.value)}
                                />
                                <p className="text-xs text-muted-foreground">
                                    When set, anyone with this code can register even in invitation-only mode.
                                </p>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="mail_from_address">Outgoing mail from</Label>
                                <Input
                                    id="mail_from_address"
                                    type="email"
                                    value={data.mail_from_address}
                                    onChange={(event) =>
                                        setData('mail_from_address', event.target.value)
                                    }
                                />
                            </div>

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Save settings
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </AppShell>
        </>
    );
}
