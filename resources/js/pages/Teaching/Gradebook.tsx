import { Head, Link, router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Props = {
    course: { id: number; title: string; slug: string };
    items: {
        id: number;
        name: string;
        max_points: number;
        weight: number | null;
        sort_order: number;
    }[];
    students: { id: number; name: string; email: string }[];
    grades: {
        item_id: number;
        user_id: number;
        final_points: number | null;
        status: string;
        is_overridden: boolean;
    }[];
    totals: Record<string, { points: number | null; max: number; percent: number | null }>;
};

export default function TeachingGradebook({ course, items, students, grades, totals }: Props) {
    const gradeFor = (itemId: number, userId: number) =>
        grades.find((grade) => grade.item_id === itemId && grade.user_id === userId);

    const save = (itemId: number, userId: number, value: string) => {
        router.post(
            `/teaching/${course.slug}/gradebook/${itemId}`,
            { user_id: userId, points: value === '' ? null : Number(value) },
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head title={`Gradebook — ${course.title}`} />

            <AppShell
                title="Gradebook"
                actions={
                    <Button asChild size="sm" variant="outline">
                        <Link
                            href={`/teaching/${course.slug}/gradebook/export`}
                            as="a"
                        >
                            <Download aria-hidden />
                            Export CSV
                        </Link>
                    </Button>
                }
            >
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{course.title}</CardTitle>
                        <CardDescription>
                            Grades are weighted by item weight. Leave a cell empty to clear it.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {items.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No graded activities yet. Add an assignment or a quiz to the course and it
                                will appear here.
                            </p>
                        ) : students.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No students enrolled yet. Add people from the roster.
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Student</TableHead>
                                        {items.map((item) => (
                                            <TableHead key={item.id} className="text-right">
                                                {item.name}
                                                <span className="ml-1 font-normal text-muted-foreground">
                                                    /{item.max_points}
                                                </span>
                                            </TableHead>
                                        ))}
                                        <TableHead className="text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {students.map((student) => {
                                        const total = totals[String(student.id)];

                                        return (
                                            <TableRow key={student.id}>
                                                <TableCell>
                                                    <span className="block font-medium">{student.name}</span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {student.email}
                                                    </span>
                                                </TableCell>

                                                {items.map((item) => {
                                                    const grade = gradeFor(item.id, student.id);

                                                    return (
                                                        <TableCell key={item.id} className="text-right">
                                                            <Input
                                                                type="number"
                                                                step="0.01"
                                                                min="0"
                                                                max={item.max_points}
                                                                defaultValue={
                                                                    grade?.final_points === null ||
                                                                    grade?.final_points === undefined
                                                                        ? ''
                                                                        : grade.final_points
                                                                }
                                                                onBlur={(event) =>
                                                                    save(
                                                                        item.id,
                                                                        student.id,
                                                                        event.target.value,
                                                                    )
                                                                }
                                                                className="h-9 w-24 text-right tabular-nums"
                                                                aria-label={`${student.name} — ${item.name}`}
                                                            />
                                                        </TableCell>
                                                    );
                                                })}

                                                <TableCell className="text-right tabular-nums">
                                                    {total?.points === null || total === undefined ? (
                                                        <span className="text-muted-foreground">—</span>
                                                    ) : (
                                                        <span className="font-medium">
                                                            {total.points}
                                                            {total.percent !== null && (
                                                                <span className="ml-1 text-xs text-muted-foreground">
                                                                    ({total.percent}%)
                                                                </span>
                                                            )}
                                                        </span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>

                {items.length > 0 && students.length > 0 && (
                    <p className="mt-4 text-xs text-muted-foreground">
                        Totals use a weighted mean across graded items. Items that are left empty do not count
                        toward the total.
                    </p>
                )}
            </AppShell>
        </>
    );
}
