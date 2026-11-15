import { Head, useForm } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
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

type Requirement = {
    label: string;
    ok: boolean;
    hint: string;
};

type Props = {
    requirements: Requirement[];
    hasAdmin: boolean;
};

type Form = {
    site_name: string;
    site_url: string;
    admin_name: string;
    admin_email: string;
    admin_password: string;
    admin_password_confirmation: string;
    registration: string;
    invite_code: string;
};

export default function SetupWelcome({ requirements }: Props) {
    const allOk = requirements.every((requirement) => requirement.ok);

    const { data, setData, post, processing, errors } = useForm<Form>({
        site_name: 'LightLMS',
        site_url: typeof window !== 'undefined' ? window.location.origin : '',
        admin_name: '',
        admin_email: '',
        admin_password: '',
        admin_password_confirmation: '',
        registration: 'invite',
        invite_code: '',
    });

    return (
        <>
            <Head title="Set up LightLMS" />

            <main className="min-h-screen bg-background px-4 py-12">
                <div className="mx-auto w-full max-w-3xl">
                    <div className="mb-8 text-center">
                        <span className="mx-auto grid size-12 place-items-center rounded-xl bg-primary font-serif text-xl font-bold text-primary-foreground">
                            L
                        </span>
                        <h1 className="mt-5 font-serif text-3xl font-semibold tracking-tight text-foreground">
                            Set up LightLMS
                        </h1>
                        <p className="mt-2 text-sm text-muted-foreground">
                            Check the requirements, create your administrator account, and choose how people
                            join.
                        </p>
                    </div>

                    <Card className="gap-4">
                        <CardHeader>
                            <CardTitle>System requirements</CardTitle>
                            <CardDescription>
                                LightLMS needs a PHP runtime, a database connection and a writable storage
                                directory.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <ul className="divide-y">
                                {requirements.map((requirement) => (
                                    <li key={requirement.label} className="flex items-center justify-between gap-4 py-3">
                                        <div>
                                            <p className="text-sm font-medium text-foreground">{requirement.label}</p>
                                            <p className="text-xs text-muted-foreground">{requirement.hint}</p>
                                        </div>
                                        <Badge variant={requirement.ok ? 'secondary' : 'destructive'}>
                                            {requirement.ok ? 'OK' : 'Missing'}
                                        </Badge>
                                    </li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>

                    <Card className="mt-6 gap-4">
                        <CardHeader>
                            <CardTitle>Site and administrator</CardTitle>
                            <CardDescription>
                                This account has full access to courses, users and settings.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/setup');
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-4 sm:grid-cols-2">
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
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="admin_name">Your name</Label>
                                        <Input
                                            id="admin_name"
                                            value={data.admin_name}
                                            onChange={(event) => setData('admin_name', event.target.value)}
                                        />
                                        {errors.admin_name && (
                                            <p className="text-xs text-destructive">{errors.admin_name}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="admin_email">Your email</Label>
                                        <Input
                                            id="admin_email"
                                            type="email"
                                            value={data.admin_email}
                                            onChange={(event) => setData('admin_email', event.target.value)}
                                        />
                                        {errors.admin_email && (
                                            <p className="text-xs text-destructive">{errors.admin_email}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="admin_password">Password</Label>
                                        <Input
                                            id="admin_password"
                                            type="password"
                                            value={data.admin_password}
                                            onChange={(event) =>
                                                setData('admin_password', event.target.value)
                                            }
                                        />
                                        {errors.admin_password && (
                                            <p className="text-xs text-destructive">{errors.admin_password}</p>
                                        )}
                                        <p className="text-xs text-muted-foreground">
                                            At least 12 characters.
                                        </p>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="admin_password_confirmation">
                                            Confirm password
                                        </Label>
                                        <Input
                                            id="admin_password_confirmation"
                                            type="password"
                                            value={data.admin_password_confirmation}
                                            onChange={(event) =>
                                                setData('admin_password_confirmation', event.target.value)
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="registration">Who can join?</Label>
                                        <Select
                                            value={data.registration}
                                            onValueChange={(value) => setData('registration', value)}
                                        >
                                            <SelectTrigger id="registration">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="open">Anyone can sign up</SelectItem>
                                                <SelectItem value="invite">Invitation only</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="invite_code">Invite code (optional)</Label>
                                        <Input
                                            id="invite_code"
                                            value={data.invite_code}
                                            onChange={(event) => setData('invite_code', event.target.value)}
                                        />
                                        <p className="text-xs text-muted-foreground">
                                            Shared code that lets anyone register once.
                                        </p>
                                    </div>
                                </div>

                                <div className="flex justify-end pt-2">
                                    <Button type="submit" disabled={!allOk || processing}>
                                        {processing ? 'Installing…' : 'Install LightLMS'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </>
    );
}
