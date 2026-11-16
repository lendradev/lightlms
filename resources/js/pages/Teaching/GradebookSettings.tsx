import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { useTranslation } from '@/lib/i18n';
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

type Props = {
    course: { id: number; title: string; slug: string };
    categories: {
        id: number;
        name: string;
        parent_id: number | null;
        aggregation: string;
        weight: number | null;
        sort_order: number;
        hidden: boolean;
    }[];
    items: {
        id: number;
        name: string;
        max_points: number;
        weight: number | null;
        category_id: number | null;
        source_type: string;
        hidden: boolean;
        locked: boolean;
    }[];
    settings: {
        aggregation: string;
        letter_a: number | null;
        letter_b: number | null;
        letter_c: number | null;
        letter_d: number | null;
    };
    aggregations: { value: string; label: string }[];
};

export default function TeachingGradebookSettings({ course, categories, items, settings, aggregations }: Props) {
    const { t } = useTranslation();

    const categoryForm = useForm({
        name: '',
        parent_id: '' as string,
        aggregation: 'weighted_mean',
        weight: '' as string,
        hidden: false,
    });

    const itemForm = useForm({
        name: '',
        max_points: '100',
        pass_points: '' as string,
        weight: '' as string,
        category_id: '' as string,
    });

    const settingsForm = useForm({
        aggregation: settings.aggregation,
        letter_a: settings.letter_a === null ? '' : String(settings.letter_a),
        letter_b: settings.letter_b === null ? '' : String(settings.letter_b),
        letter_c: settings.letter_c === null ? '' : String(settings.letter_c),
        letter_d: settings.letter_d === null ? '' : String(settings.letter_d),
    });

    const manualItems = items.filter((item) => item.source_type === 'manual');

    const removeCategory = (id: number) => {
        router.delete(`/teaching/${course.slug}/gradebook/categories/${id}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={`Gradebook settings — ${course.title}`} />

            <AppShell
                title="Gradebook settings"
                actions={
                    <Button asChild size="sm" variant="outline">
                        <Link href={`/teaching/${course.slug}/gradebook`} as="button">
                            <ArrowLeft aria-hidden />
                            {t('gradebook.back')}
                        </Link>
                    </Button>
                }
            >
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Categories</CardTitle>
                            <CardDescription>
                                {t('gradebook.categoriesBody')}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('course.titleField')}</TableHead>
                                        <TableHead>{t('gradebook.aggregation')}</TableHead>
                                        <TableHead className="text-right">{t('gradebook.weight')}</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-sm text-muted-foreground">
                                                {t('gradebook.noCategories')}
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        categories.map((category) => (
                                            <TableRow key={category.id}>
                                                <TableCell>
                                                    <span className="block font-medium">{category.name}</span>
                                                    {category.parent_id !== null && (
                                                        <span className="block text-xs text-muted-foreground">
                                                            {t('gradebook.parentCategory')}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {t(`gradebook.aggregation.${category.aggregation}`)}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right tabular-nums">
                                                    {category.weight ?? '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => removeCategory(category.id)}
                                                    >
                                                        <Trash2 aria-hidden />
                                                        {t('action.delete')}
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            <form
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    categoryForm.post(
                                        `/teaching/${course.slug}/gradebook/categories`,
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => categoryForm.reset(),
                                        },
                                    );
                                }}
                                className="grid gap-3 border-t pt-4 sm:grid-cols-2"
                            >
                                <div className="grid gap-2">
                                    <Label htmlFor="category-name">{t('course.titleField')}</Label>
                                    <Input
                                        id="category-name"
                                        value={categoryForm.data.name}
                                        onChange={(event) => categoryForm.setData('name', event.target.value)}
                                    />
                                    {categoryForm.errors.name && (
                                        <p className="text-xs text-destructive">{categoryForm.errors.name}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="category-aggregation">
                                        {t('gradebook.aggregation')}
                                    </Label>
                                    <Select
                                        value={categoryForm.data.aggregation}
                                        onValueChange={(value) =>
                                            categoryForm.setData('aggregation', value)
                                        }
                                    >
                                        <SelectTrigger id="category-aggregation">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {aggregations.map((aggregation) => (
                                                <SelectItem
                                                    key={aggregation.value}
                                                    value={aggregation.value}
                                                >
                                                    {aggregation.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="category-weight">{t('gradebook.weight')}</Label>
                                    <Input
                                        id="category-weight"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={categoryForm.data.weight}
                                        onChange={(event) =>
                                            categoryForm.setData('weight', event.target.value)
                                        }
                                    />
                                    {categoryForm.errors.weight && (
                                        <p className="text-xs text-destructive">{categoryForm.errors.weight}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="category-parent">
                                        {t('gradebook.parentCategory')}
                                    </Label>
                                    <Select
                                        value={categoryForm.data.parent_id}
                                        onValueChange={(value) => categoryForm.setData('parent_id', value)}
                                    >
                                        <SelectTrigger id="category-parent">
                                            <SelectValue placeholder={t('gradebook.topLevel')} />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                <SelectItem key={category.id} value={String(category.id)}>
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="sm:col-span-2">
                                    <Button type="submit" disabled={categoryForm.processing}>
                                        <Plus aria-hidden />
                                        {t('gradebook.addCategory')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Manual items</CardTitle>
                                <CardDescription>
                                    {t('gradebook.manualItemsBody')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {manualItems.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>{t('course.titleField')}</TableHead>
                                                <TableHead className="text-right">
                                                    {t('course.max')}
                                                </TableHead>
                                                <TableHead className="text-right">
                                                    {t('gradebook.weight')}
                                                </TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {manualItems.map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        <span className="block font-medium">{item.name}</span>
                                                        {item.locked && (
                                                            <Badge variant="outline" className="mt-1">
                                                                {t('gradebook.locked')}
                                                            </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {item.max_points}
                                                    </TableCell>
                                                    <TableCell className="text-right tabular-nums">
                                                        {item.weight ?? '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        {t('gradebook.noManualItems')}
                                    </p>
                                )}

                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        itemForm.post(`/teaching/${course.slug}/gradebook/items`, {
                                            preserveScroll: true,
                                            onSuccess: () => itemForm.reset(),
                                        });
                                    }}
                                    className="grid gap-3 border-t pt-4 sm:grid-cols-2"
                                >
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="item-name">{t('course.titleField')}</Label>
                                        <Input
                                            id="item-name"
                                            value={itemForm.data.name}
                                            onChange={(event) => itemForm.setData('name', event.target.value)}
                                        />
                                        {itemForm.errors.name && (
                                            <p className="text-xs text-destructive">{itemForm.errors.name}</p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="item-max">{t('gradebook.maxPoints')}</Label>
                                        <Input
                                            id="item-max"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={itemForm.data.max_points}
                                            onChange={(event) =>
                                                itemForm.setData('max_points', event.target.value)
                                            }
                                        />
                                        {itemForm.errors.max_points && (
                                            <p className="text-xs text-destructive">
                                                {itemForm.errors.max_points}
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="item-weight">{t('gradebook.weight')}</Label>
                                        <Input
                                            id="item-weight"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={itemForm.data.weight}
                                            onChange={(event) => itemForm.setData('weight', event.target.value)}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="item-category">{t('gradebook.category')}</Label>
                                        <Select
                                            value={itemForm.data.category_id}
                                            onValueChange={(value) => itemForm.setData('category_id', value)}
                                        >
                                            <SelectTrigger id="item-category">
                                                <SelectValue placeholder={t('gradebook.topLevel')} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {categories.map((category) => (
                                                    <SelectItem key={category.id} value={String(category.id)}>
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="item-pass">{t('gradebook.passPoints')}</Label>
                                        <Input
                                            id="item-pass"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={itemForm.data.pass_points}
                                            onChange={(event) =>
                                                itemForm.setData('pass_points', event.target.value)
                                            }
                                        />
                                    </div>

                                    <div className="sm:col-span-2">
                                        <Button type="submit" disabled={itemForm.processing}>
                                            <Plus aria-hidden />
                                            {t('gradebook.addManualItem')}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Totals and letter grades</CardTitle>
                                <CardDescription>
                                    {t('gradebook.totalsBody')}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        settingsForm.put(
                                            `/teaching/${course.slug}/gradebook/settings`,
                                            { preserveScroll: true },
                                        );
                                    }}
                                    className="grid gap-3 sm:grid-cols-2"
                                >
                                    <div className="grid gap-2 sm:col-span-2">
                                        <Label htmlFor="settings-aggregation">
                                            {t('gradebook.courseAggregation')}
                                        </Label>
                                        <Select
                                            value={settingsForm.data.aggregation}
                                            onValueChange={(value) =>
                                                settingsForm.setData('aggregation', value)
                                            }
                                        >
                                            <SelectTrigger id="settings-aggregation">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {aggregations.map((aggregation) => (
                                                    <SelectItem
                                                        key={aggregation.value}
                                                        value={aggregation.value}
                                                    >
                                                        {aggregation.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {(
                                        [
                                            ['letter_a', t('gradebook.letterA')],
                                            ['letter_b', t('gradebook.letterB')],
                                            ['letter_c', t('gradebook.letterC')],
                                            ['letter_d', t('gradebook.letterD')],
                                        ] as const
                                    ).map(([field, label]) => (
                                        <div key={field} className="grid gap-2">
                                            <Label htmlFor={`settings-${field}`}>{label}</Label>
                                            <Input
                                                id={`settings-${field}`}
                                                type="number"
                                                step="0.1"
                                                min="0"
                                                max="100"
                                                value={settingsForm.data[field]}
                                                onChange={(event) =>
                                                    settingsForm.setData(field, event.target.value)
                                                }
                                            />
                                        </div>
                                    ))}

                                    <div className="sm:col-span-2">
                                        <Button type="submit" disabled={settingsForm.processing}>
                                            {t('gradebook.saveSettings')}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppShell>
        </>
    );
}
