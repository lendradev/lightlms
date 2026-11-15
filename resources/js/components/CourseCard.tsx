import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import type { CourseSummary } from '@/types';

type Props = {
    course: CourseSummary;
};

export default function CourseCard({ course }: Props) {
    return (
        <Link
            href={`/courses/${course.slug}`}
            className="group flex flex-col gap-3 rounded-xl border bg-card p-6 text-card-foreground shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        >
            <div className="flex items-start justify-between gap-3">
                <Badge variant="outline" className="capitalize">
                    {course.level}
                </Badge>
                {typeof course.modules_count === 'number' && (
                    <span className="text-xs text-muted-foreground">{course.modules_count} modules</span>
                )}
            </div>
            <h3 className="text-balance font-serif text-lg font-semibold leading-snug group-hover:text-primary">
                {course.title}
            </h3>
            <p className="line-clamp-3 flex-1 text-sm leading-relaxed text-muted-foreground">{course.summary}</p>
            <div className="mt-2 flex items-center justify-between border-t pt-4 text-xs text-muted-foreground">
                <span>{course.instructor_name ?? 'LightLMS'}</span>
                <span>
                    {Math.round(course.duration_minutes / 60)}h {course.duration_minutes % 60}m
                </span>
            </div>
        </Link>
    );
}
