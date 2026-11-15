import { Link } from '@inertiajs/react';
import type { CourseSummary } from '@/types';

type Props = {
    course: CourseSummary;
};

export default function CourseCard({ course }: Props) {
    return (
        <Link
            href={`/courses/${course.slug}`}
            className="card card-hover group flex flex-col p-6 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"
        >
            <div className="flex items-start justify-between gap-3">
                <span className="badge bg-ink-100 text-ink-700 capitalize">{course.level}</span>
                {typeof course.modules_count === 'number' && (
                    <span className="text-xs text-ink-500">{course.modules_count} modules</span>
                )}
            </div>

            <h3 className="mt-4 text-balance font-serif text-lg font-semibold leading-snug text-ink-900 group-hover:text-brand-700">
                {course.title}
            </h3>

            <p className="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-ink-600">{course.summary}</p>

            <div className="mt-5 flex items-center justify-between border-t border-ink-200 pt-4 text-xs text-ink-500">
                <span>{course.instructor_name ?? 'LightLMS'}</span>
                <span>{Math.round(course.duration_minutes / 60)}h {course.duration_minutes % 60}m</span>
            </div>
        </Link>
    );
}
