import { Head, Link, useForm } from '@inertiajs/react';
import FlashMessage from '@/components/FlashMessage';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';

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
    const { t } = useTranslation();
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
                            {t('auth.signIn')} · {siteName}
                        </h1>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>{t('auth.signIn')}</CardTitle>
                            <CardDescription>{t('auth.welcomeBody')}</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <FlashMessage />
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/login');
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="email">{t('auth.email')}</Label>
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
                                    <div className="flex items-center justify-between">
                                        <Label htmlFor="password">{t('auth.password')}</Label>
                                        <Link
                                            href="/forgot-password"
                                            className="text-xs font-medium text-primary hover:underline"
                                        >
                                            {t('auth.forgot')}
                                        </Link>
                                    </div>
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
                                    {t('auth.remember')}
                                </label>

                                <Button type="submit" disabled={processing}>
                                    {processing ? '…' : t('auth.signIn')}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {registrationMode !== 'closed' && (
                        <p className="mt-6 text-center text-sm text-muted-foreground">
                            {t('auth.noAccount')}{' '}
                            <Link href="/register" className="font-medium text-primary hover:underline">
                                {t('auth.signUp')}
                            </Link>
                        </p>
                    )}
                </div>
            </main>
        </>
    );
}
