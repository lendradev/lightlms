<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <title inertia>{{ config('app.name', 'LightLMS') }}</title>
        {{-- Applies the stored theme before first paint so the page never flashes the wrong scheme. --}}
        <script>
            (function () {
                var stored = null;
                try {
                    stored = window.localStorage.getItem('lightlms-theme');
                } catch (error) {
                    stored = null;
                }

                var theme = stored === 'light' || stored === 'dark' || stored === 'system' ? stored : 'system';
                var resolved =
                    theme === 'system'
                        ? window.matchMedia('(prefers-color-scheme: dark)').matches
                            ? 'dark'
                            : 'light'
                        : theme;

                document.documentElement.classList.toggle('dark', resolved === 'dark');
                document.documentElement.style.colorScheme = resolved;
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.tsx'])
        @inertiaHead
    </head>
    <body class="min-h-screen">
        @inertia
    </body>
</html>
