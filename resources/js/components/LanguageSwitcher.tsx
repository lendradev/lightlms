import { router } from '@inertiajs/react';
import { Languages } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';

type Props = {
    app: {
        locale: string;
        availableLocales: string[];
        localeNames?: Record<string, string>;
    };
};

export function LanguageSwitcher({ app }: Props) {
    const locale = app.locale ?? 'en';

    const names: Record<string, string> = {
        en: 'English',
        id: 'Bahasa Indonesia',
        ...(app.localeNames ?? {}),
    };

    const choose = (code: string) => {
        router.post(
            '/locale',
            { locale: code },
            {
                preserveScroll: true,
                onSuccess: () => window.location.reload(),
            },
        );
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="gap-2" aria-label="Language">
                    <Languages className="size-4" aria-hidden />
                    <span className="text-xs uppercase">{locale}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                {app.availableLocales.map((code) => (
                    <DropdownMenuItem
                        key={code}
                        onSelect={() => choose(code)}
                        className={code === locale ? 'font-semibold' : undefined}
                    >
                        {names[code] ?? code}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
