import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';

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
    const { t } = useTranslation();
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
                            <CardTitle>{t('auth.signUp')}</CardTitle>
                            <CardDescription>{t('auth.registerBody')}</CardDescription>
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
                                    <Label htmlFor="name">{t('auth.name')}</Label>
                                    <Input
                                        id="name"
                                        autoComplete="name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                        aria-invalid={errors.name ? true : undefined}
                                        aria-describedby={errors.name ? 'name-error' : undefined}
                                    />
                                    {errors.name && (
                                        <p
                                            id="name-error"
                                            role="alert"
                                            className="text-xs text-destructive"
                                        >
                                            {errors.name}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">{t('auth.email')}</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        autoComplete="username"
                                        value={data.email}
                                        onChange={(event) => setData('email', event.target.value)}
                                        aria-invalid={errors.email ? true : undefined}
                                        aria-describedby={errors.email ? 'email-error' : undefined}
                                    />
                                    {errors.email && (
                                        <p
                                            id="email-error"
                                            role="alert"
                                            className="text-xs text-destructive"
                                        >
                                            {errors.email}
                                        </p>
                                    )}
                                </div>

                                {requiresInviteCode && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="invite_code">{t('auth.inviteCode')}</Label>
                                        <Input
                                            id="invite_code"
                                            value={data.invite_code}
                                            onChange={(event) => setData('invite_code', event.target.value)}
                                            aria-invalid={errors.invite_code ? true : undefined}
                                            aria-describedby={
                                                errors.invite_code ? 'invite-code-error' : undefined
                                            }
                                        />
                                        {errors.invite_code && (
                                            <p
                                                id="invite-code-error"
                                                role="alert"
                                                className="text-xs text-destructive"
                                            >
                                                {errors.invite_code}
                                            </p>
                                        )}
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="password">{t('auth.password')}</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        autoComplete="new-password"
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                        aria-invalid={errors.password ? true : undefined}
                                        aria-describedby={
                                            errors.password ? 'password-error' : undefined
                                        }
                                    />
                                    {errors.password && (
                                        <p
                                            id="password-error"
                                            role="alert"
                                            className="text-xs text-destructive"
                                        >
                                            {errors.password}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        {t('auth.confirmPassword')}
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        value={data.password_confirmation}
                                        onChange={(event) =>
                                            setData('password_confirmation', event.target.value)
                                        }
                                        aria-invalid={
                                            errors.password_confirmation ? true : undefined
                                        }
                                        aria-describedby={
                                            errors.password_confirmation
                                                ? 'password-confirmation-error'
                                                : undefined
                                        }
                                    />
                                    {errors.password_confirmation && (
                                        <p
                                            id="password-confirmation-error"
                                            role="alert"
                                            className="text-xs text-destructive"
                                        >
                                            {errors.password_confirmation}
                                        </p>
                                    )}
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
