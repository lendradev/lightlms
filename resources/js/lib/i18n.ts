import { usePage } from '@inertiajs/react';

type Props = {
    translations?: Record<string, string>;
    app?: { locale?: string; availableLocales?: string[] };
};

/**
 * The interface strings for the active locale.
 *
 * The server resolves the fallback already, so a key that is missing from the
 * active language arrives here in English rather than as an empty string.
 */
export function useTranslation() {
    const { translations } = usePage().props as unknown as Props;

    const strings = translations ?? {};

    const t = (key: string, replace: Record<string, string | number> = {}): string => {
        let value = strings[key];

        if (value === undefined) {
            return key;
        }

        for (const [name, replacement] of Object.entries(replace)) {
            value = value.replace(`:${name}`, String(replacement));
        }

        return value;
    };

    return { t, locale: (usePage().props as unknown as Props).app?.locale ?? 'en' };
}
