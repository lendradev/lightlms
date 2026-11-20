import { usePage } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';

type Props = {
    flash?: {
        success: string | null;
        error: string | null;
    };
};

/**
 * The message the server left behind for this visit.
 *
 * The server decides what the text says, so the same component works for a
 * confirmation, a refusal and a warning alike.
 */
export default function FlashMessage({ className }: { className?: string }) {
    const { flash } = usePage().props as unknown as Props;

    if (!flash) {
        return null;
    }

    if (flash.error) {
        return (
            <Alert variant="destructive" className={className}>
                <AlertDescription>{flash.error}</AlertDescription>
            </Alert>
        );
    }

    if (flash.success) {
        return (
            <Alert className={className}>
                <AlertDescription>{flash.success}</AlertDescription>
            </Alert>
        );
    }

    return null;
}
