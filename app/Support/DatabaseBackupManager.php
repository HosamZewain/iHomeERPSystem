<?php

namespace App\Support;

use App\Models\DatabaseBackup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupManager
{
    public const STORAGE_DISK = 'local';
    public const STORAGE_DIRECTORY = 'database-backups';
    public const RESTORE_CONFIRMATION_WORD = 'استعادة';

    public function createBackup(User $user, ?string $notes = null): DatabaseBackup
    {
        $connection = $this->connectionConfig();
        $database = $this->databaseName($connection);
        $filename = 'database-backup-' . $database . '-' . now()->format('Ymd-His') . '.sql.gz';
        $path = self::STORAGE_DIRECTORY . '/' . $filename;

        Storage::disk(self::STORAGE_DISK)->makeDirectory(self::STORAGE_DIRECTORY);

        $process = new Process(
            $this->dumpCommand($connection),
            cwd: null,
            env: $this->processEnvironment($connection),
        );
        $process->setTimeout(600);
        $process->mustRun();

        $sql = $process->getOutput();

        if ($sql === '') {
            throw new RuntimeException('لم يتم إنشاء محتوى النسخة الاحتياطية. تحقق من اتصال قاعدة البيانات وصلاحيات المستخدم.');
        }

        $compressed = gzencode($sql, 9);

        if ($compressed === false) {
            throw new RuntimeException('تعذر ضغط ملف النسخة الاحتياطية.');
        }

        Storage::disk(self::STORAGE_DISK)->put($path, $compressed);

        return DatabaseBackup::create([
            'file_name' => $filename,
            'original_file_name' => $filename,
            'file_path' => $path,
            'file_size' => Storage::disk(self::STORAGE_DISK)->size($path),
            'source_type' => DatabaseBackup::SOURCE_GENERATED,
            'created_by' => $user->id,
            'notes' => $notes,
        ]);
    }

    public function storeUploadedBackup(UploadedFile $file, User $user, ?string $notes = null): DatabaseBackup
    {
        $originalName = $file->getClientOriginalName();
        $storedName = 'uploaded-backup-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(8)) . '.' . $this->storedExtensionFor($originalName);
        $path = $file->storeAs(self::STORAGE_DIRECTORY, $storedName, self::STORAGE_DISK);

        if (! $path) {
            throw new RuntimeException('تعذر حفظ ملف النسخة الاحتياطية المرفوع.');
        }

        return DatabaseBackup::create([
            'file_name' => basename($path),
            'original_file_name' => $originalName,
            'file_path' => $path,
            'file_size' => Storage::disk(self::STORAGE_DISK)->size($path),
            'source_type' => DatabaseBackup::SOURCE_UPLOADED,
            'created_by' => $user->id,
            'notes' => $notes,
        ]);
    }

    public function restoreBackup(DatabaseBackup $backup, User $user): void
    {
        $connection = $this->connectionConfig();
        $absolutePath = Storage::disk(self::STORAGE_DISK)->path($backup->file_path);

        if (! Storage::disk(self::STORAGE_DISK)->exists($backup->file_path)) {
            throw new RuntimeException('ملف النسخة الاحتياطية غير موجود على الخادم.');
        }

        $metadata = $backup->only([
            'file_name',
            'original_file_name',
            'file_path',
            'file_size',
            'source_type',
            'created_by',
            'notes',
        ]);

        $restoreSqlPath = null;
        $stream = null;

        try {
            Artisan::call('down');

            $restoreSqlPath = $this->prepareRestoreSqlFile($absolutePath);
            $stream = fopen($restoreSqlPath, 'r');

            if ($stream === false) {
                throw new RuntimeException('تعذر فتح ملف النسخة الاحتياطية للاستعادة.');
            }

            DB::disconnect();

            $process = new Process(
                $this->restoreCommand($connection),
                cwd: null,
                env: $this->processEnvironment($connection),
            );
            $process->setInput($stream);
            $process->setTimeout(1200);
            $process->mustRun();

            if (is_resource($stream)) {
                fclose($stream);
                $stream = null;
            }

            DB::purge();
            DB::reconnect();

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');

            DatabaseBackup::updateOrCreate(
                ['file_path' => $metadata['file_path']],
                array_merge($metadata, [
                    'restored_at' => now(),
                    'restored_by' => $user->id,
                ]),
            );
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($restoreSqlPath && $restoreSqlPath !== $absolutePath && file_exists($restoreSqlPath)) {
                @unlink($restoreSqlPath);
            }

            Artisan::call('up');
        }
    }

    public function requirementsSummary(): array
    {
        return [
            'driver' => (string) Config::get('database.default'),
            'mysqldump' => $this->binaryPath('mysqldump'),
            'mysql' => $this->binaryPath('mysql'),
        ];
    }

    public function acceptedExtensions(): array
    {
        return ['sql', 'gz'];
    }

    private function connectionConfig(): array
    {
        $default = (string) Config::get('database.default');
        $connection = (array) Config::get('database.connections.' . $default, []);

        if (! in_array($connection['driver'] ?? $default, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('ميزة النسخ الاحتياطي الحالية تدعم MySQL/MariaDB فقط.');
        }

        return $connection;
    }

    private function dumpCommand(array $connection): array
    {
        $binary = $this->binaryPath((string) env('DB_BACKUP_MYSQLDUMP_BINARY', 'mysqldump'));

        if (! $binary) {
            throw new RuntimeException('أداة mysqldump غير متاحة على الخادم. ثبّت عميل MySQL أو عرّف DB_BACKUP_MYSQLDUMP_BINARY.');
        }

        return array_merge([
            $binary,
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--events',
            '--add-drop-table',
            '--no-tablespaces',
            '--skip-lock-tables',
            '--databases',
            $this->databaseName($connection),
        ], $this->connectionArguments($connection), $this->userArguments($connection));
    }

    private function restoreCommand(array $connection): array
    {
        $binary = $this->binaryPath((string) env('DB_BACKUP_MYSQL_BINARY', 'mysql'));

        if (! $binary) {
            throw new RuntimeException('أداة mysql غير متاحة على الخادم. ثبّت عميل MySQL أو عرّف DB_BACKUP_MYSQL_BINARY.');
        }

        return array_merge([
            $binary,
            '--default-character-set=utf8mb4',
            $this->databaseName($connection),
        ], $this->connectionArguments($connection), $this->userArguments($connection));
    }

    private function connectionArguments(array $connection): array
    {
        if (! empty($connection['unix_socket'])) {
            return ['--socket=' . $connection['unix_socket']];
        }

        return [
            '--host=' . ($connection['host'] ?? '127.0.0.1'),
            '--port=' . ($connection['port'] ?? '3306'),
        ];
    }

    private function userArguments(array $connection): array
    {
        return [
            '--user=' . ($connection['username'] ?? ''),
        ];
    }

    private function processEnvironment(array $connection): array
    {
        $password = (string) ($connection['password'] ?? '');

        return $password === ''
            ? []
            : ['MYSQL_PWD' => $password];
    }

    private function databaseName(array $connection): string
    {
        $database = (string) ($connection['database'] ?? '');

        if ($database === '') {
            throw new RuntimeException('اسم قاعدة البيانات غير مضبوط.');
        }

        return $database;
    }

    private function binaryPath(string $binary): ?string
    {
        return (new ExecutableFinder())->find($binary, null, array_filter([
            dirname($binary) !== '.' ? dirname($binary) : null,
        ])) ?: null;
    }

    private function prepareRestoreSqlFile(string $absolutePath): string
    {
        if (! Str::endsWith(Str::lower($absolutePath), '.gz')) {
            return $absolutePath;
        }

        $tempPath = storage_path('app/' . self::STORAGE_DIRECTORY . '/restore-' . Str::uuid() . '.sql');
        $input = gzopen($absolutePath, 'rb');

        if ($input === false) {
            throw new RuntimeException('تعذر قراءة ملف النسخة الاحتياطية المضغوط.');
        }

        $output = fopen($tempPath, 'wb');

        if ($output === false) {
            gzclose($input);
            throw new RuntimeException('تعذر إنشاء ملف مؤقت للاستعادة.');
        }

        try {
            while (! gzeof($input)) {
                $chunk = gzread($input, 1024 * 1024);

                if ($chunk === false) {
                    throw new RuntimeException('تعذر فك ضغط ملف النسخة الاحتياطية.');
                }

                fwrite($output, $chunk);
            }
        } finally {
            gzclose($input);
            fclose($output);
        }

        return $tempPath;
    }

    private function storedExtensionFor(string $originalName): string
    {
        $lower = Str::lower($originalName);

        return Str::endsWith($lower, '.sql') ? 'sql' : 'gz';
    }
}
