import { Head, Link, useForm } from '@inertiajs/react';
import FlashMessage from '@/components/FlashMessage';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslation } from '@/lib/i18n';

type Props = {
    siteName: string;
};

type Form = {
    email: string;
};

export default function ForgotPassword({ siteName }: Props) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm<Form>({
        email: '',
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
                            <CardDescription>{t('auth.resetIntro')}</CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <FlashMessage />
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/forgot-password');
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
                                <Button type="submit" disabled={processing}>
                                    {processing ? '…' : t('auth.resetSend')}
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
