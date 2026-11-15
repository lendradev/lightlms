import { Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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
    useEffect(() => {
        const handle = window.setTimeout(() => {
            const params: Record<string, string> = {};
            if (filters.q) params.q = filters.q;
            if (filters.level) params.level = filters.level;

            router.get('/courses', params, { preserveState: true, replace: true });
        }, 300);

        return () => window.clearTimeout(handle);
    }, [filters.q, filters.level]);

    return (
        <>
            <Head title="Courses" />

            <SiteHeader user={auth.user} />

            <main className="container-page py-14">
                <div className="flex flex-wrap items-end justify-between gap-6">
                    <div className="max-w-2xl">
                        <h1 className="font-serif text-3xl font-semibold tracking-tight text-foreground">
                            Course catalogue
                        </h1>
                        <p className="mt-2 text-muted-foreground">
                            {courses.total} published {courses.total === 1 ? 'course' : 'courses'} available for
                            self-paced study.
                        </p>
                    </div>

                    <div className="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        <Input
                            value={filters.q}
                            onChange={(event) =>
                                router.get(
                                    '/courses',
                                    { ...(filters.level ? { level: filters.level } : {}), q: event.target.value },
                                    { preserveState: true, replace: true },
                                )
                            }
                            placeholder="Search courses"
                            aria-label="Search courses"
                            className="w-full sm:w-64"
                        />
                        <Select
                            value={filters.level || 'all'}
                            onValueChange={(value) =>
                                router.get(
                                    '/courses',
                                    {
                                        ...(filters.q ? { q: filters.q } : {}),
                                        level: value === 'all' ? '' : value,
                                    },
                                    { preserveState: true, replace: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-44" aria-label="Filter by level">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All levels</SelectItem>
                                {levels.map((level) => (
                                    <SelectItem key={level} value={level} className="capitalize">
                                        {level}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {courses.data.length === 0 ? (
                    <Card className="mt-10 grid place-items-center px-6 py-20 text-center">
                        <p className="font-serif text-lg font-semibold text-foreground">No courses found</p>
                        <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                            {filters.q || filters.level
                                ? 'Try a different search term or clear the level filter.'
                                : 'Published courses will appear here. Sign in as an instructor to create the first one.'}
                        </p>
                    </Card>
                ) : (
                    <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {courses.data.map((course) => (
                            <CourseCard key={course.id} course={course} />
                        ))}
                    </div>
                )}

                {courses.last_page > 1 && (
                    <nav className="mt-12 flex flex-wrap items-center gap-2" aria-label="Pagination">
                        {courses.links.map((link, index) =>
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
                                    aria-current={link.active ? 'page' : undefined}
                                >
                                    {link.label}
                                </Link>
                            ),
                        )}
                    </nav>
                )}
            </main>

            <SiteFooter />
        </>
    );
}
