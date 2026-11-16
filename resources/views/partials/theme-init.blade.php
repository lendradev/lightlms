{{--
    Applies the stored theme before first paint so the page never flashes the
    wrong scheme. Used by the Inertia layout and by the standalone error page.
--}}
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
