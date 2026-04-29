<?php

namespace Tests\Feature;

use App\Enums\InvoicePaymentStatus;
use App\Enums\PaymentMethod;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Livewire\SalesInvoices\SalesInvoiceCreate;
use App\Livewire\SalesInvoices\SalesInvoiceShow;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesInvoicePayment;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SalesInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_draft_does_not_affect_stock(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Smart Relay',
            'sale_price' => 200,
            'current_average_cost' => 120,
            'is_active' => true,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'balance_after' => 5,
            'unit_cost' => 120,
            'total_cost' => 600,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(SalesInvoiceCreate::class)
            ->set('invoice_number', 'INV-TEST-001')
            ->set('customer_id', (string) $customer->id)
            ->set('invoice_date', '2026-04-12')
            ->set('invoice_discount_type', SalesInvoice::DISCOUNT_FIXED)
            ->set('invoice_discount_value', '40')
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.quantity', '2')
            ->set('items.0.unit_sale_price', '200')
            ->set('items.0.item_discount_type', SalesInvoice::DISCOUNT_FIXED)
            ->set('items.0.item_discount_value', '10')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->with('items')->firstOrFail();

        $this->assertSame(SalesInvoiceStatus::Draft, $invoice->status);
        $this->assertSame(InvoicePaymentStatus::Unpaid, $invoice->payment_status);
        $this->assertEquals(390.0, (float) $invoice->subtotal);
        $this->assertEquals(350.0, (float) $invoice->gross_total);
        $this->assertEquals(0.0, (float) $invoice->paid_amount);
        $this->assertEquals(350.0, (float) $invoice->remaining_amount);
        $this->assertEquals(0.0, (float) $invoice->total_cost);
        $this->assertSame(0, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());
        $this->assertEquals(5.0, Product::find($product->id)->current_stock_quantity);
    }

    public function test_confirming_direct_sales_invoice_decreases_stock_and_calculates_profit(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Smart Dimmer',
            'current_average_cost' => 120,
            'sale_price' => 200,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'balance_after' => 5,
            'unit_cost' => 120,
            'total_cost' => 600,
        ]);

        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TEST-002',
            'sales_channel' => SalesChannel::Direct->value,
            'subtotal' => 380,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 20,
            'invoice_discount_amount' => 20,
            'gross_total' => 360,
            'net_revenue_after_partner_commission' => 360,
            'created_by' => $user->id,
        ]);

        $item = SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 200,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 20,
            'item_discount_amount' => 20,
            'line_total' => 380,
        ]);

        $invoice->confirm($user);
        $invoice->refresh();
        $item->refresh();

        $movement = StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->firstOrFail();

        $this->assertSame(SalesInvoiceStatus::Confirmed, $invoice->status);
        $this->assertEquals(120.0, (float) $item->cost_at_sale_time);
        $this->assertEquals(140.0, (float) $item->line_profit);
        $this->assertEquals(240.0, (float) $invoice->total_cost);
        $this->assertEquals(120.0, (float) $invoice->total_profit);
        $this->assertEquals(-2.0, (float) $movement->quantity);
        $this->assertEquals(3.0, (float) $movement->balance_after);
        $this->assertEquals(3.0, Product::find($product->id)->current_stock_quantity);
    }

    public function test_confirmed_sales_invoice_can_be_fully_returned_and_restore_stock(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Returnable Dimmer',
            'current_average_cost' => 120,
            'sale_price' => 200,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'balance_after' => 5,
            'unit_cost' => 120,
            'total_cost' => 600,
        ]);

        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-RETURN-001',
            'sales_channel' => SalesChannel::Direct->value,
            'subtotal' => 380,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 20,
            'invoice_discount_amount' => 20,
            'gross_total' => 360,
            'remaining_amount' => 360,
            'net_revenue_after_partner_commission' => 360,
            'created_by' => $user->id,
        ]);

        $item = SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 200,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 20,
            'item_discount_amount' => 20,
            'line_total' => 380,
        ]);

        $invoice->confirm($user);
        $invoice->reverseConfirmed('إلغاء العملية بعد التأكيد', $user);
        $invoice->refresh();

        $saleMovement = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_SALES_ITEM)
            ->where('source_id', $item->id)
            ->firstOrFail();

        $returnMovement = StockMovement::query()
            ->where('source_type', StockMovement::SOURCE_RETURN)
            ->where('source_id', $item->id)
            ->firstOrFail();

        $this->assertSame(SalesInvoiceStatus::Returned, $invoice->status);
        $this->assertSame('إلغاء العملية بعد التأكيد', $invoice->return_reason);
        $this->assertSame($user->id, $invoice->returned_by);
        $this->assertNotNull($invoice->returned_at);
        $this->assertEquals(0.0, (float) $invoice->remaining_amount);
        $this->assertSame(StockMovement::TYPE_SALE_OUT, $saleMovement->movement_type);
        $this->assertEquals(-2.0, (float) $saleMovement->quantity);
        $this->assertSame(StockMovement::TYPE_RETURN_IN, $returnMovement->movement_type);
        $this->assertEquals(2.0, (float) $returnMovement->quantity);
        $this->assertEquals(5.0, (float) $returnMovement->balance_after);
        $this->assertEquals(5.0, Product::find($product->id)->current_stock_quantity);
    }

    public function test_confirmed_sales_invoice_return_is_blocked_when_payments_exist(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Paid Return Block',
            'current_average_cost' => 150,
            'sale_price' => 300,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'balance_after' => 4,
            'unit_cost' => 150,
            'total_cost' => 600,
        ]);

        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-RETURN-PAID',
            'sales_channel' => SalesChannel::Direct->value,
            'subtotal' => 600,
            'gross_total' => 600,
            'remaining_amount' => 600,
            'net_revenue_after_partner_commission' => 600,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 300,
            'line_total' => 600,
        ]);

        $invoice->confirm($user);
        $invoice->recordPayment([
            'payment_date' => now()->toDateString(),
            'amount' => 100,
            'payment_method' => PaymentMethod::Cash->value,
            'reference_number' => null,
            'notes' => null,
        ], $user);

        try {
            $invoice->reverseConfirmed('محاولة بعد تحصيل', $user);
            $this->fail('Expected validation exception when returning a paid invoice.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('يوجد عليها تحصيل', collect($exception->errors())->flatten()->first());
        }

        $this->assertSame(SalesInvoiceStatus::Confirmed, $invoice->refresh()->status);
        $this->assertSame(0, StockMovement::query()->where('movement_type', StockMovement::TYPE_RETURN_IN)->count());
    }

    public function test_partner_commission_is_separate_from_customer_invoice_total(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $partner = Partner::factory()->create([
            'default_commission_type' => SalesInvoice::DISCOUNT_PERCENTAGE,
            'default_commission_value' => 10,
        ]);
        $product = Product::factory()->create([
            'current_average_cost' => 150,
            'sale_price' => 300,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'balance_after' => 4,
            'unit_cost' => 150,
            'total_cost' => 600,
        ]);

        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TEST-003',
            'sales_channel' => SalesChannel::Partner->value,
            'partner_id' => $partner->id,
            'subtotal' => 900,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 0,
            'invoice_discount_amount' => 0,
            'gross_total' => 900,
            'partner_commission_type' => SalesInvoice::DISCOUNT_PERCENTAGE,
            'partner_commission_value' => 10,
            'partner_commission_amount' => 90,
            'net_revenue_after_partner_commission' => 810,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_sale_price' => 300,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 900,
        ]);

        $invoice->confirm($user);
        $invoice->refresh();

        $this->assertEquals(900.0, (float) $invoice->gross_total);
        $this->assertEquals(90.0, (float) $invoice->partner_commission_amount);
        $this->assertEquals(810.0, (float) $invoice->net_revenue_after_partner_commission);
        $this->assertEquals(450.0, (float) $invoice->total_cost);
        $this->assertEquals(360.0, (float) $invoice->total_profit);
        $this->assertEquals(1.0, Product::find($product->id)->current_stock_quantity);
    }

    public function test_installation_is_calculated_separately_and_does_not_create_stock_movement(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Smart Hub',
            'current_average_cost' => 600,
            'sale_price' => 1000,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'balance_after' => 5,
            'unit_cost' => 600,
            'total_cost' => 3000,
        ]);

        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TEST-INSTALLATION',
            'sales_channel' => SalesChannel::Direct->value,
            'subtotal' => 2000,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 200,
            'invoice_discount_amount' => 200,
            'installation_enabled' => true,
            'installation_pricing_mode' => SalesInvoice::INSTALLATION_PERCENTAGE,
            'installation_percentage_value' => 15,
            'installation_fixed_amount' => 0,
            'installation_total' => 300,
            'installation_party_type' => SalesInvoice::INSTALLATION_PARTY_TECHNICIAN,
            'installation_party_reference' => 'Tech Ahmed',
            'installation_payout_amount' => 120,
            'installation_profit' => 180,
            'gross_total' => 2100,
            'net_revenue_after_partner_commission' => 2100,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 1000,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 2000,
        ]);

        $invoice->confirm($user);
        $invoice->refresh();

        $this->assertEquals(2000.0, (float) $invoice->subtotal);
        $this->assertEquals(200.0, (float) $invoice->invoice_discount_amount);
        $this->assertEquals(300.0, (float) $invoice->installation_total);
        $this->assertEquals(2100.0, (float) $invoice->gross_total);
        $this->assertEquals(1200.0, (float) $invoice->total_cost);
        $this->assertEquals(600.0, (float) $invoice->product_profit);
        $this->assertEquals(180.0, (float) $invoice->installation_profit);
        $this->assertEquals(780.0, (float) $invoice->total_profit);
        $this->assertSame(1, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());
        $this->assertEquals(3.0, Product::find($product->id)->current_stock_quantity);
    }

    public function test_confirming_sales_invoice_requires_sufficient_stock(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'name' => 'Door Sensor',
            'current_average_cost' => 100,
            'sale_price' => 180,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'balance_after' => 1,
            'unit_cost' => 100,
            'total_cost' => 100,
        ]);

        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TEST-004',
            'sales_channel' => SalesChannel::Direct->value,
            'subtotal' => 360,
            'gross_total' => 360,
            'net_revenue_after_partner_commission' => 360,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 180,
            'line_total' => 360,
        ]);

        try {
            $invoice->confirm($user);
            $this->fail('Expected insufficient stock validation exception.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('المخزون غير كافٍ', collect($exception->errors())->flatten()->first());
        }

        $this->assertSame(SalesInvoiceStatus::Draft, $invoice->refresh()->status);
        $this->assertSame(0, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());
        $this->assertEquals(1.0, Product::find($product->id)->current_stock_quantity);
    }

    public function test_sales_invoice_pages_render_for_authorized_users(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Walk In Client']);
        $product = Product::factory()->create(['name' => 'Smart Switch', 'is_active' => true]);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TEST-005',
            'customer_id' => $customer->id,
            'gross_total' => 500,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_sale_price' => 500,
            'line_total' => 500,
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.index'))
            ->assertOk()
            ->assertSee('فواتير البيع')
            ->assertSee('INV-TEST-005');

        $this->actingAs($user)
            ->get(route('sales-invoices.create'))
            ->assertOk()
            ->assertSee('إنشاء فاتورة بيع')
            ->assertSee('قناة البيع');

        $this->actingAs($user)
            ->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('Smart Switch')
            ->assertSee('طباعة / حفظ PDF')
            ->assertSee('تأكيد الفاتورة');
    }

    public function test_sales_invoice_item_order_is_preserved_after_save_edit_and_print(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $productA = Product::factory()->create(['name' => 'Invoice Product A', 'internal_sku' => 'INV-A', 'sale_price' => 100, 'is_active' => true]);
        $productB = Product::factory()->create(['name' => 'Invoice Product B', 'internal_sku' => 'INV-B', 'sale_price' => 200, 'is_active' => true]);
        $productC = Product::factory()->create(['name' => 'Invoice Product C', 'internal_sku' => 'INV-C', 'sale_price' => 300, 'is_active' => true]);

        Livewire::actingAs($user)
            ->test(SalesInvoiceCreate::class)
            ->set('invoice_number', 'INV-ORDER-001')
            ->set('customer_id', (string) $customer->id)
            ->set('invoice_date', '2026-04-26')
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
            ->call('saveDraft')
            ->assertHasNoErrors();

        $invoice = SalesInvoice::query()->with('items.product')->firstOrFail();

        $this->assertSame([$productA->id, $productC->id, $productB->id], $invoice->items->pluck('product_id')->all());
        $this->assertSame([1, 2, 3], $invoice->items->pluck('sort_order')->all());

        Livewire::actingAs($user)
            ->test(SalesInvoiceCreate::class, ['salesInvoice' => $invoice])
            ->set('notes', 'Edited without reordering')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $invoice->refresh()->load('items.product');

        $this->assertSame([$productA->id, $productC->id, $productB->id], $invoice->items->pluck('product_id')->all());
        $this->assertSame([1, 2, 3], $invoice->items->pluck('sort_order')->all());

        $this->actingAs($user)
            ->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertSeeInOrder(['Invoice Product A', 'Invoice Product C', 'Invoice Product B']);

        $this->actingAs($user)
            ->get(route('sales-invoices.print', $invoice))
            ->assertOk()
            ->assertSeeInOrder(['Invoice Product A', 'Invoice Product C', 'Invoice Product B']);
    }

    public function test_sales_invoice_form_filters_customer_and_product_dropdowns(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Customer::factory()->create(['name' => 'Alpha Invoice Customer', 'phone' => '01011111111']);
        Customer::factory()->create(['name' => 'Beta Invoice Customer', 'phone' => '01022222222']);
        Product::factory()->create([
            'name' => 'Alpha Invoice Product',
            'internal_sku' => 'ALPHA-INVOICE',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'name' => 'Beta Invoice Product',
            'internal_sku' => 'BETA-INVOICE',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(SalesInvoiceCreate::class)
            ->set('customerSearch', 'Alpha')
            ->set('productSearch.0', 'ALPHA')
            ->assertSee('Alpha Invoice Customer')
            ->assertDontSee('Beta Invoice Customer')
            ->assertSee('Alpha Invoice Product')
            ->assertDontSee('Beta Invoice Product');
    }

    public function test_sales_invoice_form_can_create_customer_inline_and_select_it(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create([
            'sale_price' => 450,
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(SalesInvoiceCreate::class)
            ->set('invoice_number', 'INV-TEST-INLINE-CUSTOMER')
            ->set('invoice_date', '2026-04-26')
            ->call('showCreateCustomer')
            ->set('new_customer_name', 'عميل من شاشة الفاتورة')
            ->set('new_customer_phone', '01088888888')
            ->set('new_customer_email', 'invoice-customer@example.com')
            ->call('createCustomer')
            ->set('items.0.product_id', (string) $product->id)
            ->set('items.0.quantity', '1')
            ->set('items.0.unit_sale_price', '450')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $customer = Customer::query()->where('phone', '01088888888')->firstOrFail();
        $invoice = SalesInvoice::query()->firstOrFail();

        $this->assertSame('عميل من شاشة الفاتورة', $customer->name);
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame(SalesInvoiceStatus::Draft, $invoice->status);
    }

    public function test_draft_sales_invoice_can_be_edited_before_confirmation(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();
        $oldProduct = Product::factory()->create(['name' => 'Old Product', 'sale_price' => 100, 'is_active' => true]);
        $newProduct = Product::factory()->create(['name' => 'Edited Product', 'sale_price' => 500, 'is_active' => true]);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-DRAFT-EDIT',
            'customer_id' => $customer->id,
            'subtotal' => 100,
            'gross_total' => 100,
            'net_revenue_after_partner_commission' => 100,
            'created_by' => $user->id,
        ]);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $oldProduct->id,
            'quantity' => 1,
            'unit_sale_price' => 100,
            'line_total' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('تعديل مسودة فاتورة بيع')
            ->assertSee('Old Product');

        Livewire::actingAs($user)
            ->test(SalesInvoiceCreate::class, ['salesInvoice' => $invoice])
            ->set('invoice_date', '2026-04-12')
            ->set('invoice_discount_type', SalesInvoice::DISCOUNT_FIXED)
            ->set('invoice_discount_value', '50')
            ->set('installation_enabled', true)
            ->set('installation_pricing_mode', SalesInvoice::INSTALLATION_FIXED)
            ->set('installation_fixed_amount', '200')
            ->set('installation_party_type', SalesInvoice::INSTALLATION_PARTY_TECHNICIAN)
            ->set('installation_party_reference', 'Tech Team')
            ->set('installation_payout_amount', '80')
            ->set('items.0.product_id', (string) $newProduct->id)
            ->set('items.0.quantity', '2')
            ->set('items.0.unit_sale_price', '500')
            ->set('items.0.item_discount_type', SalesInvoice::DISCOUNT_FIXED)
            ->set('items.0.item_discount_value', '0')
            ->call('saveDraft')
            ->assertHasNoErrors();

        $invoice->refresh()->load('items');

        $this->assertSame(SalesInvoiceStatus::Draft, $invoice->status);
        $this->assertSame(1, $invoice->items->count());
        $this->assertSame($newProduct->id, $invoice->items->first()->product_id);
        $this->assertEquals(1000.0, (float) $invoice->subtotal);
        $this->assertEquals(50.0, (float) $invoice->invoice_discount_amount);
        $this->assertEquals(200.0, (float) $invoice->installation_total);
        $this->assertEquals(1150.0, (float) $invoice->gross_total);
        $this->assertEquals(120.0, (float) $invoice->installation_profit);
        $this->assertSame(0, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());
    }

    public function test_confirmed_sales_invoice_edit_route_is_blocked(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $invoice = SalesInvoice::factory()->create([
            'status' => SalesInvoiceStatus::Confirmed->value,
            'gross_total' => 500,
            'net_revenue_after_partner_commission' => 500,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.edit', $invoice))
            ->assertForbidden();
    }

    public function test_confirmed_sales_invoice_accepts_partial_then_full_payment_without_affecting_stock(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Paid Customer']);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-PAY-001',
            'customer_id' => $customer->id,
            'gross_total' => 1000,
            'net_revenue_after_partner_commission' => 1000,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(SalesInvoiceShow::class, ['salesInvoice' => $invoice])
            ->set('payment_date', '2026-04-26')
            ->set('payment_amount', '400')
            ->set('payment_method', PaymentMethod::BankTransfer->value)
            ->set('reference_number', 'BANK-001')
            ->call('savePayment')
            ->assertHasNoErrors();

        $invoice->refresh();
        $payment = SalesInvoicePayment::query()->firstOrFail();

        $this->assertSame(InvoicePaymentStatus::PartiallyPaid, $invoice->payment_status);
        $this->assertEquals(400.0, (float) $invoice->paid_amount);
        $this->assertEquals(600.0, (float) $invoice->remaining_amount);
        $this->assertEquals(600.0, (float) $payment->remaining_amount_after);
        $this->assertSame(PaymentMethod::BankTransfer, $payment->payment_method);
        $this->assertSame(0, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());

        Livewire::actingAs($user)
            ->test(SalesInvoiceShow::class, ['salesInvoice' => $invoice])
            ->set('payment_date', '2026-04-27')
            ->set('payment_amount', '600')
            ->set('payment_method', PaymentMethod::Cash->value)
            ->call('savePayment')
            ->assertHasNoErrors();

        $invoice->refresh();

        $this->assertSame(InvoicePaymentStatus::Paid, $invoice->payment_status);
        $this->assertEquals(1000.0, (float) $invoice->paid_amount);
        $this->assertEquals(0.0, (float) $invoice->remaining_amount);
        $this->assertSame(2, SalesInvoicePayment::query()->count());
        $this->assertSame(0, StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->count());
    }

    public function test_sales_invoice_payment_blocks_overpayment_and_draft_collection(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $draftInvoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-PAY-DRAFT',
            'gross_total' => 700,
            'net_revenue_after_partner_commission' => 700,
            'status' => SalesInvoiceStatus::Draft->value,
            'created_by' => $user->id,
        ]);

        try {
            $draftInvoice->recordPayment([
                'payment_date' => '2026-04-26',
                'amount' => 100,
                'payment_method' => PaymentMethod::Cash->value,
                'reference_number' => null,
                'notes' => null,
            ], $user);
            $this->fail('Expected validation exception for draft invoice payment.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('المؤكدة فقط', collect($exception->errors())->flatten()->first());
        }

        $confirmedInvoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-PAY-OVER',
            'gross_total' => 500,
            'net_revenue_after_partner_commission' => 500,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        try {
            $confirmedInvoice->recordPayment([
                'payment_date' => '2026-04-26',
                'amount' => 600,
                'payment_method' => PaymentMethod::Cash->value,
                'reference_number' => null,
                'notes' => null,
            ], $user);
            $this->fail('Expected validation exception for overpayment.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('أكبر من الرصيد المتبقي', collect($exception->errors())->flatten()->first());
        }

        $this->assertSame(0, SalesInvoicePayment::query()->count());
        $this->assertEquals(500.0, (float) $confirmedInvoice->refresh()->remaining_amount);
    }

    public function test_sales_invoice_print_page_is_customer_facing_and_hides_partner_commission(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Customer For Print', 'phone' => '+20 100 111 2222']);
        $partner = Partner::factory()->create(['name' => 'Internal Partner Office']);
        $product = Product::factory()->create(['name' => 'Smart Lock']);
        $quotation = Quotation::factory()->create(['quotation_number' => 'QUO-PRINT-001']);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-PRINT-001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_channel' => SalesChannel::Partner->value,
            'partner_id' => $partner->id,
            'subtotal' => 1200,
            'invoice_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'invoice_discount_value' => 100,
            'invoice_discount_amount' => 100,
            'gross_total' => 1100,
            'partner_commission_type' => SalesInvoice::DISCOUNT_PERCENTAGE,
            'partner_commission_value' => 10,
            'partner_commission_amount' => 110,
            'net_revenue_after_partner_commission' => 990,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        $invoice->recordPayment([
            'payment_date' => '2026-04-26',
            'amount' => 300,
            'payment_method' => PaymentMethod::Cash->value,
            'reference_number' => 'CASH-INV-001',
            'notes' => 'دفعة أولى',
        ], $user);

        SalesInvoiceItem::factory()->create([
            'sales_invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_sale_price' => 600,
            'item_discount_type' => SalesInvoice::DISCOUNT_FIXED,
            'item_discount_value' => 0,
            'item_discount_amount' => 0,
            'line_total' => 1200,
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.print', $invoice))
            ->assertOk()
            ->assertSee('فاتورة بيع')
            ->assertSee('INV-PRINT-001')
            ->assertSee('Customer For Print')
            ->assertSee('Smart Lock')
            ->assertSee('إجمالي فاتورة العميل')
            ->assertSee('حالة السداد')
            ->assertSee('المدفوع')
            ->assertSee('المبلغ المتبقي')
            ->assertSee('طباعة / حفظ PDF')
            ->assertDontSee('عمولة الشريك')
            ->assertDontSee('Internal Partner Office')
            ->assertDontSee('110.00 ج.م');
    }

    public function test_payment_receipt_print_page_shows_payment_details(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Receipt Customer']);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-RECEIPT-001',
            'customer_id' => $customer->id,
            'gross_total' => 900,
            'net_revenue_after_partner_commission' => 900,
            'status' => SalesInvoiceStatus::Confirmed->value,
            'created_by' => $user->id,
        ]);

        $payment = $invoice->recordPayment([
            'payment_date' => '2026-04-26',
            'amount' => 250,
            'payment_method' => PaymentMethod::Instapay->value,
            'reference_number' => 'INST-250',
            'notes' => 'دفعة مقدمة',
        ], $user);

        $this->actingAs($user)
            ->get(route('sales-invoices.payments.print', [$invoice, $payment]))
            ->assertOk()
            ->assertSee('إيصال استلام')
            ->assertSee($payment->receipt_number)
            ->assertSee('Receipt Customer')
            ->assertSee('INV-RECEIPT-001')
            ->assertSee('إنستاباي')
            ->assertSee('250.00 ج.م')
            ->assertSee('650.00 ج.م')
            ->assertSee('دفعة مقدمة');
    }

    public function test_partner_settlement_print_page_shows_commission_values_for_partner_sales(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create(['name' => 'Settlement Customer']);
        $partner = Partner::factory()->create([
            'name' => 'Engineering Partner',
            'phone' => '+20 100 333 4444',
        ]);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-SETTLE-001',
            'customer_id' => $customer->id,
            'sales_channel' => SalesChannel::Partner->value,
            'partner_id' => $partner->id,
            'subtotal' => 1250,
            'gross_total' => 1250,
            'partner_commission_type' => SalesInvoice::DISCOUNT_PERCENTAGE,
            'partner_commission_value' => 12,
            'partner_commission_amount' => 150,
            'net_revenue_after_partner_commission' => 1100,
            'notes' => 'Commission payable after collection.',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertSee('طباعة مستند عمولة الشريك');

        $this->actingAs($user)
            ->get(route('sales-invoices.partner-settlement.print', $invoice))
            ->assertOk()
            ->assertSee('مستند عمولة شريك')
            ->assertSee('Engineering Partner')
            ->assertSee('INV-SETTLE-001')
            ->assertSee('Settlement Customer')
            ->assertSee('إجمالي فاتورة العميل')
            ->assertSee('1,250.00 ج.م')
            ->assertSee('12.00%')
            ->assertSee('150.00 ج.م')
            ->assertSee('1,100.00 ج.م')
            ->assertSee('Commission payable after collection.')
            ->assertSee('طباعة / حفظ PDF');
    }

    public function test_partner_settlement_print_page_is_not_available_for_direct_sales(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $invoice = SalesInvoice::factory()->create([
            'invoice_number' => 'INV-DIRECT-SETTLEMENT',
            'sales_channel' => SalesChannel::Direct->value,
            'partner_id' => null,
            'gross_total' => 600,
            'net_revenue_after_partner_commission' => 600,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.show', $invoice))
            ->assertOk()
            ->assertDontSee('طباعة مستند عمولة الشريك');

        $this->actingAs($user)
            ->get(route('sales-invoices.partner-settlement.print', $invoice))
            ->assertNotFound();
    }
}
