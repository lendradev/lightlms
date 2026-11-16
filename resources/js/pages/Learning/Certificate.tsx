import { Head, Link } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    certificate: {
        code: string;
        recipient_name: string;
        course_title: string;
        course_slug: string;
        issued_at: string | null;
        revoked_at: string | null;
        revocation_reason: string | null;
    };
};

function formatDate(value: string | null) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'long' });
}

export default function LearningCertificate({ certificate }: Props) {
    return (
        <>
            <Head title="Certificate" />
            <AppShell title="Certificate">
                <div className="mx-auto max-w-3xl">
                    <Card className="overflow-hidden">
                        <div className="bg-primary text-primary-foreground px-8 py-10 text-center">
                            <Award className="mx-auto size-8" aria-hidden />
                            <p className="mt-3 text-xs font-medium tracking-[0.18em] uppercase opacity-80">
                                Certificate of completion
                            </p>
                            <h1 className="mt-4 font-serif text-3xl leading-tight font-semibold text-balance">
                                {certificate.course_title}
                            </h1>
                        </div>
                        <CardContent className="grid gap-6 py-8 text-center">
                            <p className="text-muted-foreground text-sm">This certifies that</p>
                            <p className="font-serif text-2xl font-semibold">
                                {certificate.recipient_name}
                            </p>
                            <p className="text-muted-foreground text-sm">
                                completed the course on {formatDate(certificate.issued_at)}
                            </p>

                            {certificate.revoked_at && (
                                <Badge variant="destructive" className="mx-auto">
                                    Revoked
                                </Badge>
                            )}

                            <div className="bg-muted rounded-md border px-4 py-3">
                                <p className="text-muted-foreground text-xs">Verification code</p>
                                <p className="font-mono text-lg">{certificate.code}</p>
                            </div>

                            <div className="flex flex-wrap justify-center gap-2">
                                <Button asChild variant="outline">
                                    <Link href={`/certificates/verify/${certificate.code}`}>
                                        Public verification page
                                    </Link>
                                </Button>
                                <Button asChild variant="ghost">
                                    <Link href="/certificates">All certificates</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </AppShell>
        </>
    );
}
