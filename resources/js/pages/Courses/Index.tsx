import { Head, Link } from '@inertiajs/react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import type { CourseSummary, Paginated, SharedProps } from '@/types';

type Props = {
    courses: Paginated<CourseSummary>;
    auth: SharedProps['auth'];
};

export default function CoursesIndex({ courses, auth }: Props) {
    return (
        <>
            <Head title="Courses" />

            <SiteHeader user={auth.user} />

            <main className="container-page py-14">
                <div className="max-w-2xl">
                    <h1 className="font-serif text-3xl font-semibold tracking-tight text-ink-900">
                        Course catalogue
                    </h1>
                    <p className="mt-2 text-ink-600">
                        {courses.total} published {courses.total === 1 ? 'course' : 'courses'} available for
                        self-paced study.
                    </p>
                </div>

                {courses.data.length === 0 ? (
                    <div className="card mt-10 grid place-items-center px-6 py-20 text-center">
                        <p className="font-serif text-lg font-semibold text-ink-900">No courses yet</p>
                        <p className="mt-1 max-w-sm text-sm text-ink-500">
                            Published courses will appear here. Sign in as an instructor to create the first one.
                        </p>
                    </div>
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
                                <span key={`empty-${index}`} className="px-3 py-2 text-sm text-ink-400">
                                    {link.label}
                                </span>
                            ) : (
                                <Link
                                    key={`${link.label}-${index}`}
                                    href={link.url}
                                    preserveScroll
                                    className={
                                        link.active
                                            ? 'rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white'
                                            : 'rounded-lg px-3 py-2 text-sm text-ink-600 hover:bg-ink-100'
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
