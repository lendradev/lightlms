import { Head, Link } from '@inertiajs/react';
import { Award, CircleCheck, Clock } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

type Certificate = {
    id: number;
    code: string;
    course: { id: number; title: string; slug: string };
    issued_at: string | null;
    revoked_at: string | null;
};

type Pending = {
    course: { id: number; title: string; slug: string };
    progress: number;
    grade: number | null;
    eligible: boolean;
    reason: string;
};

type Props = {
    certificates: Certificate[];
    pending: Pending[];
};

function formatDate(value: string | null) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'long' });
}

export default function LearningCertificates({ certificates, pending }: Props) {
    return (
        <>
            <Head title="Certificates" />
            <AppShell title="Certificates">
                <div className="mx-auto max-w-3xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Your certificates</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {certificates.length === 0 ? (
                                <div className="grid place-items-center py-12 text-center">
                                    <Award className="text-muted-foreground size-8" aria-hidden />
                                    <p className="mt-3 font-serif text-lg font-semibold">
                                        No certificates yet
                                    </p>
                                    <p className="text-muted-foreground mt-1 max-w-sm text-sm">
                                        Finish a course and its certificate appears here.
                                    </p>
                                </div>
                            ) : (
                                <ul className="divide-y">
                                    {certificates.map((certificate) => (
                                        <li key={certificate.id} className="py-4">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="font-serif text-base font-semibold">
                                                        {certificate.course.title}
                                                    </p>
                                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                                        Issued {formatDate(certificate.issued_at)} ·
                                                        Code{' '}
                                                        <span className="font-mono">
                                                            {certificate.code}
                                                        </span>
                                                    </p>
                                                </div>
                                                {certificate.revoked_at ? (
                                                    <Badge variant="destructive">Revoked</Badge>
                                                ) : (
                                                    <Badge>
                                                        <CircleCheck
                                                            className="size-3"
                                                            aria-hidden
                                                        />
                                                        Valid
                                                    </Badge>
                                                )}
                                            </div>
                                            <div className="mt-3 flex flex-wrap gap-2">
                                                <Link
                                                    href={`/certificates/${certificate.code}`}
                                                    className="hover:bg-accent focus-visible:outline-ring rounded-lg border px-3 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2"
                                                >
                                                    View certificate
                                                </Link>
                                                <Link
                                                    href={`/certificates/verify/${certificate.code}`}
                                                    className="hover:bg-accent focus-visible:outline-ring rounded-lg border px-3 py-1.5 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-offset-2"
                                                >
                                                    Verification page
                                                </Link>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    {pending.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle>In progress</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-5">
                                    {pending.map((item) => (
                                        <li key={item.course.id}>
                                            <div className="flex items-center justify-between gap-3">
                                                <p className="text-sm font-medium">
                                                    {item.course.title}
                                                </p>
                                                {item.grade !== null && (
                                                    <span className="text-muted-foreground text-xs">
                                                        {item.grade}%
                                                    </span>
                                                )}
                                            </div>
                                            <Progress value={item.progress} className="mt-2" />
                                            <p className="text-muted-foreground mt-1.5 flex items-center gap-1.5 text-xs">
                                                <Clock className="size-3" aria-hidden />
                                                {item.reason}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AppShell>
        </>
    );
}
