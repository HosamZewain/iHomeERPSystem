<?php

namespace Tests\Feature;

use App\Livewire\Products\ProductList;
use App\Livewire\Settings\PrintTemplates\PrintTemplateForm;
use App\Models\Category;
use App\Models\Customer;
use App\Models\PrintTemplate;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class PrintSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_print_template_that_affects_quotation_output(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Template Customer']);
        $product = Product::factory()->create([
            'name' => 'Template Sensor',
            'internal_sku' => 'TMP-SENSOR',
            'image_path' => '/storage/products/template-sensor.png',
        ]);

        $quotation = Quotation::factory()->create([
            'quotation_number' => 'QUO-TEMPLATE-001',
            'customer_id' => $customer->id,
            'subtotal' => 475,
            'invoice_discount_amount' => 0,
            'installation_enabled' => true,
            'installation_pricing_mode' => Quotation::INSTALLATION_FIXED,
            'installation_fixed_amount' => 125,
            'installation_total' => 125,
            'total' => 600,
            'created_by' => $user->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_sale_price' => 500,
            'item_discount_type' => Quotation::DISCOUNT_FIXED,
            'item_discount_value' => 25,
            'item_discount_amount' => 25,
            'line_total' => 475,
        ]);

        $this->actingAs($user)
            ->get(route('settings.print'))
            ->assertOk()
            ->assertSee('قوالب الطباعة');

        Livewire::actingAs($user)
            ->test(PrintTemplateForm::class)
            ->set('name', 'قالب عرض بدون خصومات')
            ->set('code', 'quotation-no-discounts')
            ->set('document_type', PrintTemplate::TYPE_QUOTATION)
            ->set('is_default', true)
            ->set('title', 'عرض فني')
            ->set('settings.company.use_global_identity', false)
            ->set('settings.company.name', 'iHome Test Print')
            ->set('settings.company.show_header_image', true)
            ->set('settings.company.header_image_path', '/images/quotation-header.png')
            ->set('settings.company.show_footer_image', true)
            ->set('settings.company.footer_image_path', '/images/quotation-footer.png')
            ->set('settings.quotation.footer_text', 'تذييل عرض مخصص')
            ->set('settings.quotation.installation_item_name', 'تركيب نظام المنزل الذكي')
            ->set('settings.quotation.show_item_discounts', false)
            ->set('settings.quotation.show_invoice_discount', false)
            ->set('settings.warranty.enabled', true)
            ->set('settings.warranty.title', 'شروط الضمان الخاصة')
            ->set('settings.warranty.body', 'محتوى شروط الضمان من القالب.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('settings.print'));

        $template = PrintTemplate::query()->where('code', 'quotation-no-discounts')->firstOrFail();

        $this->actingAs($user)
            ->get(route('quotations.print', ['quotation' => $quotation, 'template' => $template->id]))
            ->assertOk()
            ->assertSee('اختيار قالب الطباعة')
            ->assertSee('iHome Test Print')
            ->assertSee('عرض فني')
            ->assertSee('Template Customer')
            ->assertSee('Template Sensor')
            ->assertSee('تركيب نظام المنزل الذكي')
            ->assertSee('/storage/products/template-sensor.png')
            ->assertSee('تذييل عرض مخصص')
            ->assertSee('/images/quotation-header.png')
            ->assertSee('/images/quotation-footer.png')
            ->assertSee('شروط الضمان الخاصة')
            ->assertSee('محتوى شروط الضمان من القالب.')
            ->assertDontSee('القيمة: 25.00 ج.م')
            ->assertDontSee('خصم العرض');
    }

    public function test_selected_sales_invoice_template_controls_invoice_print_output(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Invoice Template Customer']);
        $product = Product::factory()->create([
            'name' => 'Template Lock',
            'internal_sku' => 'TMP-LOCK',
            'image_path' => '/storage/products/template-lock.png',
        ]);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TEMPLATE-001',
            'customer_id' => $customer->id,
            'subtotal' => 900,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 100,
            'invoice_discount_amount' => 100,
            'installation_enabled' => true,
            'installation_pricing_mode' => SalesInvoice::INSTALLATION_FIXED,
            'installation_fixed_amount' => 250,
            'installation_total' => 250,
            'gross_total' => 1050,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_sale_price' => 1000,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 100,
            'item_discount_amount' => 100,
            'line_total' => 900,
        ]);

        $hiddenDiscountTemplate = PrintTemplate::factory()->create([
            'name' => 'فاتورة بدون إظهار الخصومات',
            'document_type' => PrintTemplate::TYPE_SALES_INVOICE,
            'is_active' => true,
            'is_default' => false,
            'title' => 'فاتورة مختصرة',
            'settings' => [
                'company' => [
                    'use_global_identity' => false,
                    'name' => 'iHome Invoice Print',
                ],
                'sales_invoice' => [
                    'installation_item_name' => 'تركيب وبرمجة',
                    'show_item_discounts' => false,
                    'show_invoice_discount' => false,
                ],
                'warranty' => [
                    'enabled' => true,
                    'title' => 'شروط الضمان',
                    'body' => 'شروط ضمان فاتورة البيع.',
                    'footer_text' => 'تذييل الضمان',
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.print', ['salesInvoice' => $invoice, 'template' => $hiddenDiscountTemplate->id]))
            ->assertOk()
            ->assertSee('اختيار قالب الطباعة')
            ->assertSee('iHome Invoice Print')
            ->assertSee('فاتورة مختصرة')
            ->assertSee('Invoice Template Customer')
            ->assertSee('Template Lock')
            ->assertSee('تركيب وبرمجة')
            ->assertSee('/storage/products/template-lock.png')
            ->assertSee('إجمالي فاتورة العميل')
            ->assertSee('شروط ضمان فاتورة البيع.')
            ->assertDontSee('القيمة: 100.00 ج.م')
            ->assertDontSee('خصم الفاتورة');
    }

    public function test_print_template_form_stores_uploaded_branding_assets(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($user)
            ->test(PrintTemplateForm::class)
            ->set('name', 'قالب صور الطباعة')
            ->set('code', 'template-images')
            ->set('document_type', PrintTemplate::TYPE_QUOTATION)
            ->set('title', 'عرض بصور')
            ->set('settings.company.use_global_identity', true)
            ->set('logoUpload', UploadedFile::fake()->image('logo.png', 300, 300))
            ->set('headerImageUpload', UploadedFile::fake()->image('header.jpg', 1200, 250))
            ->set('footerImageUpload', UploadedFile::fake()->image('footer.jpg', 1200, 220))
            ->call('save')
            ->assertHasNoErrors();

        $template = PrintTemplate::query()->where('code', 'template-images')->firstOrFail();
        $settings = $template->settings;

        foreach ([
            $settings['company']['logo_path'],
            $settings['company']['header_image_path'],
            $settings['company']['footer_image_path'],
        ] as $path) {
            $this->assertStringContainsString('/storage/print-templates/', $path);

            $publicPath = Str::after(parse_url($path, PHP_URL_PATH) ?: $path, '/storage/');
            Storage::disk('public')->assertExists($publicPath);
        }
    }

    public function test_product_form_stores_uploaded_image_for_printed_items(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Livewire::actingAs($user)
            ->test(ProductList::class)
            ->set('name', 'منتج بصورة')
            ->set('internal_sku', 'IMG-PRODUCT-001')
            ->set('category_id', (string) $category->id)
            ->set('sale_price', '1500')
            ->set('current_average_cost', '900')
            ->set('minimum_stock_alert_level', '2')
            ->set('imageUpload', UploadedFile::fake()->image('product.jpg', 700, 700))
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->where('internal_sku', 'IMG-PRODUCT-001')->firstOrFail();

        $this->assertStringContainsString('/storage/products/', $product->image_path);
        $publicPath = Str::after(parse_url($product->image_path, PHP_URL_PATH) ?: $product->image_path, '/storage/');
        Storage::disk('public')->assertExists($publicPath);
    }
}
