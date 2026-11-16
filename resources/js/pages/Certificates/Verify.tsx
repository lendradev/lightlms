import { Head } from '@inertiajs/react';
import { Award, CircleCheck, CircleX } from 'lucide-react';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { SharedProps } from '@/types';

type Props = {
    certificate: {
        code: string;
        course_title: string;
        issued_at: string | null;
        revoked_at: string | null;
        revocation_reason: string | null;
        recipient_name: string | null;
        valid: boolean;
    };
    auth: SharedProps['auth'];
};

function formatDate(value: string | null) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'long' });
}

export default function VerifyCertificate({ certificate, auth }: Props) {
    return (
        <>
            <Head title="Verify a certificate" />
            <SiteHeader user={auth.user} />
            <main className="container-page py-12">
                <div className="mx-auto max-w-2xl">
                    <Card>
                        <CardContent className="grid justify-items-center gap-4 py-12 text-center">
                            {certificate.valid ? (
                                <>
                                    <CircleCheck className="text-primary size-10" aria-hidden />
                                    <Badge>Valid certificate</Badge>
                                </>
                            ) : (
                                <>
                                    <CircleX className="text-destructive size-10" aria-hidden />
                                    <Badge variant="destructive">Revoked certificate</Badge>
                                </>
                            )}

                            <h1 className="font-serif text-2xl font-semibold">
                                {certificate.course_title}
                            </h1>

                            {certificate.valid && certificate.recipient_name && (
                                <p className="text-muted-foreground">
                                    Awarded to{' '}
                                    <span className="text-foreground font-medium">
                                        {certificate.recipient_name}
                                    </span>
                                </p>
                            )}

                            <p className="text-muted-foreground text-sm">
                                Issued {formatDate(certificate.issued_at)}
                            </p>

                            {!certificate.valid && certificate.revocation_reason && (
                                <p className="text-muted-foreground max-w-md text-sm">
                                    Reason: {certificate.revocation_reason}
                                </p>
                            )}

                            <div className="bg-muted mt-2 rounded-md border px-4 py-3">
                                <p className="text-muted-foreground text-xs">Verification code</p>
                                <p className="font-mono text-lg">{certificate.code}</p>
                            </div>

                            <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                <Award className="size-3" aria-hidden />
                                Verified by LightLMS
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </main>
            <SiteFooter />
        </>
    );
}
