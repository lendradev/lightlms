import { Head, router, useForm } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Role = { value: string; label: string };

type Invitation = {
    id: number;
    email: string;
    role: string;
    token: string;
    accepted_at: string | null;
    expires_at: string | null;
    created_at: string;
    inviter: { id: number; name: string } | null;
};

type Props = {
    invitations: { data: Invitation[] };
    roles: Role[];
};

export default function AdminInvitations({ invitations, roles }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: 'student',
        expires_in_days: 14,
    });

    return (
        <>
            <Head title="Invitations" />

            <AppShell title="Invitations">
                <div className="grid gap-6 lg:grid-cols-[22rem_1fr]">
                    <Card>
                        <CardHeader>
                            <CardTitle>Invite someone</CardTitle>
                            <CardDescription>
                                The invited person registers with this email and becomes the chosen role.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post('/admin/invitations', {
                                        preserveScroll: true,
                                        onSuccess: () => reset(),
                                    });
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(event) => setData('email', event.target.value)}
                                    />
                                    {errors.email && <p className="text-xs text-destructive">{errors.email}</p>}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="role">Role</Label>
                                    <Select
                                        value={data.role}
                                        onValueChange={(value) => setData('role', value)}
                                    >
                                        <SelectTrigger id="role">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((role) => (
                                                <SelectItem key={role.value} value={role.value}>
                                                    {role.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="expires_in_days">Valid for (days)</Label>
                                    <Input
                                        id="expires_in_days"
                                        type="number"
                                        min={1}
                                        max={365}
                                        value={data.expires_in_days}
                                        onChange={(event) =>
                                            setData('expires_in_days', Number(event.target.value))
                                        }
                                    />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    Create invitation
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Outstanding invitations</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {invitations.data.length === 0 ? (
                                <p className="text-sm text-muted-foreground">No invitations yet.</p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Email</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invitations.data.map((invitation) => (
                                            <TableRow key={invitation.id}>
                                                <TableCell className="font-medium">{invitation.email}</TableCell>
                                                <TableCell className="capitalize">{invitation.role}</TableCell>
                                                <TableCell>
                                                    {invitation.accepted_at ? (
                                                        <Badge variant="secondary">Accepted</Badge>
                                                    ) : (
                                                        <Badge variant="outline">Pending</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.delete(
                                                                `/admin/invitations/${invitation.id}`,
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                    >
                                                        Revoke
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </AppShell>
        </>
    );
}
