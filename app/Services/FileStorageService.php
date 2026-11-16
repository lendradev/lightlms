<?php

namespace App\Services;

use App\Models\FileAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded files.
 *
 * Files never land in `public/`. The name on disk is generated on the server,
 * so a crafted original name cannot decide where a file ends up, and the
 * original name is kept in the database for display only.
 */
class FileStorageService
{
    /**
     * The MIME types a learner or instructor may upload.
     *
     * @var list<string>
     */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'text/plain',
        'text/markdown',
        'text/csv',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * The largest upload we accept, in kilobytes.
     */
    public const MAX_SIZE_KB = 10240;

    public const DISK = 'local';

    /**
     * Store an upload and record it.
     */
    public function store(UploadedFile $upload, User $uploader, ?int $lessonId = null): FileAsset
    {
        $path = $this->storeOnDisk($upload);

        return FileAsset::query()->create([
            'lesson_id' => $lessonId,
            'uploader_id' => $uploader->id,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => $this->displayName($upload),
            'mime_type' => $upload->getMimeType(),
            'size' => $upload->getSize() ?: 0,
        ]);
    }

    /**
     * Delete a stored file and its record.
     */
    public function delete(FileAsset $file): void
    {
        Storage::disk($file->disk)->delete($file->path);

        $file->delete();
    }

    /**
     * The path a file is stored at, relative to the disk root.
     */
    public function storeOnDisk(UploadedFile $upload): string
    {
        $extension = strtolower($upload->guessExtension() ?: 'bin');
        $name = Str::uuid()->toString().'.'.$extension;
        $directory = now()->format('Y/m');

        Storage::disk(self::DISK)->putFileAs($directory, $upload, $name);

        return $directory.'/'.$name;
    }

    /**
     * The name shown to a person. The extension is kept, the rest is trimmed.
     */
    private function displayName(UploadedFile $upload): string
    {
        $original = $upload->getClientOriginalName();
        $extension = strtolower($upload->guessExtension() ?: 'bin');

        $base = pathinfo($original, PATHINFO_FILENAME);
        $base = Str::limit($base, 120, '');

        if ($base === '') {
            $base = 'file';
        }

        return $base.'.'.$extension;
    }
}
