<?php

namespace Tests\Feature;

use App\Livewire\Products\ProductList;
use App\Livewire\PurchaseInvoices\PurchaseInvoiceList;
use App\Livewire\Quotations\QuotationList;
use App\Livewire\Customers\CustomerList;
use App\Livewire\SalesInvoices\SalesInvoiceList;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListingTimestampsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_list_shows_timestamp_columns_and_sorts_by_them(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TIME-OLD',
            'customer_id' => $customer->id,
            'created_at' => '2026-04-20 08:00:00',
            'updated_at' => '2026-04-22 08:00:00',
        ]);

        SalesInvoice::factory()->create([
            'invoice_number' => 'INV-TIME-NEW',
            'customer_id' => $customer->id,
            'created_at' => '2026-04-21 08:00:00',
            'updated_at' => '2026-04-23 08:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('sales-invoices.index'))
            ->assertOk()
            ->assertSee('حالة السداد')
            ->assertSee('تاريخ الإنشاء')
            ->assertSee('آخر تحديث');

        Livewire::actingAs($user)
            ->test(SalesInvoiceList::class)
            ->set('sortField', 'payment_status')
            ->set('sortDirection', 'asc')
            ->assertSee('غير مدفوع')
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['INV-TIME-OLD', 'INV-TIME-NEW'])
            ->set('sortField', 'updated_at')
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['INV-TIME-NEW', 'INV-TIME-OLD']);
    }

    public function test_quotation_list_shows_timestamp_columns_and_sorts_by_them(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $customer = Customer::factory()->create();

        Quotation::factory()->create([
            'quotation_number' => 'QUO-TIME-OLD',
            'customer_id' => $customer->id,
            'created_at' => '2026-04-20 08:00:00',
            'updated_at' => '2026-04-22 08:00:00',
        ]);

        Quotation::factory()->create([
            'quotation_number' => 'QUO-TIME-NEW',
            'customer_id' => $customer->id,
            'created_at' => '2026-04-21 08:00:00',
            'updated_at' => '2026-04-23 08:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('quotations.index'))
            ->assertOk()
            ->assertSee('تاريخ الإنشاء')
            ->assertSee('آخر تحديث');

        Livewire::actingAs($user)
            ->test(QuotationList::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['QUO-TIME-OLD', 'QUO-TIME-NEW'])
            ->set('sortField', 'updated_at')
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['QUO-TIME-NEW', 'QUO-TIME-OLD']);
    }

    public function test_product_list_shows_timestamp_columns_and_sorts_by_them(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $category = Category::factory()->create();

        Product::factory()->create([
            'name' => 'Timestamp Product Old',
            'internal_sku' => 'TIME-OLD',
            'category_id' => $category->id,
            'created_at' => '2026-04-20 08:00:00',
            'updated_at' => '2026-04-22 08:00:00',
        ]);

        Product::factory()->create([
            'name' => 'Timestamp Product New',
            'internal_sku' => 'TIME-NEW',
            'category_id' => $category->id,
            'created_at' => '2026-04-21 08:00:00',
            'updated_at' => '2026-04-23 08:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('تاريخ الإنشاء')
            ->assertSee('آخر تحديث');

        Livewire::actingAs($user)
            ->test(ProductList::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['Timestamp Product Old', 'Timestamp Product New'])
            ->set('sortField', 'updated_at')
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['Timestamp Product New', 'Timestamp Product Old']);
    }

    public function test_purchase_invoice_list_shows_timestamp_columns_and_sorts_by_them(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::factory()->create();

        PurchaseInvoice::factory()->create([
            'invoice_number' => 'PINV-TIME-OLD',
            'supplier_id' => $supplier->id,
            'created_at' => '2026-04-20 08:00:00',
            'updated_at' => '2026-04-22 08:00:00',
        ]);

        PurchaseInvoice::factory()->create([
            'invoice_number' => 'PINV-TIME-NEW',
            'supplier_id' => $supplier->id,
            'created_at' => '2026-04-21 08:00:00',
            'updated_at' => '2026-04-23 08:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('purchase-invoices.index'))
            ->assertOk()
            ->assertSee('تاريخ الإنشاء')
            ->assertSee('آخر تحديث');

        Livewire::actingAs($user)
            ->test(PurchaseInvoiceList::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['PINV-TIME-OLD', 'PINV-TIME-NEW'])
            ->set('sortField', 'updated_at')
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['PINV-TIME-NEW', 'PINV-TIME-OLD']);
    }

    public function test_customer_list_shows_timestamp_columns_and_sorts_by_them(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        Customer::factory()->create([
            'name' => 'Customer Time Old',
            'created_at' => '2026-04-20 08:00:00',
            'updated_at' => '2026-04-22 08:00:00',
        ]);

        Customer::factory()->create([
            'name' => 'Customer Time New',
            'created_at' => '2026-04-21 08:00:00',
            'updated_at' => '2026-04-23 08:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('تاريخ الإنشاء')
            ->assertSee('آخر تحديث');

        Livewire::actingAs($user)
            ->test(CustomerList::class)
            ->set('sortField', 'created_at')
            ->set('sortDirection', 'asc')
            ->assertSeeInOrder(['Customer Time Old', 'Customer Time New'])
            ->set('sortField', 'updated_at')
            ->set('sortDirection', 'desc')
            ->assertSeeInOrder(['Customer Time New', 'Customer Time Old']);
    }
}
