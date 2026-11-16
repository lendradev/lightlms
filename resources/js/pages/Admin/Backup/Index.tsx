import { Head, Link, router } from '@inertiajs/react';
import { useRef } from 'react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Backup = {
    name: string;
    size: number;
    created_at: string;
};

type Props = {
    backups: Backup[];
};

export default function AdminBackup({ backups }: Props) {
    const formRef = useRef<HTMLFormElement>(null);

    return (
        <>
            <Head title="Backup" />

            <AppShell title="Backup and restore">
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Create a backup</CardTitle>
                            <CardDescription>
                                Produces a ZIP archive with a database dump and the uploaded files. Download it
                                before making large changes.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                onClick={() =>
                                    router.post(
                                        '/admin/backup',
                                        {},
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => router.reload({ only: ['backups'] }),
                                        },
                                    )
                                }
                            >
                                Create backup now
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Restore a backup</CardTitle>
                            <CardDescription className="text-destructive">
                                Restoring replaces the current data with the contents of the archive. This cannot
                                be undone.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                ref={formRef}
                                encType="multipart/form-data"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    const form = formRef.current;
                                    if (!form) return;
                                    router.post(
                                        '/admin/backup/restore',
                                        form,
                                        {
                                            forceFormData: true,
                                            onSuccess: () =>
                                                router.reload({ only: ['backups'] }),
                                        },
                                    );
                                }}
                                className="grid gap-4"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="archive">Backup archive</Label>
                                    <Input id="archive" type="file" name="archive" accept=".zip" required />
                                </div>
                                <div className="flex justify-end">
                                    <Button type="submit" variant="destructive">
                                        Restore
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Stored backups</CardTitle>
                        <CardDescription>
                            Backups live in <code>storage/app/backups</code> on the server.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {backups.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No backups on this server yet.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>File</TableHead>
                                        <TableHead>Size</TableHead>
                                        <TableHead>Created</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {backups.map((backup) => (
                                        <TableRow key={backup.name}>
                                            <TableCell className="font-medium">{backup.name}</TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {(backup.size / 1024 / 1024).toFixed(2)} MB
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {new Date(Number(backup.created_at) * 1000).toLocaleString()}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end gap-2">
                                                    <Button asChild size="sm" variant="outline">
                                                        <Link
                                                            href={`/admin/backup/${backup.name}/download`}
                                                            as="a"
                                                        >
                                                            Download
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() =>
                                                            router.delete(`/admin/backup/${backup.name}`, {
                                                                preserveScroll: true,
                                                                onSuccess: () =>
                                                                    router.reload({ only: ['backups'] }),
                                                            })
                                                        }
                                                    >
                                                        Delete
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </AppShell>
        </>
    );
}
