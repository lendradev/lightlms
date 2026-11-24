import { Head, Link, router } from '@inertiajs/react';
import { Download, Settings2 } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { useTranslation } from '@/lib/i18n';
import { Badge } from '@/components/ui/badge';
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
        category_id: number | null;
        source_type: string;
        hidden: boolean;
        locked: boolean;
    }[];
    categories: {
        id: number;
        name: string;
        parent_id: number | null;
        aggregation: string;
        weight: number | null;
        sort_order: number;
        hidden: boolean;
    }[];
    students: { id: number; name: string; email: string }[];
    grades: {
        item_id: number;
        user_id: number;
        final_points: number | null;
        status: string;
        is_overridden: boolean;
    }[];
    totals: Record<
        string,
        { points: number | null; max: number; percent: number | null; graded: number; letter: string | null }
    >;
    categoryTotals: Record<
        string,
        Record<string, { points: number | null; max: number; percent: number | null; graded: number }>
    >;
};

export default function TeachingGradebook({
    course,
    items,
    categories,
    students,
    grades,
    totals,
    categoryTotals,
}: Props) {
    const { t } = useTranslation();
    const gradeFor = (itemId: number, userId: number) =>
        grades.find((grade) => grade.item_id === itemId && grade.user_id === userId);

    const save = (itemId: number, userId: number, value: string) => {
        router.post(
            `/teaching/${course.slug}/gradebook/${itemId}`,
            { user_id: userId, points: value === '' ? null : Number(value) },
            { preserveScroll: true },
        );
    };

    const saveWeight = (itemId: number, value: string) => {
        router.put(
            `/teaching/${course.slug}/gradebook/items/${itemId}`,
            { weight: value === '' ? null : Number(value) },
            { preserveScroll: true },
        );
    };

    const topLevel = categories.filter((category) => category.parent_id === null);
    const categoryName = (categoryId: number | null) =>
        categories.find((category) => category.id === categoryId)?.name;

    return (
        <>
            <Head title={`Gradebook — ${course.title}`} />

            <AppShell
                title="Gradebook"
                actions={
                    <div className="flex gap-2">
                        <Button asChild size="sm" variant="outline">
                            <Link href={`/teaching/${course.slug}/gradebook/settings`} as="button">
                                <Settings2 aria-hidden />
                                {t('action.settings')}
                            </Link>
                        </Button>
                        <Button asChild size="sm" variant="outline">
                            <Link href={`/teaching/${course.slug}/gradebook/export`} as="a">
                                <Download aria-hidden />
                                {t('action.export')}
                            </Link>
                        </Button>
                    </div>
                }
            >
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">{course.title}</CardTitle>
                        <CardDescription>
                            {t('gradebook.body')}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {items.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('gradebook.noItems')}
                            </p>
                        ) : students.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                {t('gradebook.noStudents')}
                            </p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('gradebook.student')}</TableHead>
                                        {items.map((item) => {
                                            const category = categoryName(item.category_id);

                                            return (
                                                <TableHead key={item.id} className="text-right">
                                                    <span className="block">{item.name}</span>
                                                    <span className="block text-xs font-normal text-muted-foreground">
                                                        /{item.max_points}
                                                    </span>
                                                    {category && (
                                                        <Badge variant="outline" className="mt-1 font-normal">
                                                            {category}
                                                        </Badge>
                                                    )}
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="1000"
                                                        defaultValue={item.weight ?? ''}
                                                        onBlur={(event) =>
                                                            saveWeight(item.id, event.target.value)
                                                        }
                                                        className="mt-1 h-7 w-20 text-right text-xs tabular-nums"
                                                        aria-label={`Weight for ${item.name}`}
                                                    />
                                                </TableHead>
                                            );
                                        })}
                                        <TableHead className="text-right">{t('course.total')}</TableHead>
                                        <TableHead className="text-right">
                                            {t('gradebook.gradeColumn')}
                                        </TableHead>
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

                                                <TableCell className="text-right">
                                                    {total?.letter ? (
                                                        <Badge>{total.letter}</Badge>
                                                    ) : (
                                                        <span className="text-muted-foreground">—</span>
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

                {topLevel.length > 0 && (
                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {topLevel.map((category) => (
                            <Card key={category.id}>
                                <CardHeader>
                                    <CardTitle as="h3" className="text-sm">{category.name}</CardTitle>
                                    <CardDescription>
                                        {category.weight !== null
                                        ? `${t('gradebook.weight')} ×${category.weight}`
                                        : t('gradebook.weight')}
                                    {' · '}
                                    {t(`gradebook.aggregation.${category.aggregation}`)}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-1 text-sm">
                                        {students.map((student) => {
                                            const subtotal =
                                                categoryTotals[String(student.id)]?.[String(category.id)];

                                            return (
                                                <li
                                                    key={student.id}
                                                    className="flex justify-between gap-3 tabular-nums"
                                                >
                                                    <span className="truncate text-muted-foreground">
                                                        {student.name}
                                                    </span>
                                                    <span>
                                                        {subtotal?.points === null ||
                                                        subtotal === undefined ? (
                                                            '—'
                                                        ) : (
                                                            <>
                                                                {subtotal.points} / {subtotal.max}
                                                            </>
                                                        )}
                                                    </span>
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {items.length > 0 && students.length > 0 && (
                    <p className="mt-4 text-xs text-muted-foreground">
                        {t('gradebook.totalNote')}
                    </p>
                )}
            </AppShell>
        </>
    );
}
