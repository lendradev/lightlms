import { router, usePage } from '@inertiajs/react';
import { createContext, useContext, useEffect, useMemo, useState } from 'react';
import type { ReactNode } from 'react';

/**
 * Whether the browser is on its way to another page.
 *
 * Inertia renders the new page only after the server answers, so the old page
 * stays on screen during the request. Without this signal a slow answer looks
 * like a broken page. The context keeps one listener for the whole app
 * instead of one per page.
 */
type PendingVisits = {
    isNavigating: boolean;
    pendingUrl: string | null;
};

const PendingVisitsContext = createContext<PendingVisits>({
    isNavigating: false,
    pendingUrl: null,
});

export function PendingVisitsProvider({ children }: { children: ReactNode }) {
    const [pendingUrl, setPendingUrl] = useState<string | null>(null);

    useEffect(() => {
        const removeStart = router.on('start', (event) => {
            setPendingUrl(event.detail.visit.url.pathname + event.detail.visit.url.search);
        });

        const removeFinish = router.on('finish', () => {
            setPendingUrl(null);
        });

        const removeCancel = router.on('cancel', () => {
            setPendingUrl(null);
        });

        return () => {
            removeStart();
            removeFinish();
            removeCancel();
        };
    }, []);

    const value = useMemo<PendingVisits>(
        () => ({ isNavigating: pendingUrl !== null, pendingUrl }),
        [pendingUrl],
    );

    return <PendingVisitsContext.Provider value={value}>{children}</PendingVisitsContext.Provider>;
}

export function usePendingVisits(): PendingVisits {
    return useContext(PendingVisitsContext);
}

/**
 * True when a move to another page is on its way and the server takes long
 * enough to notice.
 *
 * A reload of the current page keeps its data on screen, so it shows the
 * progress bar but no skeleton. A move to another page swaps the content for
 * a skeleton, but only after a short delay: a fast answer should not flash a
 * skeleton the reader never needed.
 */
export function useIsNavigatingAway(delay = 250): boolean {
    const { isNavigating, pendingUrl } = usePendingVisits();
    const currentUrl = usePage().url;
    const [showSkeleton, setShowSkeleton] = useState(false);

    useEffect(() => {
        if (!isNavigating || pendingUrl === null) {
            setShowSkeleton(false);

            return;
        }

        const currentPath = new URL(currentUrl, 'http://localhost').pathname;
        const pendingPath = pendingUrl.split('?')[0];

        if (pendingPath === currentPath) {
            setShowSkeleton(false);

            return;
        }

        const timer = setTimeout(() => setShowSkeleton(true), delay);

        return () => clearTimeout(timer);
    }, [isNavigating, pendingUrl, currentUrl, delay]);

    return showSkeleton;
}
