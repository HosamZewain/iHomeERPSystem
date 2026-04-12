<?php

namespace App\Support;

use App\Models\PrintTemplate;
use App\Models\Setting;

class PrintTemplateSettings
{
    private const PREFIX = 'print.';

    public static function defaults(): array
    {
        return [
            'company' => [
                'use_global_identity' => true,
                'name' => 'iHome',
                'logo_path' => '',
                'header_image_path' => '',
                'footer_image_path' => '',
                'show_logo' => false,
                'show_header_image' => false,
                'show_footer_image' => false,
                'phone' => '',
                'email' => '',
                'address' => '',
                'website' => '',
                'tax_number' => '',
                'registration_number' => '',
                'currency_label' => 'ج.م',
                'show_phone' => true,
                'show_email' => true,
                'show_address' => true,
                'show_website' => false,
                'show_tax_number' => false,
                'show_registration_number' => false,
            ],
            'general' => [
                'footer_text' => 'iHome - حلول المنازل الذكية',
                'thank_you_message' => 'شكراً لاختياركم iHome.',
                'disclaimer' => '',
                'show_thank_you_message' => true,
                'show_disclaimer' => false,
            ],
            'layout' => [
                'paper_size' => 'A4',
                'header_alignment' => 'split',
                'spacing' => 'normal',
                'font_size' => 'normal',
                'table_density' => 'normal',
                'logo_size' => 'medium',
                'margin' => 'normal',
            ],
            'quotation' => [
                'title' => 'عرض سعر',
                'footer_text' => 'هذا العرض لا يؤثر على المخزون ولا يعتبر فاتورة بيع أو حركة مخزون.',
                'terms' => 'الأسعار صالحة حسب الاتفاق وتخضع لتوفر المنتجات وقت التنفيذ.',
                'installation_item_name' => 'خدمة التركيب',
                'show_customer_name' => true,
                'show_customer_phone' => true,
                'show_customer_email' => true,
                'show_customer_address' => true,
                'show_number' => true,
                'show_date' => true,
                'show_status' => true,
                'show_creator' => true,
                'show_product_images' => true,
                'show_item_discounts' => true,
                'show_subtotal' => true,
                'show_invoice_discount' => true,
                'show_installation' => true,
                'show_total' => true,
                'show_notes' => true,
                'show_terms' => true,
            ],
            'sales_invoice' => [
                'title' => 'فاتورة بيع',
                'footer_text' => 'إجمالي فاتورة العميل هو المبلغ المستحق حسب البنود والخصومات الموضحة في هذه الفاتورة.',
                'installation_item_name' => 'خدمة التركيب',
                'show_customer_name' => true,
                'show_customer_phone' => true,
                'show_customer_email' => true,
                'show_customer_address' => true,
                'show_number' => true,
                'show_date' => true,
                'show_status' => true,
                'show_creator' => true,
                'show_quotation_reference' => true,
                'show_product_images' => true,
                'show_item_discounts' => true,
                'show_subtotal' => true,
                'show_invoice_discount' => true,
                'show_installation' => true,
                'show_gross_total' => true,
                'show_notes' => true,
            ],
            'warranty' => [
                'enabled' => false,
                'title' => 'شروط الضمان',
                'body' => "يرجى الاحتفاظ بالفاتورة أو عرض السعر للرجوع إليه عند طلب خدمة الضمان.\nيخضع الضمان لشروط الشركة المصنعة وسياسة iHome.",
                'footer_text' => '',
            ],
        ];
    }

    public static function templateDefaults(string $documentType): array
    {
        $settings = self::defaults();

        if ($documentType === PrintTemplate::TYPE_SALES_INVOICE) {
            $settings['sales_invoice']['title'] = 'فاتورة بيع';
        }

        if ($documentType === PrintTemplate::TYPE_QUOTATION) {
            $settings['quotation']['title'] = 'عرض سعر';
        }

        return $settings;
    }

    public static function all(): array
    {
        $settings = self::defaults();

        Setting::query()
            ->where('key', 'like', self::PREFIX.'%')
            ->pluck('value', 'key')
            ->each(function (?string $value, string $key) use (&$settings) {
                $path = substr($key, strlen(self::PREFIX));
                data_set($settings, $path, self::castStoredValue($path, $value));
            });

        foreach (self::legacyCompanySettingMap() as $path => $legacyKey) {
            if (blank(data_get($settings, $path))) {
                data_set($settings, $path, Setting::get($legacyKey, data_get($settings, $path)));
            }
        }

        return $settings;
    }

    public static function save(array $settings): void
    {
        foreach (self::flatten(self::defaults()) as $path => $default) {
            $value = data_get($settings, $path, $default);
            Setting::set(self::PREFIX.$path, self::serializeValue($path, $value));
        }

        foreach (self::legacyCompanySettingMap() as $path => $legacyKey) {
            Setting::set($legacyKey, (string) data_get($settings, $path, ''));
        }
    }

    public static function seedDefaults(): void
    {
        $settings = self::all();
        self::save($settings);
    }

    public static function seedDefaultTemplates(): void
    {
        foreach ([PrintTemplate::TYPE_QUOTATION, PrintTemplate::TYPE_SALES_INVOICE] as $documentType) {
            $template = PrintTemplate::query()->firstOrNew([
                'code' => $documentType.'-default',
            ]);

            $template->fill([
                'name' => 'القالب الافتراضي - '.PrintTemplate::documentTypes()[$documentType],
                'document_type' => $documentType,
                'is_active' => true,
                'is_default' => true,
                'title' => PrintTemplate::defaultTitleFor($documentType),
                'notes' => 'قالب افتراضي يتم إنشاؤه من إعدادات الطباعة العامة.',
                'sort_order' => 0,
                'settings' => array_replace_recursive(self::templateDefaults($documentType), self::all()),
            ]);
            $template->save();
        }
    }

    public static function options(): array
    {
        return [
            'paper_size' => [
                'A4' => 'A4',
            ],
            'header_alignment' => [
                'split' => 'يمين/يسار',
                'center' => 'توسيط',
            ],
            'spacing' => [
                'compact' => 'مضغوط',
                'normal' => 'عادي',
            ],
            'font_size' => [
                'small' => 'صغير',
                'normal' => 'عادي',
                'large' => 'كبير',
            ],
            'table_density' => [
                'compact' => 'مضغوط',
                'normal' => 'عادي',
            ],
            'logo_size' => [
                'small' => 'صغير',
                'medium' => 'متوسط',
                'large' => 'كبير',
            ],
            'margin' => [
                'narrow' => 'هامش ضيق',
                'normal' => 'هامش عادي',
                'wide' => 'هامش واسع',
            ],
        ];
    }

    public static function booleanPaths(): array
    {
        return [
            'company.use_global_identity',
            'company.show_logo',
            'company.show_header_image',
            'company.show_footer_image',
            'company.show_phone',
            'company.show_email',
            'company.show_address',
            'company.show_website',
            'company.show_tax_number',
            'company.show_registration_number',
            'general.show_thank_you_message',
            'general.show_disclaimer',
            'quotation.show_customer_name',
            'quotation.show_customer_phone',
            'quotation.show_customer_email',
            'quotation.show_customer_address',
            'quotation.show_number',
            'quotation.show_date',
            'quotation.show_status',
            'quotation.show_creator',
            'quotation.show_product_images',
            'quotation.show_item_discounts',
            'quotation.show_subtotal',
            'quotation.show_invoice_discount',
            'quotation.show_installation',
            'quotation.show_total',
            'quotation.show_notes',
            'quotation.show_terms',
            'sales_invoice.show_customer_name',
            'sales_invoice.show_customer_phone',
            'sales_invoice.show_customer_email',
            'sales_invoice.show_customer_address',
            'sales_invoice.show_number',
            'sales_invoice.show_date',
            'sales_invoice.show_status',
            'sales_invoice.show_creator',
            'sales_invoice.show_quotation_reference',
            'sales_invoice.show_product_images',
            'sales_invoice.show_item_discounts',
            'sales_invoice.show_subtotal',
            'sales_invoice.show_invoice_discount',
            'sales_invoice.show_installation',
            'sales_invoice.show_gross_total',
            'sales_invoice.show_notes',
            'warranty.enabled',
        ];
    }

    public static function truthy(array $settings, string $path): bool
    {
        return filter_var(data_get($settings, $path), FILTER_VALIDATE_BOOLEAN);
    }

    public static function companyIdentityPaths(): array
    {
        return [
            'name',
            'logo_path',
            'header_image_path',
            'footer_image_path',
            'phone',
            'email',
            'address',
            'website',
            'tax_number',
            'registration_number',
            'currency_label',
        ];
    }

    private static function legacyCompanySettingMap(): array
    {
        return [
            'company.name' => 'company_name',
            'company.phone' => 'company_phone',
            'company.email' => 'company_email',
            'company.address' => 'company_address',
        ];
    }

    private static function castStoredValue(string $path, mixed $value): mixed
    {
        if (in_array($path, self::booleanPaths(), true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $value;
    }

    private static function serializeValue(string $path, mixed $value): string
    {
        if (in_array($path, self::booleanPaths(), true)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }

        return (string) ($value ?? '');
    }

    private static function flatten(array $values, string $prefix = ''): array
    {
        $flat = [];

        foreach ($values as $key => $value) {
            $path = $prefix === '' ? $key : $prefix.'.'.$key;

            if (is_array($value)) {
                $flat += self::flatten($value, $path);

                continue;
            }

            $flat[$path] = $value;
        }

        return $flat;
    }
}
