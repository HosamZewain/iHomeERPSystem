<?php

namespace Tests\Feature;

use App\Enums\QuotationStatus;
use App\Enums\InvoicePaymentStatus;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Livewire\Quotations\QuotationForm;
use App\Livewire\Quotations\QuotationShow;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotation_form_calculates_item_and_invoice_discounts_without_stock_movement(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'sale_price' => 1000,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(QuotationForm::class)
            ->set('quotation_number', 'QUO-TEST-001')
            ->set('customer_id', (string) $customer->id)
            ->set('quotation_date', '2026-04-12')
            ->set('invoice_discount_type', Quotation::DISCOUNT_PERCENTAGE)
            ->set('invoice_discount_value', '10')
            ->set('installation_enabled', true)
            ->set('installation_pricing_mode', Quotation::INSTALLATION_PERCENTAGE)
            ->set('installation_percentage_value', '15')
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.quantity', '2')
            ->set('items.0.unit_sale_price', '1000')
            ->set('items.0.item_discount_type', Quotation::DISCOUNT_FIXED)
            ->set('items.0.item_discount_value', '100')
            ->call('save')
            ->assertHasNoErrors();

        $quotation = Quotation::query()->with('items')->firstOrFail();
        $item = $quotation->items->first();

        $this->assertEquals(1900.0, (float) $quotation->subtotal);
        $this->assertEquals(190.0, (float) $quotation->invoice_discount_amount);
        $this->assertEquals(285.0, (float) $quotation->installation_total);
        $this->assertEquals(1995.0, (float) $quotation->total);
        $this->assertEquals(100.0, (float) $item->item_discount_amount);
        $this->assertEquals(1900.0, (float) $item->line_total);
        $this->assertSame(0, StockMovement::query()->count());
    }

    public function test_quotation_pages_render_for_authorized_users(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Smart Sensor', 'is_active' => true]);
        $quotation = Quotation::factory()->create([
            'quotation_number' => 'QUO-TEST-002',
            'subtotal' => 900,
            'invoice_discount_type' => Quotation::DISCOUNT_FIXED,
            'invoice_discount_value' => 50,
            'invoice_discount_amount' => 50,
            'total' => 850,
            'created_by' => $user->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_sale_price' => 900,
            'item_discount_type' => Quotation::DISCOUNT_FIXED,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 900,
        ]);

        $this->actingAs($user)
            ->get(route('quotations.index'))
            ->assertOk()
            ->assertSee('QUO-TEST-002')
            ->assertSee('عروض الأسعار');

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Smart Sensor')
            ->assertSee('طباعة / حفظ PDF')
            ->assertSee('عرض السعر لا يؤثر على المخزون');

        $this->actingAs($user)
            ->get(route('quotations.edit', $quotation))
            ->assertOk()
            ->assertSee('تعديل عرض سعر')
            ->assertSee('Smart Sensor');

        $this->actingAs($user)
            ->get(route('quotations.print', $quotation))
            ->assertOk()
            ->assertSee('عرض سعر')
            ->assertSee('QUO-TEST-002')
            ->assertSee('Smart Sensor')
            ->assertSee('طباعة / حفظ PDF')
            ->assertSee('هذا العرض لا يؤثر على المخزون');
    }

    public function test_quotation_form_filters_customer_and_product_dropdowns(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Customer::factory()->create(['name' => 'Alpha Customer', 'phone' => '01011111111']);
        Customer::factory()->create(['name' => 'Beta Customer', 'phone' => '01022222222']);
        Product::factory()->create([
            'name' => 'Alpha Sensor',
            'internal_sku' => 'ALPHA-SENSOR',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Beta Switch',
            'internal_sku' => 'BETA-SWITCH',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(QuotationForm::class)
            ->set('customerSearch', 'Alpha')
            ->set('productSearch.0', 'ALPHA')
            ->assertSee('Alpha Customer')
            ->assertDontSee('Beta Customer')
            ->assertSee('Alpha Sensor')
            ->assertDontSee('Beta Switch');
    }

    public function test_quotation_form_can_create_customer_inline_and_select_it(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'sale_price' => 750,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(QuotationForm::class)
            ->set('quotation_number', 'QUO-TEST-INLINE-CUSTOMER')
            ->set('quotation_date', '2026-04-26')
            ->call('showCreateCustomer')
            ->set('new_customer_name', 'عميل من شاشة العرض')
            ->set('new_customer_phone', '01099999999')
            ->set('new_customer_email', 'quote-customer@example.com')
            ->call('createCustomer')
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.quantity', '1')
            ->set('items.0.unit_sale_price', '750')
            ->call('save')
            ->assertHasNoErrors();

        $customer = Customer::query()->where('phone', '01099999999')->firstOrFail();
        $quotation = Quotation::query()->firstOrFail();

        $this->assertSame('عميل من شاشة العرض', $customer->name);
        $this->assertSame($customer->id, $quotation->customer_id);
    }

    public function test_quotation_conversion_creates_draft_sales_invoice_without_stock_movement(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Smart Thermostat',
            'current_average_cost' => 400,
            'sale_price' => 900,
        ]);

        $quotation = Quotation::factory()->create([
            'quotation_number' => 'QUO-TEST-003',
            'customer_id' => $customer->id,
            'subtotal' => 1700,
            'invoice_discount_type' => Quotation::DISCOUNT_PERCENTAGE,
            'invoice_discount_value' => 10,
            'invoice_discount_amount' => 170,
            'installation_enabled' => true,
            'installation_pricing_mode' => Quotation::INSTALLATION_FIXED,
            'installation_fixed_amount' => 300,
            'installation_total' => 300,
            'installation_notes' => 'Installation included.',
            'total' => 1830,
            'notes' => 'Customer approved package.',
            'created_by' => $user->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 900,
            'item_discount_type' => Quotation::DISCOUNT_FIXED,
            'item_discount_value' => 100,
            'item_discount_amount' => 100,
            'line_total' => 1700,
        ]);

        Livewire::actingAs($user)
            ->test(QuotationShow::class, ['quotation' => $quotation])
            ->call('convertToInvoice')
            ->assertHasNoErrors();

        $quotation->refresh();
        $invoice = SalesInvoice::query()->with('items')->firstOrFail();
        $invoiceItem = $invoice->items->first();

        $this->assertSame(QuotationStatus::Converted, $quotation->status);
        $this->assertSame(SalesInvoiceStatus::Draft, $invoice->status);
        $this->assertSame(SalesChannel::Direct, $invoice->sales_channel);
        $this->assertSame($quotation->id, $invoice->quotation_id);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertEquals(1700.0, (float) $invoice->subtotal);
        $this->assertSame(Quotation::DISCOUNT_PERCENTAGE, $invoice->invoice_discount_type);
        $this->assertEquals(10.0, (float) $invoice->invoice_discount_value);
        $this->assertEquals(170.0, (float) $invoice->invoice_discount_amount);
        $this->assertTrue($invoice->installation_enabled);
        $this->assertEquals(300.0, (float) $invoice->installation_total);
        $this->assertEquals(300.0, (float) $invoice->installation_profit);
        $this->assertSame('Installation included.', $invoice->installation_notes);
        $this->assertEquals(1830.0, (float) $invoice->gross_total);
        $this->assertSame(InvoicePaymentStatus::Unpaid, $invoice->payment_status);
        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(1830.0, (float) $invoice->remaining_amount);
        $this->assertEquals(1830.0, (float) $invoice->net_revenue_after_partner_commission);
        $this->assertSame($product->id, $invoiceItem->product_id);
        $this->assertEquals(2.0, (float) $invoiceItem->quantity);
        $this->assertEquals(900.0, (float) $invoiceItem->unit_sale_price);
        $this->assertSame(Quotation::DISCOUNT_FIXED, $invoiceItem->item_discount_type);
        $this->assertEquals(100.0, (float) $invoiceItem->item_discount_value);
        $this->assertEquals(100.0, (float) $invoiceItem->item_discount_amount);
        $this->assertEquals(1700.0, (float) $invoiceItem->line_total);
        $this->assertEquals(0.0, (float) $invoiceItem->cost_at_sale_time);
        $this->assertEquals(0.0, (float) $invoice->total_cost);
        $this->assertSame(0, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());
    }

    public function test_quotation_conversion_cannot_run_twice(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $quotation = Quotation::factory()->create([
            'quotation_number' => 'QUO-TEST-004',
            'subtotal' => 500,
            'total' => 500,
            'created_by' => $user->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_sale_price' => 500,
            'line_total' => 500,
        ]);

        $quotation->convertToSalesInvoice($user);

        try {
            $quotation->convertToSalesInvoice($user);
            $this->fail('Expected duplicate conversion validation exception.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('لا يمكن تحويل عرض السعر', collect($exception->errors())->flatten()->first());
        }

        $this->assertSame(1, SalesInvoice::query()->count());
    }

    public function test_quotation_item_order_is_preserved_in_save_print_and_conversion(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $productA = Product::factory()->create(['name' => 'Product A', 'internal_sku' => 'A-1', 'sale_price' => 100, 'is_active' => true]);
        $productB = Product::factory()->create(['name' => 'Product B', 'internal_sku' => 'B-1', 'sale_price' => 200, 'is_active' => true]);
        $productC = Product::factory()->create(['name' => 'Product C', 'internal_sku' => 'C-1', 'sale_price' => 300, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(QuotationForm::class)
            ->set('quotation_number', 'QUO-ORDER-001')
            ->set('customer_id', (string) $customer->id)
            ->set('quotation_date', '2026-04-26')
            ->set('items.0.product_id', (string) $productA->id)
            ->set('items.0.quantity', '1')
            ->set('items.0.unit_sale_price', '100')
            ->call('addItem')
            ->set('items.1.product_id', (string) $productC->id)
            ->set('items.1.quantity', '1')
            ->set('items.1.unit_sale_price', '300')
            ->call('addItem')
            ->set('items.2.product_id', (string) $productB->id)
            ->set('items.2.quantity', '1')
            ->set('items.2.unit_sale_price', '200')
            ->call('save')
            ->assertHasNoErrors();

        $quotation = Quotation::query()->with('items.product')->firstOrFail();

        $this->assertSame([$productA->id, $productC->id, $productB->id], $quotation->items->pluck('product_id')->all());
        $this->assertSame([1, 2, 3], $quotation->items->pluck('sort_order')->all());

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSeeInOrder(['Product A', 'Product C', 'Product B']);

        $this->actingAs($user)
            ->get(route('quotations.print', $quotation))
            ->assertOk()
            ->assertSeeInOrder(['Product A', 'Product C', 'Product B']);

        $invoice = $quotation->convertToSalesInvoice($user)->load('items.product');

        $this->assertSame([$productA->id, $productC->id, $productB->id], $invoice->items->pluck('product_id')->all());
        $this->assertSame([1, 2, 3], $invoice->items->pluck('sort_order')->all());
    }

    public function test_quotation_supports_sections_and_product_descriptions_without_affecting_totals(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $productA = Product::factory()->create(['name' => 'Bed Sensor', 'internal_sku' => 'BED-1', 'sale_price' => 1000, 'is_active' => true]);
        $productB = Product::factory()->create(['name' => 'Living Speaker', 'internal_sku' => 'LIV-1', 'sale_price' => 500, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(QuotationForm::class)
            ->set('quotation_number', 'QUO-SECTIONS-001')
            ->set('customer_id', (string) $customer->id)
            ->set('quotation_date', '2026-05-02')
            ->call('addSection')
            ->set('items.1.section_title', 'غرفة النوم')
            ->call('moveItemUp', 1)
            ->set('items.1.product_id', (string) $productA->id)
            ->set('items.1.quantity', '2')
            ->set('items.1.unit_sale_price', '1000')
            ->set('items.1.description', 'يشمل حساسين للسرير مع ضبط أولي.')
            ->call('addSection')
            ->set('items.2.section_title', 'الريسيبشن')
            ->call('addItem')
            ->set('items.3.product_id', (string) $productB->id)
            ->set('items.3.quantity', '1')
            ->set('items.3.unit_sale_price', '500')
            ->call('save')
            ->assertHasNoErrors();

        $quotation = Quotation::query()->with('items.product')->firstOrFail();

        $this->assertSame(
            [
                QuotationItem::TYPE_SECTION,
                QuotationItem::TYPE_PRODUCT,
                QuotationItem::TYPE_SECTION,
                QuotationItem::TYPE_PRODUCT,
            ],
            $quotation->items->pluck('row_type')->all()
        );
        $this->assertSame(['غرفة النوم', null, 'الريسيبشن', null], $quotation->items->pluck('section_title')->all());
        $this->assertSame([1, 2, 3, 4], $quotation->items->pluck('sort_order')->all());
        $this->assertSame('يشمل حساسين للسرير مع ضبط أولي.', $quotation->items[1]->description);
        $this->assertEquals(2500.0, (float) $quotation->subtotal);
        $this->assertEquals(2500.0, (float) $quotation->total);

        $this->actingAs($user)
            ->get(route('quotations.show', $quotation))
            ->assertOk()
            ->assertSeeInOrder(['غرفة النوم', 'Bed Sensor', 'الريسيبشن', 'Living Speaker']);

        $this->actingAs($user)
            ->get(route('quotations.print', $quotation))
            ->assertOk()
            ->assertSeeInOrder(['غرفة النوم', 'Bed Sensor', 'الريسيبشن', 'Living Speaker'])
            ->assertSee('يشمل حساسين للسرير مع ضبط أولي.');
    }

    public function test_quotation_conversion_ignores_section_rows_and_preserves_product_order(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $productA = Product::factory()->create(['name' => 'Product A', 'sale_price' => 100, 'is_active' => true]);
        $productB = Product::factory()->create(['name' => 'Product B', 'sale_price' => 200, 'is_active' => true]);

        $quotation = Quotation::factory()->create([
            'customer_id' => $customer->id,
            'subtotal' => 300,
            'total' => 300,
            'created_by' => $user->id,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'row_type' => QuotationItem::TYPE_SECTION,
            'product_id' => null,
            'section_title' => 'غرفة النوم',
            'description' => null,
            'sort_order' => 1,
            'quantity' => 0,
            'unit_sale_price' => 0,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 0,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $productA->id,
            'sort_order' => 2,
            'quantity' => 1,
            'unit_sale_price' => 100,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 100,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'row_type' => QuotationItem::TYPE_SECTION,
            'product_id' => null,
            'section_title' => 'الريسيبشن',
            'description' => null,
            'sort_order' => 3,
            'quantity' => 0,
            'unit_sale_price' => 0,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 0,
        ]);

        QuotationItem::factory()->create([
            'quotation_id' => $quotation->id,
            'product_id' => $productB->id,
            'sort_order' => 4,
            'quantity' => 1,
            'unit_sale_price' => 200,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 200,
        ]);

        $invoice = $quotation->convertToSalesInvoice($user)->load('items');

        $this->assertCount(2, $invoice->items);
        $this->assertSame([$productA->id, $productB->id], $invoice->items->pluck('product_id')->all());
        $this->assertSame([1, 2], $invoice->items->pluck('sort_order')->all());
    }

    public function test_quotation_can_save_with_more_than_ten_product_rows(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $products = Product::factory()->count(12)->create(['is_active' => true, 'sale_price' => 100]);

        $component = Livewire::actingAs($user)
            ->test(QuotationForm::class)
            ->set('quotation_number', 'QUO-BULK-012')
            ->set('customer_id', (string) $customer->id)
            ->set('quotation_date', '2026-05-12');

        foreach ($products as $index => $product) {
            if ($index > 0) {
                $component->call('addItem');
            }

            $component
                ->set("items.{$index}.product_id", (string) $product->id)
                ->set("items.{$index}.quantity", '1')
                ->set("items.{$index}.unit_sale_price", '100');
        }

        $component->call('save')->assertHasNoErrors();

        $quotation = Quotation::query()->with('items')->where('quotation_number', 'QUO-BULK-012')->firstOrFail();

        $this->assertCount(12, $quotation->items);
        $this->assertSame(range(1, 12), $quotation->items->pluck('sort_order')->all());
        $this->assertEquals(1200.0, (float) $quotation->subtotal);
        $this->assertEquals(1200.0, (float) $quotation->total);
    }
}
