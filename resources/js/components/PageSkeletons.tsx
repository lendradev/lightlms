import { Skeleton } from '@/components/ui/skeleton';

/**
 * Placeholder shapes for the pages that load the most data.
 *
 * They keep the layout stable while the server is answering, so the page
 * does not jump when the real content arrives.
 */
export function CourseCardSkeleton() {
    return (
        <div className="bg-card shadow-soft overflow-hidden rounded-md border">
            <Skeleton className="aspect-[16/9] w-full rounded-none" />

            <div className="flex flex-col gap-2.5 p-5">
                <Skeleton className="h-5 w-24" />
                <Skeleton className="h-5 w-3/4" />
                <Skeleton className="h-16 w-full" />
                <Skeleton className="h-3 w-1/2" />
            </div>
        </div>
    );
}

export function CourseListSkeleton() {
    return (
        <div className="mt-6 grid gap-6 sm:grid-cols-2 2xl:grid-cols-3">
            <CourseCardSkeleton />
            <CourseCardSkeleton />
            <CourseCardSkeleton />
        </div>
    );
}

export function StatCardSkeleton() {
    return (
        <div className="bg-card shadow-soft rounded-md border p-5">
            <Skeleton className="h-4 w-28" />
            <Skeleton className="mt-3 h-8 w-16" />
        </div>
    );
}

/**
 * The card grid of the pages that list courses by enrolment rather than by
 * catalogue.
 */
export function EnrolledCourseSkeleton() {
    return (
        <div className="bg-card flex flex-col gap-3 rounded-xl border p-5">
            <div className="flex items-start justify-between gap-3">
                <Skeleton className="h-5 w-2/3" />
                <Skeleton className="h-5 w-14" />
            </div>
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-2 w-full" />
            <Skeleton className="h-3 w-24" />
        </div>
    );
}

export function EnrolledCourseListSkeleton() {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <EnrolledCourseSkeleton />
            <EnrolledCourseSkeleton />
            <EnrolledCourseSkeleton />
        </div>
    );
}
