import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
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
                            Back to the gradebook
                        </Link>
                    </Button>
                }
            >
                <div className="grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Categories</CardTitle>
                            <CardDescription>
                                A category groups activities. Its weight decides how much the group
                                counts toward the course total.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Aggregation</TableHead>
                                        <TableHead className="text-right">Weight</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="text-sm text-muted-foreground">
                                                No categories yet. Everything is counted at the top level.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        categories.map((category) => (
                                            <TableRow key={category.id}>
                                                <TableCell>
                                                    <span className="block font-medium">{category.name}</span>
                                                    {category.parent_id !== null && (
                                                        <span className="block text-xs text-muted-foreground">
                                                            Inside another category
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">
                                                        {category.aggregation.replace('_', ' ')}
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
                                                        Delete
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
                                    <Label htmlFor="category-name">Name</Label>
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
                                    <Label htmlFor="category-aggregation">Aggregation</Label>
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
                                    <Label htmlFor="category-weight">Weight</Label>
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
                                    <Label htmlFor="category-parent">Parent category</Label>
                                    <Select
                                        value={categoryForm.data.parent_id}
                                        onValueChange={(value) => categoryForm.setData('parent_id', value)}
                                    >
                                        <SelectTrigger id="category-parent">
                                            <SelectValue placeholder="Top level" />
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
                                        Add category
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
                                    A mark that is not tied to an activity, such as a final project or
                                    class participation.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {manualItems.length > 0 ? (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Name</TableHead>
                                                <TableHead className="text-right">Max</TableHead>
                                                <TableHead className="text-right">Weight</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {manualItems.map((item) => (
                                                <TableRow key={item.id}>
                                                    <TableCell>
                                                        <span className="block font-medium">{item.name}</span>
                                                        {item.locked && (
                                                            <Badge variant="outline" className="mt-1">
                                                                Locked
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
                                        No manual items yet.
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
                                        <Label htmlFor="item-name">Name</Label>
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
                                        <Label htmlFor="item-max">Maximum points</Label>
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
                                        <Label htmlFor="item-weight">Weight</Label>
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
                                        <Label htmlFor="item-category">Category</Label>
                                        <Select
                                            value={itemForm.data.category_id}
                                            onValueChange={(value) => itemForm.setData('category_id', value)}
                                        >
                                            <SelectTrigger id="item-category">
                                                <SelectValue placeholder="Top level" />
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
                                        <Label htmlFor="item-pass">Pass points</Label>
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
                                            Add manual item
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Totals and letter grades</CardTitle>
                                <CardDescription>
                                    How the course total is computed, and the percentage each letter grade
                                    starts at.
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
                                        <Label htmlFor="settings-aggregation">Course aggregation</Label>
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
                                            ['letter_a', 'A from'],
                                            ['letter_b', 'B from'],
                                            ['letter_c', 'C from'],
                                            ['letter_d', 'D from'],
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
                                            Save settings
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
