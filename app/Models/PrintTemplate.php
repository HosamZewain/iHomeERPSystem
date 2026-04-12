<?php

namespace App\Models;

use App\Support\PrintTemplateSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrintTemplate extends Model
{
    use HasFactory;

    public const TYPE_QUOTATION = 'quotation';

    public const TYPE_SALES_INVOICE = 'sales_invoice';

    public const TYPE_PARTNER_SETTLEMENT = 'partner_settlement';

    protected $fillable = [
        'name',
        'code',
        'document_type',
        'is_active',
        'is_default',
        'title',
        'notes',
        'sort_order',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PrintTemplate $template) {
            if (blank($template->code)) {
                $template->code = self::generateCode($template->document_type);
            }
        });

        static::saved(function (PrintTemplate $template) {
            if ($template->is_default) {
                self::query()
                    ->where('document_type', $template->document_type)
                    ->whereKeyNot($template->getKey())
                    ->update(['is_default' => false]);
            }
        });
    }

    public function scopeForDocumentType(Builder $query, string $documentType): Builder
    {
        return $query->where('document_type', $documentType);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function documentTypes(): array
    {
        return [
            self::TYPE_QUOTATION => 'عرض سعر',
            self::TYPE_SALES_INVOICE => 'فاتورة بيع',
            self::TYPE_PARTNER_SETTLEMENT => 'مستند عمولة شريك',
        ];
    }

    public static function generateCode(string $documentType): string
    {
        $prefix = match ($documentType) {
            self::TYPE_QUOTATION => 'QUO',
            self::TYPE_SALES_INVOICE => 'INV',
            self::TYPE_PARTNER_SETTLEMENT => 'SET',
            default => 'DOC',
        };

        do {
            $code = $prefix.'-TPL-'.Str::upper(Str::random(6));
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public static function resolveForDocument(string $documentType, ?int $templateId = null): self
    {
        if ($templateId) {
            $template = self::query()
                ->forDocumentType($documentType)
                ->active()
                ->whereKey($templateId)
                ->first();

            if ($template) {
                return $template;
            }
        }

        return self::query()
            ->forDocumentType($documentType)
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first()
            ?? self::fallback($documentType);
    }

    public static function fallback(string $documentType): self
    {
        return new self([
            'name' => self::documentTypes()[$documentType] ?? 'قالب طباعة',
            'code' => 'fallback-'.$documentType,
            'document_type' => $documentType,
            'is_active' => true,
            'is_default' => true,
            'title' => self::defaultTitleFor($documentType),
            'settings' => PrintTemplateSettings::templateDefaults($documentType),
        ]);
    }

    public function resolvedSettings(): array
    {
        $globalSettings = PrintTemplateSettings::all();
        $settings = array_replace_recursive(
            PrintTemplateSettings::defaults(),
            $globalSettings,
            $this->settings ?? [],
        );

        data_set($settings, $this->document_type.'.title', $this->title);

        if (PrintTemplateSettings::truthy($settings, 'company.use_global_identity')) {
            foreach (PrintTemplateSettings::companyIdentityPaths() as $path) {
                if (blank(data_get($settings, 'company.'.$path))) {
                    data_set($settings, 'company.'.$path, data_get($globalSettings, 'company.'.$path));
                }
            }
        }

        return $settings;
    }

    public static function defaultTitleFor(string $documentType): string
    {
        return match ($documentType) {
            self::TYPE_SALES_INVOICE => 'فاتورة بيع',
            self::TYPE_PARTNER_SETTLEMENT => 'مستند عمولة شريك',
            default => 'عرض سعر',
        };
    }

    public function documentTypeLabel(): string
    {
        return self::documentTypes()[$this->document_type] ?? $this->document_type;
    }
}
