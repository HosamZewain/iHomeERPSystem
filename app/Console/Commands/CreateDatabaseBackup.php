<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\DatabaseBackupManager;
use Illuminate\Console\Command;

class CreateDatabaseBackup extends Command
{
    protected $signature = 'ihome:db-backup-create {--user-id=} {--notes=}';

    protected $description = 'Create a full database backup and store it in the application backup directory.';

    public function handle(DatabaseBackupManager $manager): int
    {
        $user = $this->option('user-id')
            ? User::query()->find((int) $this->option('user-id'))
            : User::query()->where('role', 'admin')->orderBy('id')->first();

        if (! $user) {
            $this->error('No admin user was found to attribute the backup to.');

            return self::FAILURE;
        }

        try {
            $backup = $manager->createBackup($user, $this->option('notes'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup created successfully.');
        $this->line('File: ' . $backup->file_name);
        $this->line('Path: ' . $backup->file_path);
        $this->line('Size: ' . $backup->formattedFileSize());

        return self::SUCCESS;
    }
}
