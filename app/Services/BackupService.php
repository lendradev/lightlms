<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Phar;
use PharData;
use RecursiveIteratorIterator;
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

        $tar = new PharData($destination, 0, null, Phar::TAR);
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
            if ($name === false || ! str_starts_with($name, 'storage/')) {
                continue;
            }

            $target = $storageRoot.'/'.substr($name, strlen('storage/'));
            $this->writeFile($target, (string) $archive->getFromIndex($i));
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

        foreach (new RecursiveIteratorIterator($tar) as $file) {
            $name = $this->relativeName($archivePath, $file->getPathname());

            if ($name === null || ! str_starts_with($name, 'storage/')) {
                continue;
            }

            $target = $storageRoot.'/'.substr($name, strlen('storage/'));
            $this->writeFile($target, $file->getContent());
        }

        $this->load($dump->getContent());
    }

    /**
     * The path of an archive member, relative to the archive root.
     *
     * `getFilename()` on a PharData member is the basename alone, which loses
     * the folder structure, so the path is taken from the phar URI instead.
     */
    private function relativeName(string $archivePath, string $memberPath): ?string
    {
        $prefix = 'phar://'.realpath($archivePath);
        if (! str_starts_with($memberPath, $prefix)) {
            return null;
        }

        $name = substr($memberPath, strlen($prefix));
        $name = ltrim($name, '/');

        return $name === '' ? null : $name;
    }

    /**
     * Write an extracted file, refusing any path that escapes the storage root.
     */
    private function writeFile(string $target, string $contents): void
    {
        $normalised = str_replace('\\', '/', $target);

        if (str_contains($normalised, '../') || str_ends_with($normalised, '/..')) {
            return;
        }

        File::ensureDirectoryExists(dirname($target));
        File::put($target, $contents);
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

            $sql .= 'DELETE FROM '.$this->quoteIdentifier($table).";\n";

            foreach ($rows as $row) {
                $values = array_map(
                    fn ($value) => $value === null ? 'NULL' : $this->quote((string) $value),
                    (array) $row,
                );
                $columnList = implode(', ', array_map($this->quoteIdentifier(...), $columns));
                $sql .= 'INSERT INTO '.$this->quoteIdentifier($table).' ('.$columnList.') VALUES ('.implode(', ', $values).");\n";
            }

            $sql .= "\n";
        }

        $this->enableForeignKeys();

        return $sql;
    }

    /**
     * Run the statements from a dump.
     *
     * A dump only ever holds a `DELETE` and an `INSERT` per table, and only
     * for the tables this service knows. Anything else in the archive is
     * refused, so a crafted backup cannot drop a table or rewrite rows that
     * the dump format never writes.
     *
     * Every statement is checked before the first one runs. A dump that
     * carries a statement this restore refuses leaves the database untouched
     * rather than half-restored.
     */
    private function load(string $sql): void
    {
        $statements = [];

        foreach ($this->splitStatements($sql) as $statement) {
            $statement = trim($statement);

            if ($statement === '') {
                continue;
            }

            if (! $this->isRestoreStatement($statement)) {
                throw new RuntimeException('The archive contains a statement this restore does not run.');
            }

            $statements[] = $statement;
        }

        $this->disableForeignKeys();

        try {
            // A statement that fails leaves PostgreSQL with an aborted
            // transaction, so the whole run is one transaction that rolls
            // back. The database is then left as it was.
            DB::transaction(function () use ($statements): void {
                foreach ($statements as $statement) {
                    DB::unprepared($statement);
                }
            });
        } finally {
            $this->enableForeignKeys();
        }
    }

    /**
     * Split a dump into its statements.
     *
     * A statement ends at a semicolon that is not inside a string literal,
     * and a comment runs to the end of its line. Both are tracked in one
     * pass, so a crafted archive can neither hide a statement behind a
     * semicolon that only looks like part of a value nor hide one inside a
     * comment.
     *
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];

            if ($inString) {
                $current .= $char;

                if ($char === "'") {
                    // A doubled quote is an escaped quote, not the end of the literal.
                    if ($i + 1 < $length && $sql[$i + 1] === "'") {
                        $current .= "'";
                        $i++;

                        continue;
                    }

                    $inString = false;
                }

                continue;
            }

            if ($char === '-' && $i + 1 < $length && $sql[$i + 1] === '-') {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }

                $current .= ' ';

                continue;
            }

            if ($char === "'") {
                $inString = true;
                $current .= $char;

                continue;
            }

            if ($char === ';') {
                $statements[] = $current;
                $current = '';

                continue;
            }

            $current .= $char;
        }

        if ($inString) {
            throw new RuntimeException('The archive contains an unterminated string.');
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    /**
     * True when the statement is a delete or an insert for a known table.
     *
     * The whole statement has to match. A prefix match would let a crafted
     * archive trail a second statement behind a valid one, and the second
     * statement would run as part of the first. The values of an insert have
     * to be plain literals too, so a value cannot smuggle a subquery in.
     */
    private function isRestoreStatement(string $statement): bool
    {
        // The identifier may be double quoted or, on MySQL, in backticks.
        $identifier = '(?:`|\")?([A-Za-z0-9_]+)(?:`|\")?';

        if (preg_match('/^DELETE\s+FROM\s+'.$identifier.'\s*$/i', $statement, $matches)) {
            return in_array(strtolower($matches[1]), self::TABLES, true);
        }

        if (! preg_match('/^INSERT\s+INTO\s+'.$identifier.'\s*\(([^()]*)\)\s*VALUES\s*\((.*)\)\s*$/is', $statement, $matches)) {
            return false;
        }

        if (! in_array(strtolower($matches[1]), self::TABLES, true)) {
            return false;
        }

        return $this->holdsOnlyLiteralValues($matches[2]);
    }

    /**
     * True when every value in a value list is a plain literal.
     *
     * A dump writes numbers, `NULL`, and quoted strings, and nothing else. A
     * parenthesised expression would be a subquery, which reads rows the
     * dump format never reads.
     */
    private function holdsOnlyLiteralValues(string $values): bool
    {
        $length = strlen($values);
        $inString = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $values[$i];

            if ($inString) {
                if ($char === "'") {
                    if ($i + 1 < $length && $values[$i + 1] === "'") {
                        $i++;

                        continue;
                    }

                    $inString = false;
                }

                continue;
            }

            if ($char === "'") {
                $inString = true;

                continue;
            }

            if ($char === '(' || $char === ')') {
                return false;
            }
        }

        return ! $inString;
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

    /**
     * A string literal, quoted for the driver in use.
     *
     * MySQL treats a backslash as an escape character, so a backslash has to
     * be doubled there as well. The other drivers use the SQL standard of
     * doubling the quote.
     */
    private function quote(string $value): string
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return "'".str_replace(['\\', "'"], ['\\\\', "''"], $value)."'";
        }

        return "'".str_replace("'", "''", $value)."'";
    }

    /**
     * An identifier, quoted for the driver in use.
     *
     * Some columns are reserved words, `key` and `group` among them, so they
     * cannot be written unquoted.
     */
    private function quoteIdentifier(string $identifier): string
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return '`'.str_replace('`', '``', $identifier).'`';
        }

        return '"'.str_replace('"', '""', $identifier).'"';
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
