import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    requiresInviteCode: boolean;
    inviteCode: string;
    invitedEmail?: string;
    invitedRole?: string;
    siteName: string;
};

type Form = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    invite_code: string;
};

export default function Register({
    requiresInviteCode,
    inviteCode,
    invitedEmail,
    siteName,
}: Props) {
    const { data, setData, post, processing, errors } = useForm<Form>({
        name: '',
        email: invitedEmail ?? '',
        password: '',
        password_confirmation: '',
        invite_code: inviteCode,
    });

    return (
        <>
            <Head title="Create an account" />

            <main className="flex min-h-screen items-center justify-center bg-background px-4 py-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <span className="mx-auto grid size-12 place-items-center rounded-xl bg-primary font-serif text-xl font-bold text-primary-foreground">
                            L
                        </span>
                        <h1 className="mt-5 font-serif text-2xl font-semibold tracking-tight text-foreground">
                            Join {siteName}
                        </h1>
                        {requiresInviteCode && (
                            <p className="mt-2 text-sm text-muted-foreground">
                                This site is invitation only. Enter the code from your invitation to register.
                            </p>
                        )}
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Create your account</CardTitle>
                            <CardDescription>You will be able to enrol in courses right away.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/register');
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        autoComplete="name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                    />
                                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        autoComplete="username"
                                        value={data.email}
                                        onChange={(event) => setData('email', event.target.value)}
                                    />
                                    {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                                </div>

                                {requiresInviteCode && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="invite_code">Invite code</Label>
                                        <Input
                                            id="invite_code"
                                            value={data.invite_code}
                                            onChange={(event) => setData('invite_code', event.target.value)}
                                        />
                                        {errors.invite_code && (
                                            <p className="text-xs text-destructive">{errors.invite_code}</p>
                                        )}
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        autoComplete="new-password"
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                    />
                                    {errors.password && <p className="text-xs text-destructive">{errors.password}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">Confirm password</Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        value={data.password_confirmation}
                                        onChange={(event) =>
                                            setData('password_confirmation', event.target.value)
                                        }
                                    />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Creating account…' : 'Create account'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <p className="mt-6 text-center text-sm text-muted-foreground">
                        Already have an account?{' '}
                        <Link href="/login" className="font-medium text-primary hover:underline">
                            Sign in
                        </Link>
                    </p>
                </div>
            </main>
        </>
    );
}
