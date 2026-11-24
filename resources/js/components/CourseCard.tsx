import { Link } from '@inertiajs/react';
import { Clock, Layers, User } from 'lucide-react';
import type { CSSProperties } from 'react';
import { Badge } from '@/components/ui/badge';
import type { CourseSummary } from '@/types';

type Props = {
    course: CourseSummary;
};

/**
 * Course covers are generated, not uploaded: a deterministic gradient keeps the
 * catalogue visually alive without shipping stock photography that would be
 * wrong for half the courses. The hue is spread across the whole wheel so
 * neighbouring courses do not look alike, and the colours themselves come
 * from the theme tokens in app.css.
 */
function coverHue(slug: string) {
    // FNV-1a spreads short strings better than the classic djb2-style hash.
    // The golden angle then spreads the result across the wheel: a plain
    // modulo lands three courses on three shades of the same green.
    let hash = 0x811c9dc5;

    for (let index = 0; index < slug.length; index += 1) {
        hash ^= slug.charCodeAt(index);
        hash = Math.imul(hash, 0x01000193);
    }

    return Math.round(((hash >>> 0) * 137.508) % 360);
}

export default function CourseCard({ course }: Props) {
    const hours = Math.floor(course.duration_minutes / 60);
    const minutes = course.duration_minutes % 60;

    return (
        <Link
            href={`/courses/${course.slug}`}
            className="group flex flex-col overflow-hidden rounded-md border bg-card shadow-soft transition duration-200 hover:-translate-y-0.5 hover:shadow-lifted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
        >
            <div
                className="course-cover relative aspect-[16/9] shrink-0 overflow-hidden"
                style={{ '--cover-hue': coverHue(course.slug) } as CSSProperties}
                aria-hidden
            >
                <span className="course-cover-sheen absolute inset-0" />
                <span className="absolute bottom-3 left-4 font-serif text-2xl font-semibold tracking-tight text-white/95">
                    {course.title.charAt(0)}
                </span>
            </div>

            <div className="flex flex-1 flex-col gap-2.5 p-5">
                <div className="flex items-center gap-2">
                    <Badge variant="outline" className="capitalize">
                        {course.level}
                    </Badge>
                    {typeof course.modules_count === 'number' && (
                        <span className="text-xs text-muted-foreground">
                            {course.modules_count} {course.modules_count === 1 ? 'module' : 'modules'}
                        </span>
                    )}
                </div>

                <h3 className="text-balance font-serif text-lg font-semibold leading-snug group-hover:text-primary">
                    {course.title}
                </h3>

                <p className="line-clamp-3 flex-1 text-sm leading-relaxed text-muted-foreground">
                    {course.summary}
                </p>

                <div className="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 border-t pt-3 text-xs text-muted-foreground">
                    <span className="inline-flex items-center gap-1.5">
                        <User className="size-3.5" aria-hidden />
                        {course.instructor_name ?? 'LightLMS'}
                    </span>
                    <span className="inline-flex items-center gap-1.5">
                        <Clock className="size-3.5" aria-hidden />
                        {hours > 0 ? `${hours}h ` : ''}
                        {minutes}m
                    </span>
                    {typeof course.modules_count === 'number' && (
                        <span className="inline-flex items-center gap-1.5">
                            <Layers className="size-3.5" aria-hidden />
                            {course.modules_count}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
