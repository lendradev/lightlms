<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backups) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Backup/Index', [
            'backups' => $this->available(),
        ]);
    }

    public function store(): RedirectResponse
    {
        $extension = $this->backups->extension();
        $path = storage_path('app/backups/lightlms-'.now()->format('Ymd-His').'-'.uniqid().".{$extension}");

        $this->backups->create($path);

        return back()->with('success', 'Backup created.');
    }

    public function download(string $filename): StreamedResponse
    {
        $path = $this->resolve($filename);

        return response()->streamDownload(function () use ($path): void {
            $handle = fopen($path, 'rb');
            while (! feof($handle)) {
                echo fread($handle, 1024 * 1024);
                flush();
            }
            fclose($handle);
        }, basename($path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    public function restore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'archive' => ['required', 'file', 'mimes:zip,tar,gz,tgz'],
        ]);

        $path = storage_path('app/backups/restore-'.now()->format('Ymd-His').'.upload');
        $validated['archive']->moveTo($path);

        try {
            $this->backups->restore($path);
        } finally {
            File::delete($path);
        }

        return back()->with('success', 'Backup restored.');
    }

    public function destroy(string $filename): RedirectResponse
    {
        File::delete($this->resolve($filename));

        return back()->with('success', 'Backup deleted.');
    }

    /** @return list<array{name: string, size: int, created_at: string}> */
    private function available(): array
    {
        $directory = storage_path('app/backups');

        if (! File::isDirectory($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn ($file) => in_array($file->getExtension(), ['zip', 'tar', 'gz', 'tgz'], true))
            ->map(fn ($file) => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => (string) $file->getMTime(),
            ])
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    private function resolve(string $filename): string
    {
        $directory = storage_path('app/backups');
        $path = $directory.'/'.basename($filename);

        if (! File::exists($path) || ! str_starts_with(realpath($path) ?: '', realpath($directory) ?: $directory)) {
            abort(404);
        }

        return $path;
    }
}
