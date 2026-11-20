import { Head, Link, useForm } from '@inertiajs/react';
import FlashMessage from '@/components/FlashMessage';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';

type Props = {
    email: string;
    token: string;
    siteName: string;
};

type Form = {
    email: string;
    token: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ email, token, siteName }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<Form>({
        email,
        token,
        password: '',
        password_confirmation: '',
    });

    return (
        <>
            <Head title={t('auth.resetTitle')} />
            <main className="flex min-h-screen items-center justify-center bg-background px-4 py-12">
                <div className="w-full max-w-sm">
                    <div className="mb-8 text-center">
                        <span className="mx-auto grid size-12 place-items-center rounded-xl bg-primary font-serif text-xl font-bold text-primary-foreground">
                            L
                        </span>
                        <h1 className="mt-5 font-serif text-2xl font-semibold tracking-tight text-foreground">
                            {t('auth.resetTitle')}
                        </h1>
                    </div>
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('auth.resetNewPassword')}</CardTitle>
                            <CardDescription>{t('auth.resetNewPasswordIntro')}</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <FlashMessage />
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/reset-password');
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="email">{t('auth.email')}</Label>
                                    <Input id="email" type="email" value={data.email} readOnly />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="password">{t('auth.resetNewPassword')}</Label>
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
                                    <Label htmlFor="password_confirmation">
                                        {t('auth.resetConfirmPassword')}
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        value={data.password_confirmation}
                                        onChange={(event) =>
                                            setData('password_confirmation', event.target.value)
                                        }
                                    />
                                    {errors.password_confirmation && (
                                        <p className="text-xs text-destructive">{errors.password_confirmation}</p>
                                    )}
                                </div>
                                <Button type="submit" disabled={processing}>
                                    {processing ? '…' : t('auth.resetSubmit')}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                    <p className="mt-6 text-center text-sm text-muted-foreground">
                        <Link href="/login" className="font-medium text-primary hover:underline">
                            {t('auth.resetBackToLogin')}
                        </Link>
                    </p>
                    <p className="mt-2 text-center text-xs text-muted-foreground">{siteName}</p>
                </div>
            </main>
        </>
    );
}
