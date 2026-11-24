import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import type { ComponentType } from 'react';
import ErrorBoundary from '@/components/ErrorBoundary';
import { PendingVisitsProvider } from '@/components/PendingVisits';

createInertiaApp({
    title: (title) => (title ? `${title} · LightLMS` : 'LightLMS'),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx');
        const page = pages[`./pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }

        return page() as Promise<ComponentType>;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <ErrorBoundary>
                <PendingVisitsProvider>
                    <App {...props} />
                </PendingVisitsProvider>
            </ErrorBoundary>,
        );
    },
    progress: {
        // The bar reads the primary token, so it follows the theme instead of
        // carrying a colour of its own.
        color: 'var(--primary)',
    },
});
