import { Monitor, Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { applyTheme, readStoredTheme, type Theme } from '@/lib/theme';

const OPTIONS: { value: Theme; label: string; icon: typeof Sun }[] = [
    { value: 'light', label: 'Light', icon: Sun },
    { value: 'dark', label: 'Dark', icon: Moon },
    { value: 'system', label: 'System', icon: Monitor },
];

export function ThemeToggle() {
    const [theme, setTheme] = useState<Theme>('system');

    useEffect(() => {
        setTheme(readStoredTheme() ?? 'system');
    }, []);

    useEffect(() => {
        const media = window.matchMedia('(prefers-color-scheme: dark)');

        const sync = () => {
            if ((readStoredTheme() ?? 'system') === 'system') {
                applyTheme('system');
            }
        };

        media.addEventListener('change', sync);

        return () => media.removeEventListener('change', sync);
    }, []);

    const select = (value: Theme) => {
        setTheme(value);

        try {
            window.localStorage.setItem('lightlms-theme', value);
        } catch {
            // Storage can be blocked; the in-page toggle still works.
        }

        applyTheme(value);
    };

    const current = OPTIONS.find((option) => option.value === theme) ?? OPTIONS[2];
    const Icon = current.icon;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" aria-label={`Theme: ${current.label}`}>
                    <Icon aria-hidden />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-36">
                {OPTIONS.map((option) => (
                    <DropdownMenuItem
                        key={option.value}
                        onClick={() => select(option.value)}
                        className={option.value === theme ? 'bg-accent text-accent-foreground' : undefined}
                        aria-current={option.value === theme}
                    >
                        <option.icon className="size-4" aria-hidden />
                        {option.label}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
