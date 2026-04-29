<?php

namespace Tests\Feature;

use App\Livewire\Settings\DatabaseBackups\DatabaseBackupList;
use App\Models\DatabaseBackup;
use App\Models\User;
use App\Support\DatabaseBackupManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery\MockInterface;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_management_page_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'manager']);

        $this->actingAs($admin)
            ->get(route('settings.backups'))
            ->assertOk()
            ->assertSee('النسخ الاحتياطية');

        $this->actingAs($manager)
            ->get(route('settings.backups'))
            ->assertForbidden();
    }

    public function test_admin_can_download_stored_backup_file(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('local')->put('database-backups/demo-backup.sql.gz', 'backup-content');

        $backup = DatabaseBackup::factory()->create([
            'file_name' => 'demo-backup.sql.gz',
            'original_file_name' => 'demo-backup.sql.gz',
            'file_path' => 'database-backups/demo-backup.sql.gz',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('settings.backups.download', $backup))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename=demo-backup.sql.gz');
    }

    public function test_admin_can_upload_backup_file_and_store_metadata(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($admin)
            ->test(DatabaseBackupList::class)
            ->set('backupUpload', UploadedFile::fake()->create('restore-point.sql', 32, 'application/sql'))
            ->set('uploadNotes', 'نسخة من العميل')
            ->call('uploadBackup')
            ->assertHasNoErrors();

        $backup = DatabaseBackup::query()->firstOrFail();

        $this->assertSame(DatabaseBackup::SOURCE_UPLOADED, $backup->source_type);
        $this->assertSame('restore-point.sql', $backup->original_file_name);
        Storage::disk('local')->assertExists($backup->file_path);
    }

    public function test_admin_can_trigger_backup_creation_from_ui(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $backup = DatabaseBackup::factory()->create(['created_by' => $admin->id]);

        $this->mock(DatabaseBackupManager::class, function (MockInterface $mock) use ($admin, $backup): void {
            $mock->shouldReceive('requirementsSummary')->andReturn([
                'driver' => 'mysql',
                'mysqldump' => '/usr/bin/mysqldump',
                'mysql' => '/usr/bin/mysql',
            ]);
            $mock->shouldReceive('acceptedExtensions')->andReturn(['sql', 'gz']);
            $mock->shouldReceive('createBackup')->once()->withArgs(fn ($user) => $user->is($admin))->andReturn($backup);
        });

        Livewire::actingAs($admin)
            ->test(DatabaseBackupList::class)
            ->call('createBackup')
            ->assertHasNoErrors();
    }

    public function test_restore_requires_explicit_confirmation_word(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $backup = DatabaseBackup::factory()->create(['created_by' => $admin->id]);

        $this->mock(DatabaseBackupManager::class, function (MockInterface $mock): void {
            $mock->shouldReceive('requirementsSummary')->andReturn([
                'driver' => 'mysql',
                'mysqldump' => '/usr/bin/mysqldump',
                'mysql' => '/usr/bin/mysql',
            ]);
            $mock->shouldReceive('acceptedExtensions')->andReturn(['sql', 'gz']);
            $mock->shouldNotReceive('restoreBackup');
        });

        Livewire::actingAs($admin)
            ->test(DatabaseBackupList::class)
            ->call('startRestore', $backup->id)
            ->set('restoreConfirmation', 'خطأ')
            ->call('restoreSelectedBackup')
            ->assertHasErrors(['restoreConfirmation']);
    }

    public function test_admin_can_restore_backup_after_confirmation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $backup = DatabaseBackup::factory()->create(['created_by' => $admin->id]);

        $this->mock(DatabaseBackupManager::class, function (MockInterface $mock) use ($admin, $backup): void {
            $mock->shouldReceive('requirementsSummary')->andReturn([
                'driver' => 'mysql',
                'mysqldump' => '/usr/bin/mysqldump',
                'mysql' => '/usr/bin/mysql',
            ]);
            $mock->shouldReceive('acceptedExtensions')->andReturn(['sql', 'gz']);
            $mock->shouldReceive('restoreBackup')
                ->once()
                ->withArgs(fn ($passedBackup, $user) => $passedBackup->is($backup) && $user->is($admin));
        });

        Livewire::actingAs($admin)
            ->test(DatabaseBackupList::class)
            ->call('startRestore', $backup->id)
            ->set('restoreConfirmation', DatabaseBackupManager::RESTORE_CONFIRMATION_WORD)
            ->call('restoreSelectedBackup')
            ->assertHasNoErrors();
    }
}
