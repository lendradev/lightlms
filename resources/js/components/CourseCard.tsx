import { Link } from '@inertiajs/react';
import { Clock, Layers, User } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import type { CourseSummary } from '@/types';

type Props = {
    course: CourseSummary;
};

/**
 * Course covers are generated, not uploaded: a deterministic gradient keeps the
 * catalogue visually alive without shipping stock photography that would be
 * wrong for half the courses. The hue stays inside the indigo family so the
 * covers read as one set.
 */
function coverStyle(slug: string) {
    let hash = 0;

    for (let index = 0; index < slug.length; index += 1) {
        hash = (hash << 5) - hash + slug.charCodeAt(index);
        hash |= 0;
    }

    const hue = 240 + (Math.abs(hash) % 40);

    return {
        backgroundImage: `linear-gradient(135deg, oklch(34% 0.09 ${hue}) 0%, oklch(22% 0.06 ${hue + 18}) 100%)`,
    };
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
                className="relative aspect-[16/9] shrink-0 overflow-hidden"
                style={coverStyle(course.slug)}
                aria-hidden
            >
                <span className="absolute inset-0 bg-[radial-gradient(circle_at_20%_120%,rgb(255_255_255/0.18),transparent_60%)]" />
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
