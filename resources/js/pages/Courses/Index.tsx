import { Head, Link, router } from '@inertiajs/react';
import { LayoutGrid, List, SlidersHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { CourseSummary, Paginated, SharedProps } from '@/types';

type Props = {
    courses: Paginated<CourseSummary>;
    filters: { q: string; level: string };
    levels: string[];
    auth: SharedProps['auth'];
};


export default function CoursesIndex({ courses, filters, levels, auth }: Props) {
    const [view, setView] = useState<'grid' | 'list'>('grid');
    const [filtersOpen, setFiltersOpen] = useState(false);

    useEffect(() => {
        const handle = window.setTimeout(() => {
            const params: Record<string, string> = {};
            if (filters.q) params.q = filters.q;
            if (filters.level) params.level = filters.level;

            router.get('/courses', params, { preserveState: true, replace: true });
        }, 300);

        return () => window.clearTimeout(handle);
    }, [filters.q, filters.level]);

    const update = (key: 'q' | 'level', value: string) => {
        const next: Record<string, string> = {};
        const q = key === 'q' ? value : filters.q;
        const level = key === 'level' ? value : filters.level;
        if (q) next.q = q;
        if (level) next.level = level;

        router.get('/courses', next, { preserveState: true, replace: true });
    };

    const hasFilters = Boolean(filters.q || filters.level);

    return (
        <>
            <Head title="Courses" />

            <SiteHeader user={auth.user} />

            <main className="container-page py-10">
                <nav aria-label="Breadcrumb" className="text-sm text-muted-foreground">
                    <Link href="/" className="transition hover:text-foreground">
                        Home
                    </Link>
                    <span className="px-1.5">/</span>
                    <span className="text-foreground">Courses</span>
                </nav>

                <div className="mt-3 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="font-serif text-3xl font-semibold tracking-tight text-foreground">
                            Course catalogue
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {courses.total} {courses.total === 1 ? 'course' : 'courses'} available for
                            self-paced study.
                        </p>
                    </div>

                    <Button
                        variant="outline"
                        size="sm"
                        className="lg:hidden"
                        onClick={() => setFiltersOpen((open) => !open)}
                    >
                        <SlidersHorizontal aria-hidden />
                        Filters
                    </Button>
                </div>

                <div className="mt-8 grid gap-8 lg:grid-cols-[16rem_1fr]">
                    <aside
                        className={`${filtersOpen ? 'block' : 'hidden'} lg:block lg:sticky lg:top-20 lg:self-start`}
                        aria-label="Course filters"
                    >
                        <div className="rounded-md border bg-card">
                            <div className="border-b bg-muted px-4 py-2.5 text-sm font-semibold text-foreground">
                                Filter
                            </div>

                            <div className="space-y-5 p-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="search">Search</Label>
                                    <Input
                                        id="search"
                                        value={filters.q}
                                        onChange={(event) => update('q', event.target.value)}
                                        placeholder="Title, instructor or code"
                                    />
                                </div>

                                <fieldset>
                                    <legend className="mb-2 text-sm font-semibold text-foreground">
                                        Level
                                    </legend>
                                    <div className="space-y-1.5">
                                        {levels.map((level) => (
                                            <label
                                                key={level}
                                                className="flex cursor-pointer items-center gap-2.5 text-sm text-foreground"
                                            >
                                                <input
                                                    type="radio"
                                                    name="level"
                                                    checked={filters.level === level}
                                                    onChange={() => update('level', level)}
                                                    className="size-4 accent-primary"
                                                />
                                                <span className="capitalize">{level}</span>
                                            </label>
                                        ))}
                                        <label className="flex cursor-pointer items-center gap-2.5 text-sm text-foreground">
                                            <input
                                                type="radio"
                                                name="level"
                                                checked={filters.level === ''}
                                                onChange={() => update('level', '')}
                                                className="size-4 accent-primary"
                                            />
                                            <span>All levels</span>
                                        </label>
                                    </div>
                                </fieldset>

                                {hasFilters && (
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => router.get('/courses', {}, { preserveState: true })}
                                    >
                                        Clear filters
                                    </Button>
                                )}
                            </div>
                        </div>
                    </aside>

                    <div>
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b pb-3">
                            <div className="flex flex-wrap items-center gap-2">
                                {filters.q && (
                                    <Badge variant="secondary">
                                        “{filters.q}”
                                        <button
                                            type="button"
                                            aria-label="Clear search"
                                            onClick={() => update('q', '')}
                                            className="ml-1"
                                        >
                                            ×
                                        </button>
                                    </Badge>
                                )}
                                {filters.level && (
                                    <Badge variant="secondary" className="capitalize">
                                        {filters.level}
                                        <button
                                            type="button"
                                            aria-label="Clear level filter"
                                            onClick={() => update('level', '')}
                                            className="ml-1"
                                        >
                                            ×
                                        </button>
                                    </Badge>
                                )}
                            </div>

                            <div className="flex items-center gap-2">
                                <Select
                                    value={filters.level || 'all'}
                                    onValueChange={(value) => update('level', value === 'all' ? '' : value)}
                                >
                                    <SelectTrigger className="w-40" aria-label="Sort by">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Newest first</SelectItem>
                                        <SelectItem value="level">Level</SelectItem>
                                    </SelectContent>
                                </Select>

                                <div className="flex rounded-sm border">
                                    <Button
                                        variant={view === 'grid' ? 'secondary' : 'ghost'}
                                        size="icon"
                                        className="size-9"
                                        aria-label="Grid view"
                                        aria-pressed={view === 'grid'}
                                        onClick={() => setView('grid')}
                                    >
                                        <LayoutGrid aria-hidden />
                                    </Button>
                                    <Button
                                        variant={view === 'list' ? 'secondary' : 'ghost'}
                                        size="icon"
                                        className="size-9"
                                        aria-label="List view"
                                        aria-pressed={view === 'list'}
                                        onClick={() => setView('list')}
                                    >
                                        <List aria-hidden />
                                    </Button>
                                </div>
                            </div>
                        </div>

                        {courses.data.length === 0 ? (
                            <Card className="mt-8 grid place-items-center px-6 py-16 text-center">
                                <p className="font-serif text-lg font-semibold text-foreground">
                                    No courses found
                                </p>
                                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                    {hasFilters
                                        ? 'Try a different search term or clear the filters.'
                                        : 'Published courses will appear here. Sign in as an instructor to create the first one.'}
                                </p>
                                {hasFilters && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="mt-4"
                                        onClick={() =>
                                            router.get('/courses', {}, { preserveState: true })
                                        }
                                    >
                                        Clear filters
                                    </Button>
                                )}
                            </Card>
                        ) : view === 'grid' ? (
                            <div className="mt-6 grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
                                {courses.data.map((course) => (
                                    <CourseCard key={course.id} course={course} />
                                ))}
                            </div>
                        ) : (
                            <ul className="mt-2 divide-y">
                                {courses.data.map((course) => (
                                    <li key={course.id}>
                                        <Link
                                            href={`/courses/${course.slug}`}
                                            className="flex items-center gap-4 py-4 transition hover:bg-muted/60"
                                        >
                                            <span className="min-w-0 flex-1">
                                                <span className="block font-serif text-base font-semibold text-foreground">
                                                    {course.title}
                                                </span>
                                                <span className="mt-0.5 block line-clamp-1 text-sm text-muted-foreground">
                                                    {course.summary}
                                                </span>
                                            </span>
                                            <Badge variant="outline" className="capitalize shrink-0">
                                                {course.level}
                                            </Badge>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}

                        {courses.last_page > 1 && (
                            <nav className="mt-10 flex flex-wrap items-center gap-1" aria-label="Pagination">
                                {courses.links.map((link, index) =>
                                    link.url === null ? (
                                        <span
                                            key={`empty-${index}`}
                                            className="px-3 py-2 text-sm text-muted-foreground"
                                        >
                                            {link.label}
                                        </span>
                                    ) : (
                                        <Link
                                            key={`${link.label}-${index}`}
                                            href={link.url}
                                            preserveScroll
                                            className={
                                                link.active
                                                    ? 'rounded-sm bg-primary px-3 py-2 text-sm font-medium text-primary-foreground'
                                                    : 'rounded-sm px-3 py-2 text-sm text-muted-foreground transition hover:bg-muted'
                                            }
                                            aria-current={link.active ? 'page' : undefined}
                                        >
                                            {link.label}
                                        </Link>
                                    ),
                                )}
                            </nav>
                        )}
                    </div>
                </div>
            </main>

            <SiteFooter />
        </>
    );
}
