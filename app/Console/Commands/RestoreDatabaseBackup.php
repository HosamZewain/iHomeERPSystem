<?php

namespace App\Console\Commands;

use App\Models\DatabaseBackup;
use App\Models\User;
use App\Support\DatabaseBackupManager;
use Illuminate\Console\Command;

class RestoreDatabaseBackup extends Command
{
    protected $signature = 'ihome:db-backup-restore {backup : Backup ID, file name, or relative file path} {--user-id=}';

    protected $description = 'Restore the database from a stored backup record.';

    public function handle(DatabaseBackupManager $manager): int
    {
        $backup = DatabaseBackup::query()
            ->whereKey($this->argument('backup'))
            ->orWhere('file_name', $this->argument('backup'))
            ->orWhere('file_path', $this->argument('backup'))
            ->first();

        if (! $backup) {
            $this->error('Backup record not found.');

            return self::FAILURE;
        }

        $user = $this->option('user-id')
            ? User::query()->find((int) $this->option('user-id'))
            : User::query()->where('role', 'admin')->orderBy('id')->first();

        if (! $user) {
            $this->error('No admin user was found to attribute the restore to.');

            return self::FAILURE;
        }

        if (! $this->confirm('This will replace the current database state. Continue?')) {
            $this->warn('Restore cancelled.');

            return self::INVALID;
        }

        try {
            $manager->restoreBackup($backup, $user);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Database restored successfully.');

        return self::SUCCESS;
    }
}
