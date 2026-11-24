import { Head, Link } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { useTranslation } from '@/lib/i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
        category_id: number | null;
        sort_order: number;
    }[];
    categories: { id: number; name: string; weight: number | null }[];
    grades: {
        item_id: number;
        final_points: number | null;
        feedback: string | null;
        status: string;
    }[];
    categoryTotals: Record<string, { points: number | null; max: number; percent: number | null }>;
    total: {
        points: number | null;
        max: number;
        percent: number | null;
        graded: number;
        letter: string | null;
    };
};

export default function LearningGradebook({ course, items, categories, grades, categoryTotals, total }: Props) {
    const { t } = useTranslation();

    const gradeFor = (itemId: number) => grades.find((grade) => grade.item_id === itemId);

    const categoryName = (categoryId: number | null) =>
        categories.find((category) => category.id === categoryId)?.name;

    return (
        <>
            <Head title={`Grades — ${course.title}`} />

            <AppShell title="Your grades">
                <div className="grid gap-6 lg:grid-cols-[1fr_18rem]">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">{course.title}</CardTitle>
                            <CardDescription>
                                {t('gradebook.transcriptBody')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {items.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    {t('gradebook.transcriptEmpty')}
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{t('gradebook.activity')}</TableHead>
                                            <TableHead>{t('gradebook.category')}</TableHead>
                                            <TableHead className="text-right">
                                                {t('gradebook.markColumn')}
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {items.map((item) => {
                                            const grade = gradeFor(item.id);
                                            const category = categoryName(item.category_id);

                                            return (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        <span className="block font-medium">{item.name}</span>
                                                        {grade?.feedback && (
                                                            <span className="mt-0.5 block text-xs text-muted-foreground">
                                                                {grade.feedback}
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {category ? (
                                                            <Badge variant="outline">{category}</Badge>
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground">
                                                                —
                                                            </span>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {grade?.final_points === null ||
                                                        grade?.final_points === undefined ? (
                                                            <span className="text-muted-foreground">—</span>
                                                        ) : (
                                                            <span className="font-medium">
                                                                {grade.final_points}
                                                                <span className="ml-1 text-xs text-muted-foreground">
                                                                    /{item.max_points}
                                                                </span>
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

                    <aside className="space-y-4 lg:sticky lg:top-20 lg:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">{t('gradebook.yourGrade')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {total.points === null ? (
                                    <p className="text-sm text-muted-foreground">
                                        {t('gradebook.noGradeYet')}
                                    </p>
                                ) : (
                                    <div className="flex items-baseline gap-3">
                                        <span className="font-serif text-4xl font-semibold tracking-tight">
                                            {total.letter ?? '—'}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {total.points} / {total.max}
                                        </span>
                                    </div>
                                )}
                                {total.percent !== null && (
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {total.percent}% ·{' '}
                                        {t(
                                            total.graded === 1
                                                ? 'gradebook.countedOne'
                                                : 'gradebook.counted',
                                            { count: total.graded },
                                        )}
                                    </p>
                                )}
                            </CardContent>
                        </Card>

                        {categories.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle as="h3" className="text-sm">{t('gradebook.categories')}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <dl className="space-y-3">
                                        {categories.map((category) => {
                                            const subtotal = categoryTotals[String(category.id)];

                                            return (
                                                <div key={category.id}>
                                                    <dt className="text-sm font-medium">
                                                        {category.name}
                                                        {category.weight !== null && (
                                                            <span className="ml-1 text-xs text-muted-foreground">
                                                                ×{category.weight}
                                                            </span>
                                                        )}
                                                    </dt>
                                                    <dd className="text-xs text-muted-foreground">
                                                        {subtotal?.points === null ||
                                                        subtotal === undefined ? (
                                                            '—'
                                                        ) : (
                                                            <>
                                                                {subtotal.points} / {subtotal.max}
                                                                {subtotal.percent !== null &&
                                                                    ` (${subtotal.percent}%)`}
                                                            </>
                                                        )}
                                                    </dd>
                                                </div>
                                            );
                                        })}
                                    </dl>
                                </CardContent>
                            </Card>
                        )}

                        <Button asChild variant="outline" size="sm">
                            <Link href={`/learning/${course.slug}`}>{t('course.backToCourse')}</Link>
                        </Button>
                    </aside>
                </div>
            </AppShell>
        </>
    );
}
