import { Head, useForm } from '@inertiajs/react';
import { Award, Ban, CircleCheck } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

type Rule = {
    type: string;
    label: string;
    minimum_percent: number | null;
    enabled: boolean;
};

type RuleType = { value: string; label: string };

type Learner = {
    id: number;
    name: string;
    email: string;
    progress: number;
    grade: number | null;
    eligible: boolean;
    reason: string;
    certificate: {
        id: number;
        code: string;
        issued_at: string | null;
        revoked_at: string | null;
    } | null;
};

type Props = {
    course: { id: number; title: string; slug: string };
    rule: Rule;
    ruleTypes: RuleType[];
    learners: Learner[];
};

function formatDate(value: string | null) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function TeachingCertificates({ course, rule, ruleTypes, learners }: Props) {
    const ruleForm = useForm({
        rule_type: rule.type,
        minimum_percent: rule.minimum_percent ?? 70,
        enabled: rule.enabled,
    });
    const issueForm = useForm<{ user_id: string }>({ user_id: '' });
    const revokeForm = useForm<{ reason: string }>({ reason: '' });
    const [revoking, setRevoking] = useState<number | null>(null);

    const saveRule = () => {
        ruleForm.put(`/teaching/${course.slug}/certificates/rule`, { preserveScroll: true });
    };

    const issue = (userId: number) => {
        issueForm.setData('user_id', String(userId));
        issueForm.post(`/teaching/${course.slug}/certificates`, { preserveScroll: true });
    };

    const revoke = (certificateId: number) => {
        revokeForm.delete(`/teaching/${course.slug}/certificates/${certificateId}`, {
            preserveScroll: true,
        });
        setRevoking(null);
    };

    return (
        <>
            <Head title={`Certificates · ${course.title}`} />
            <AppShell title="Certificates">
                <div className="mx-auto max-w-4xl space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Completion rule</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="rule_type">A learner is finished when</Label>
                                <Select
                                    value={ruleForm.data.rule_type}
                                    onValueChange={(value) => ruleForm.setData('rule_type', value)}
                                >
                                    <SelectTrigger id="rule_type">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {ruleTypes.map((type) => (
                                            <SelectItem key={type.value} value={type.value}>
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            {ruleForm.data.rule_type === 'grade_minimum' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="minimum_percent">Minimum grade (%)</Label>
                                    <Input
                                        id="minimum_percent"
                                        type="number"
                                        min={0}
                                        max={100}
                                        value={ruleForm.data.minimum_percent}
                                        onChange={(event) =>
                                            ruleForm.setData(
                                                'minimum_percent',
                                                Number(event.target.value),
                                            )
                                        }
                                    />
                                </div>
                            )}

                            <div className="flex items-center justify-between rounded-md border px-4 py-3">
                                <Label htmlFor="enabled">Issue certificates for this course</Label>
                                <Switch
                                    id="enabled"
                                    checked={ruleForm.data.enabled}
                                    onCheckedChange={(checked) =>
                                        ruleForm.setData('enabled', checked)
                                    }
                                />
                            </div>

                            <Button type="button" onClick={saveRule} disabled={ruleForm.processing}>
                                Save rule
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Learners</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {learners.length === 0 ? (
                                <p className="text-muted-foreground py-8 text-center text-sm">
                                    Nobody is enrolled in this course yet.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {learners.map((learner) => (
                                        <li
                                            key={learner.id}
                                            className="flex flex-wrap items-center gap-3 py-4"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium">
                                                    {learner.name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {learner.progress}% complete
                                                    {learner.grade !== null &&
                                                        ` · ${learner.grade}%`}{' '}
                                                    · {learner.reason}
                                                </p>
                                                {learner.certificate && (
                                                    <p className="text-muted-foreground mt-1 font-mono text-xs">
                                                        {learner.certificate.code} · issued{' '}
                                                        {formatDate(learner.certificate.issued_at)}
                                                    </p>
                                                )}
                                            </div>

                                            {learner.certificate === null && (
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => issue(learner.id)}
                                                    disabled={issueForm.processing}
                                                >
                                                    <Award className="size-4" aria-hidden />
                                                    Issue
                                                </Button>
                                            )}

                                            {learner.certificate &&
                                                !learner.certificate.revoked_at && (
                                                    <>
                                                        <Badge>
                                                            <CircleCheck
                                                                className="size-3"
                                                                aria-hidden
                                                            />
                                                            Issued
                                                        </Badge>
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="ghost"
                                                            onClick={() =>
                                                                setRevoking(learner.certificate!.id)
                                                            }
                                                        >
                                                            <Ban className="size-4" aria-hidden />
                                                            Revoke
                                                        </Button>
                                                    </>
                                                )}

                                            {learner.certificate?.revoked_at && (
                                                <Badge variant="destructive">Revoked</Badge>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    {revoking !== null && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Revoke this certificate</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="reason">Reason</Label>
                                    <Input
                                        id="reason"
                                        value={revokeForm.data.reason}
                                        onChange={(event) =>
                                            revokeForm.setData('reason', event.target.value)
                                        }
                                        placeholder="The work was found to be plagiarised."
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        onClick={() => revoke(revoking)}
                                        disabled={
                                            revokeForm.data.reason.length < 3 ||
                                            revokeForm.processing
                                        }
                                    >
                                        Revoke certificate
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={() => setRevoking(null)}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AppShell>
        </>
    );
}
