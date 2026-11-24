import { Component, type ErrorInfo, type ReactNode } from 'react';

type Props = {
    children: ReactNode;
};

type State = {
    error: Error | null;
};

/**
 * A render error on the client used to leave a blank page.
 *
 * Inertia has no error boundary of its own, so this one catches the failure,
 * keeps the message on screen and offers a way back: reload the page.
 */
export default class ErrorBoundary extends Component<Props, State> {
    state: State = { error: null };

    static getDerivedStateFromError(error: Error): State {
        return { error };
    }

    componentDidCatch(error: Error, info: ErrorInfo): void {
        console.error('LightLMS render error', error, info.componentStack);
    }

    render(): ReactNode {
        const { error } = this.state;

        if (!error) {
            return this.props.children;
        }

        return (
            <div className="bg-background flex min-h-screen items-center justify-center px-4 py-12">
                <div className="bg-card text-card-foreground shadow-soft w-full max-w-md rounded-lg border p-6">
                    <h1 className="font-serif text-xl font-semibold tracking-tight">
                        This page could not be shown
                    </h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Something went wrong while drawing the page. Reloading usually fixes it. If
                        it keeps happening, the details are in the browser console.
                    </p>
                    <p className="bg-muted text-muted-foreground mt-3 rounded-md px-3 py-2 font-mono text-xs break-words">
                        {error.message}
                    </p>
                    <button
                        type="button"
                        onClick={() => window.location.reload()}
                        className="bg-primary text-primary-foreground focus-visible:outline-ring mt-4 inline-flex h-9 items-center rounded-md px-4 text-sm font-medium transition hover:opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2"
                    >
                        Reload the page
                    </button>
                </div>
            </div>
        );
    }
}
