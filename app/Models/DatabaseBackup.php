<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseBackup extends Model
{
    use HasFactory;

    public const SOURCE_GENERATED = 'generated';
    public const SOURCE_UPLOADED = 'uploaded';

    protected $fillable = [
        'file_name',
        'original_file_name',
        'file_path',
        'file_size',
        'source_type',
        'created_by',
        'restored_at',
        'restored_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'restored_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function sourceLabel(): string
    {
        return match ($this->source_type) {
            self::SOURCE_GENERATED => 'تم إنشاؤه من النظام',
            self::SOURCE_UPLOADED => 'تم رفعه',
            default => $this->source_type,
        };
    }

    public function formattedFileSize(): string
    {
        $size = max((int) $this->file_size, 0);

        if ($size >= 1024 * 1024 * 1024) {
            return number_format($size / (1024 * 1024 * 1024), 2) . ' GB';
        }

        if ($size >= 1024 * 1024) {
            return number_format($size / (1024 * 1024), 2) . ' MB';
        }

        if ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        }

        return $size . ' B';
    }
}
