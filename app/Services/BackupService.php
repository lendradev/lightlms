<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PharData;
use RuntimeException;
use ZipArchive;

/**
 * Exports and imports a full site backup.
 *
 * A backup holds a database dump and the storage tree. The dump is built with
 * the framework's own query builder, so it stays driver independent — no
 * shell-out to mysqldump or pg_dump.
 */
class BackupService
{
    /**
     * Tables dumped in dependency order. Foreign keys are disabled while the
     * dump is written, so the order only matters for readability.
     *
     * @var list<string>
     */
    private const TABLES = [
        'users',
        'settings',
        'invitations',
        'categories',
        'tags',
        'courses',
        'course_tag',
        'course_user',
        'modules',
        'lessons',
        'lesson_user',
        'files',
        'assignments',
        'submissions',
        'quizzes',
        'questions',
        'quiz_attempts',
        'forums',
        'forum_posts',
        'announcements',
        'notifications',
        'grades',
    ];

    /** The archive extension that this installation produces. */
    public function extension(): string
    {
        return class_exists(ZipArchive::class) ? 'zip' : 'tar';
    }

    public function create(string $destination): string
    {
        File::ensureDirectoryExists(dirname($destination));

        $payload = [
            'manifest.json' => (string) json_encode($this->manifest(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'database.sql' => $this->dump(),
        ] + $this->storageFiles();

        if (class_exists(ZipArchive::class)) {
            $archive = new ZipArchive;
            if ($archive->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("Unable to open {$destination} for writing.");
            }

            foreach ($payload as $name => $contents) {
                $archive->addFromString($name, $contents);
            }

            $archive->close();

            return $destination;
        }

        $tar = new PharData($destination, 0, null, \Phar::TAR);
        foreach ($payload as $name => $contents) {
            $tar->addFromString($name, $contents);
        }
        unset($tar);

        return $destination;
    }

    public function restore(string $archivePath): void
    {
        $storageRoot = storage_path('app');
        File::ensureDirectoryExists($storageRoot);

        if (class_exists(ZipArchive::class) && str_ends_with($archivePath, '.zip')) {
            $this->restoreZip($archivePath, $storageRoot);

            return;
        }

        $this->restoreTar($archivePath, $storageRoot);
    }

    private function restoreZip(string $archivePath, string $storageRoot): void
    {
        $archive = new ZipArchive;
        if ($archive->open($archivePath) !== true) {
            throw new RuntimeException('The uploaded file is not a valid backup archive.');
        }

        $dump = $archive->getFromName('database.sql');
        if ($dump === false) {
            $archive->close();
            throw new RuntimeException('The archive does not contain a database dump.');
        }

        for ($i = 0; $i < $archive->numFiles; $i++) {
            $name = $archive->getNameIndex($i);
            if ($name === false || ! str_starts_with($name, 'storage/') || str_contains($name, '..')) {
                continue;
            }

            $target = $storageRoot.'/'.substr($name, strlen('storage/'));
            File::ensureDirectoryExists(dirname($target));
            File::put($target, (string) $archive->getFromIndex($i));
        }

        $archive->close();

        $this->load((string) $dump);
    }

    private function restoreTar(string $archivePath, string $storageRoot): void
    {
        $tar = new PharData($archivePath);
        $dump = $tar['database.sql'] ?? null;

        if ($dump === null) {
            throw new RuntimeException('The archive does not contain a database dump.');
        }

        foreach ($tar as $file) {
            $name = $file->getFilename();

            if (! str_starts_with($name, 'storage/') || str_contains($name, '..')) {
                continue;
            }

            $target = $storageRoot.'/'.substr($name, strlen('storage/'));
            File::ensureDirectoryExists(dirname($target));
            File::put($target, $file->getContent());
        }

        $this->load((string) $dump);
    }

    private function dump(): string
    {
        $sql = "-- LightLMS backup\n-- Generated at ".now()->toIso8601String()."\n\n";

        $this->disableForeignKeys();

        foreach (self::TABLES as $table) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->get();

            if ($rows->isEmpty()) {
                $sql .= "-- table {$table} is empty\n";

                continue;
            }

            $columns = array_keys((array) $rows->first());

            $sql .= "DELETE FROM {$table};\n";

            foreach ($rows as $row) {
                $values = array_map(
                    fn ($value) => $value === null ? 'NULL' : $this->quote((string) $value),
                    (array) $row,
                );
                $sql .= "INSERT INTO {$table} (".implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n";
            }

            $sql .= "\n";
        }

        $this->enableForeignKeys();

        return $sql;
    }

    private function load(string $sql): void
    {
        $this->disableForeignKeys();

        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) {
                continue;
            }

            DB::unprepared($statement);
        }

        $this->enableForeignKeys();
    }

    private function disableForeignKeys(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=0'),
            'pgsql' => DB::statement('SET session_replication_role = replica'),
            default => DB::statement('PRAGMA foreign_keys = OFF'),
        };
    }

    private function enableForeignKeys(): void
    {
        match (DB::connection()->getDriverName()) {
            'mysql' => DB::statement('SET FOREIGN_KEY_CHECKS=1'),
            'pgsql' => DB::statement('SET session_replication_role = origin'),
            default => DB::statement('PRAGMA foreign_keys = ON'),
        };
    }

    private function quote(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }

    /** @return array<string, mixed> */
    private function manifest(): array
    {
        return [
            'application' => config('app.name'),
            'generated_at' => now()->toIso8601String(),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'site_name' => Setting::get('site.name'),
            'tables' => self::TABLES,
        ];
    }

    /**
     * Files under storage/app, excluding the backup folder itself so archives
     * never nest inside one another.
     *
     * @return array<string, string>
     */
    private function storageFiles(): array
    {
        $files = [];
        $storageRoot = storage_path('app');
        $backups = storage_path('app/backups');

        if (! File::isDirectory($storageRoot)) {
            return $files;
        }

        foreach (File::allFiles($storageRoot) as $file) {
            if (str_starts_with($file->getPathname(), $backups.DIRECTORY_SEPARATOR)) {
                continue;
            }

            $files['storage/'.str_replace('\\', '/', $file->getRelativePathname())] =
                (string) file_get_contents($file->getPathname());
        }

        return $files;
    }
}
