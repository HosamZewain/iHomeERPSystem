<?php

namespace App\Livewire\Settings\DatabaseBackups;

use App\Models\DatabaseBackup;
use App\Support\DatabaseBackupManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class DatabaseBackupList extends Component
{
    use WithFileUploads;

    public $backupUpload = null;
    public string $uploadNotes = '';
    public ?int $restoreBackupId = null;
    public string $restoreConfirmation = '';

    public function createBackup(): void
    {
        try {
            app(DatabaseBackupManager::class)->createBackup(auth()->user());
            session()->flash('success', 'تم إنشاء النسخة الاحتياطية بنجاح. يمكنك تنزيلها من القائمة أدناه.');
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function uploadBackup(): void
    {
        $this->validate([
            'backupUpload' => [
                'required',
                'file',
                'max:102400',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $name = Str::lower($value->getClientOriginalName());

                    if (! Str::endsWith($name, ['.sql', '.gz'])) {
                        $fail('نوع الملف غير مدعوم. الصيغ المسموح بها هي .sql أو .gz فقط.');
                    }
                },
            ],
            'uploadNotes' => ['nullable', 'string', 'max:1000'],
        ], [], [
            'backupUpload' => 'ملف النسخة الاحتياطية',
            'uploadNotes' => 'ملاحظات الرفع',
        ]);

        try {
            app(DatabaseBackupManager::class)->storeUploadedBackup($this->backupUpload, auth()->user(), $this->uploadNotes ?: null);
            $this->reset(['backupUpload', 'uploadNotes']);
            session()->flash('success', 'تم رفع ملف النسخة الاحتياطية وإضافته إلى القائمة.');
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function startRestore(int $backupId): void
    {
        $this->restoreBackupId = $backupId;
        $this->restoreConfirmation = '';
        $this->resetValidation(['restoreConfirmation']);
    }

    public function cancelRestore(): void
    {
        $this->restoreBackupId = null;
        $this->restoreConfirmation = '';
        $this->resetValidation(['restoreConfirmation']);
    }

    public function restoreSelectedBackup(): void
    {
        $backup = DatabaseBackup::query()->findOrFail($this->restoreBackupId);

        $this->validate([
            'restoreConfirmation' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! in_array(trim((string) $value), [DatabaseBackupManager::RESTORE_CONFIRMATION_WORD, 'RESTORE'], true)) {
                        $fail('اكتب كلمة "استعادة" أو "RESTORE" لتأكيد عملية الاستعادة الكاملة.');
                    }
                },
            ],
        ], [], [
            'restoreConfirmation' => 'تأكيد الاستعادة',
        ]);

        try {
            app(DatabaseBackupManager::class)->restoreBackup($backup, auth()->user());
            $this->cancelRestore();
            session()->flash('success', 'تمت استعادة قاعدة البيانات بنجاح. قد تحتاج إلى تسجيل الدخول مرة أخرى إذا تغيّرت جلسة المستخدم الحالية.');
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function render()
    {
        $manager = app(DatabaseBackupManager::class);

        return view('livewire.settings.database-backups.database-backup-list', [
            'backups' => DatabaseBackup::query()->with(['creator', 'restorer'])->latest()->get(),
            'requirements' => $manager->requirementsSummary(),
            'acceptedExtensions' => $manager->acceptedExtensions(),
            'restoreKeyword' => DatabaseBackupManager::RESTORE_CONFIRMATION_WORD,
            'storageDisk' => Storage::disk(DatabaseBackupManager::STORAGE_DISK),
        ])->layout('layouts.app', ['header' => 'النسخ الاحتياطية']);
    }
}
