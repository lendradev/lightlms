import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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

type User = {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
};

type Props = {
    users: {
        data: User[];
        links: { url: string | null; label: string; active: boolean }[];
        total: number;
    };
    filters: { q: string; role: string };
    roles: Role[];
};

export default function AdminUsers({ users, roles }: Props) {
    const [creating, setCreating] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        role: 'student',
    });

    const submit = () => {
        post('/admin/users', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setCreating(false);
            },
        });
    };

    return (
        <>
            <Head title="Users" />

            <AppShell
                title="Users"
                actions={
                    <Dialog open={creating} onOpenChange={setCreating}>
                        <DialogTrigger asChild>
                            <Button size="sm">Add user</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add user</DialogTitle>
                                <DialogDescription>
                                    Create an account directly. The password must be at least 12 characters.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(event) => setData('name', event.target.value)}
                                    />
                                    {errors.name && <p className="text-xs text-destructive">{errors.name}</p>}
                                </div>
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
                                    <Label htmlFor="password">Password</Label>
                                    <Input
                                        id="password"
                                        type="password"
                                        value={data.password}
                                        onChange={(event) => setData('password', event.target.value)}
                                    />
                                    {errors.password && (
                                        <p className="text-xs text-destructive">{errors.password}</p>
                                    )}
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
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setCreating(false)}>
                                    Cancel
                                </Button>
                                <Button onClick={submit} disabled={processing}>
                                    Create
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                }
            >
                <Card>
                    <CardHeader>
                        <CardTitle>All users</CardTitle>
                        <CardDescription>{users.total} accounts on this site.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">{user.name}</TableCell>
                                        <TableCell className="text-muted-foreground">{user.email}</TableCell>
                                        <TableCell>
                                            <Badge variant="outline" className="capitalize">
                                                {user.role}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.delete(`/admin/users/${user.id}`, {
                                                        preserveScroll: true,
                                                    })
                                                }
                                            >
                                                Delete
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {users.links.length > 1 && (
                            <nav className="mt-6 flex flex-wrap items-center gap-2" aria-label="Pagination">
                                {users.links.map((link, index) =>
                                    link.url === null ? (
                                        <span key={`empty-${index}`} className="px-3 py-2 text-sm text-muted-foreground">
                                            {link.label}
                                        </span>
                                    ) : (
                                        <Link
                                            key={`${link.label}-${index}`}
                                            href={link.url}
                                            preserveScroll
                                            className={
                                                link.active
                                                    ? 'rounded-lg bg-primary px-3 py-2 text-sm font-medium text-primary-foreground'
                                                    : 'rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted'
                                            }
                                        >
                                            {link.label}
                                        </Link>
                                    ),
                                )}
                            </nav>
                        )}
                    </CardContent>
                </Card>
            </AppShell>
        </>
    );
}
