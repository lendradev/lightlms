<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ $title ?? 'Something went wrong' }} · {{ config('app.name', 'LightLMS') }}</title>

        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-12">
            <div class="w-full max-w-md text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-xl bg-primary font-serif text-xl font-bold text-primary-foreground">
                    L
                </span>
                <h1 class="mt-6 font-serif text-2xl font-semibold tracking-tight">
                    {{ $title ?? 'Something went wrong' }}
                </h1>
                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                    {{ $message ?? 'The request could not be completed. Please try again.' }}
                </p>
                <a
                    href="{{ url('/') }}"
                    class="mt-6 inline-block rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                >
                    Back to the homepage
                </a>
            </div>
        </main>
    </body>
</html>
