import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Props = {
    registrationMode: string;
    siteName: string;
};

type Form = {
    email: string;
    password: string;
    remember: boolean;
};

export default function Login({ registrationMode, siteName }: Props) {
    const { data, setData, post, processing, errors } = useForm<Form>({
        email: '',
        password: '',
        remember: false,
    });

    return (
        <>
            <Head title="Sign in" />

            <main className="flex min-h-screen items-center justify-center bg-background px-4 py-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <span className="mx-auto grid size-12 place-items-center rounded-xl bg-primary font-serif text-xl font-bold text-primary-foreground">
                            L
                        </span>
                        <h1 className="mt-5 font-serif text-2xl font-semibold tracking-tight text-foreground">
                            Sign in to {siteName}
                        </h1>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Sign in</CardTitle>
                            <CardDescription>Use your account to reach your courses.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/login');
                                }}
                                className="grid gap-4"
                            >
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

                                <div className="grid gap-2">
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        autoComplete="current-password"
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                    />
                                    {errors.password && (
                                        <p className="text-xs text-destructive">{errors.password}</p>
                                    )}
                                </div>

                                <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(event) => setData('remember', event.target.checked)}
                                        className="size-4 rounded border-input accent-primary"
                                    />
                                    Keep me signed in
                                </label>

                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Signing in…' : 'Sign in'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {registrationMode !== 'closed' && (
                        <p className="mt-6 text-center text-sm text-muted-foreground">
                            Need an account?{' '}
                            <Link href="/register" className="font-medium text-primary hover:underline">
                                Register
                            </Link>
                        </p>
                    )}
                </div>
            </main>
        </>
    );
}
