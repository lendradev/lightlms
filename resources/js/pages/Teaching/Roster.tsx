import { Head, router, useForm } from '@inertiajs/react';
import { UserPlus } from 'lucide-react';
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

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    progress: number;
    enrolled_at: string | null;
};

type Props = {
    course: {
        id: number;
        title: string;
        slug: string;
        self_enroll: boolean;
        capacity: number | null;
    };
    members: Member[];
    roles: { value: string; label: string }[];
};

export default function TeachingRoster({ course, members, roles }: Props) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        role: 'student',
    });

    const changeRole = (userId: number, role: string) => {
        router.put(
            `/teaching/${course.slug}/roster/${userId}`,
            { role },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Roster — ${course.title}`} />

            <AppShell title="Roster">
                <div className="grid gap-6 lg:grid-cols-[20rem_1fr]">
                    <Card className="h-fit">
                        <CardHeader>
                            <CardTitle className="text-base">Add someone</CardTitle>
                            <CardDescription>
                                The account must already exist. Use its email address to add it to this course.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    post(`/teaching/${course.slug}/roster`, {
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
                                    {errors.email && (
                                        <p className="text-xs text-destructive">{errors.email}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="role">Role in this course</Label>
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

                                <Button type="submit" disabled={processing}>
                                    <UserPlus aria-hidden />
                                    Add to course
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Members</CardTitle>
                            <CardDescription>
                                {members.length} {members.length === 1 ? 'person' : 'people'}
                                {course.capacity !== null && ` · capacity ${course.capacity}`}
                                {course.self_enroll ? ' · self-enrolment is on' : ' · self-enrolment is off'}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {members.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nobody is in this course yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>Role</TableHead>
                                            <TableHead>Progress</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {members.map((member) => (
                                            <TableRow key={member.id}>
                                                <TableCell>
                                                    <span className="block font-medium">{member.name}</span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {member.email}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <Select
                                                        value={member.role}
                                                        onValueChange={(value) => changeRole(member.id, value)}
                                                    >
                                                        <SelectTrigger className="w-40" aria-label="Course role">
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            {roles.map((role) => (
                                                                <SelectItem
                                                                    key={role.value}
                                                                    value={role.value}
                                                                >
                                                                    {role.label}
                                                                </SelectItem>
                                                            ))}
                                                        </SelectContent>
                                                    </Select>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{member.progress}%</Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            router.delete(
                                                                `/teaching/${course.slug}/roster/${member.id}`,
                                                                { preserveScroll: true },
                                                            )
                                                        }
                                                    >
                                                        Remove
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
