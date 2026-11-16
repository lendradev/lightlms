export type Theme = 'light' | 'dark' | 'system';

const STORAGE_KEY = 'lightlms-theme';

export function readStoredTheme(): Theme | null {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);
        return value === 'light' || value === 'dark' || value === 'system' ? value : null;
    } catch {
        return null;
    }
}

export function prefersDark(): boolean {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

export function resolveTheme(theme: Theme): 'light' | 'dark' {
    if (theme === 'system') {
        return prefersDark() ? 'dark' : 'light';
    }

    return theme;
}

export function applyTheme(theme: Theme): void {
    const resolved = resolveTheme(theme);
    const root = document.documentElement;

    root.classList.toggle('dark', resolved === 'dark');
    root.style.colorScheme = resolved;
}
