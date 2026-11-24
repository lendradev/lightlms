import { usePage } from '@inertiajs/react';
import { CircleAlert, CircleCheck } from 'lucide-react';
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
 *
 * Success and failure differ in shape as well as in colour: each one carries
 * its own icon, so the difference survives a monochrome screen or a reader
 * who cannot tell green from red.
 */
export default function FlashMessage({ className }: { className?: string }) {
    const { flash } = usePage().props as unknown as Props;

    if (!flash) {
        return null;
    }

    if (flash.error) {
        return (
            <Alert variant="destructive" className={className}>
                <CircleAlert aria-hidden />
                <AlertDescription>{flash.error}</AlertDescription>
            </Alert>
        );
    }

    if (flash.success) {
        return (
            <Alert variant="success" className={className}>
                <CircleCheck aria-hidden />
                <AlertDescription>{flash.success}</AlertDescription>
            </Alert>
        );
    }

    return null;
}
