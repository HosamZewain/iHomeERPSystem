<?php

namespace App\Console\Commands;

use App\Enums\CommissionType;
use App\Enums\PartnerType;
use App\Enums\PurchaseInvoiceStatus;
use App\Enums\QuotationStatus;
use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use SplFileObject;
use Throwable;

class ImportErpnextLegacyData extends Command
{
    protected $signature = 'ihome:import-erpnext
        {--path= : Folder containing the cleaned ERPNext CSV files}
        {--dry-run : Run the import inside a rollback transaction}
        {--skip-stock : Import documents without creating legacy stock movement/reconciliation records}';

    protected $description = 'Safely import cleaned legacy ERPNext CSV data into the iHome showroom database.';

    private const SOURCE = 'erpnext_cleaned_v2';

    private string $basePath = '';

    private bool $dryRun = false;

    private bool $skipStock = false;

    private ?int $adminId = null;

    private array $summary = [];

    private array $categoryIds = [];

    private array $supplierIds = [];

    private array $customerIds = [];

    private array $partnerIds = [];

    private array $productIds = [];

    private array $sellingPrices = [];

    private array $buyingPrices = [];

    private $logHandle = null;

    private string $logPath = '';

    public function handle(): int
    {
        $this->basePath = rtrim((string) ($this->option('path') ?: base_path('storage/app/imports/erpnext_migration_cleaned_v2')), DIRECTORY_SEPARATOR);
        $this->dryRun = (bool) $this->option('dry-run');
        $this->skipStock = (bool) $this->option('skip-stock');
        $this->adminId = User::query()->where('role', 'admin')->orderBy('id')->value('id');

        if (! is_dir($this->basePath)) {
            $this->error("Import path does not exist: {$this->basePath}");

            return self::FAILURE;
        }

        $this->openLog();
        $this->info(($this->dryRun ? '[DRY RUN] ' : '').'Importing cleaned ERPNext data from '.$this->basePath);

        DB::beginTransaction();

        try {
            $this->loadItemPrices();
            $this->importCategories();
            $this->importSuppliers();
            $this->importCustomers();
            $this->importPartners();
            $this->importProducts();
            $this->importPurchaseInvoices();
            $this->importQuotations();
            $this->importSalesInvoices();
            $this->deferSalesReturns();

            if (! $this->skipStock) {
                $this->createLegacyTransactionStockMovements();
                $this->reconcileStockSnapshot();
            }

            if ($this->dryRun) {
                DB::rollBack();
                $this->warn('Dry run complete. Database changes were rolled back.');
            } else {
                DB::commit();
                $this->info('Import committed.');
            }

            $this->printSummary();
            $this->info('Import log: '.$this->logPath);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            DB::rollBack();
            $this->error($exception->getMessage());
            $this->logIssue('import', 'failed', 'run', $exception->getMessage());

            return self::FAILURE;
        } finally {
            if (is_resource($this->logHandle)) {
                fclose($this->logHandle);
            }
        }
    }

    private function importCategories(): void
    {
        foreach ($this->csv('categories_clean.csv') as $row) {
            $legacyId = $row['legacy_category_name'] ?: $row['name'];
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                $this->skip('categories', $legacyId ?: 'row', 'Missing category name');

                continue;
            }

            $category = Category::query()->firstOrCreate(['name' => $name]);
            $this->categoryIds[$legacyId] = $category->id;
            $this->record('categories', $legacyId, $category);
        }
    }

    private function importSuppliers(): void
    {
        foreach ($this->csv('suppliers_clean.csv') as $row) {
            $legacyId = $row['legacy_id'] ?: $row['name'];
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                $this->skip('suppliers', $legacyId ?: 'row', 'Missing supplier name');

                continue;
            }

            $supplier = Supplier::withTrashed()->firstOrNew(['name' => $name]);
            $supplier->fill([
                'contact_person' => null,
                'phone' => $this->phone($row['phone'] ?? null),
                'email' => $this->nullable($row['email'] ?? null),
                'address' => $this->nullable($row['address'] ?? null),
                'notes' => $this->notes($row['notes'] ?? null, $legacyId, [
                    'Legacy supplier type' => $row['supplier_type'] ?? null,
                    'Legacy supplier group' => $row['supplier_group'] ?? null,
                    'Legacy tax id' => $row['tax_id'] ?? null,
                    'Legacy website' => $row['website'] ?? null,
                ]),
            ]);
            $supplier->deleted_at = null;
            $supplier->save();

            $this->supplierIds[$legacyId] = $supplier->id;
            $this->record('suppliers', $legacyId, $supplier);
        }
    }

    private function importCustomers(): void
    {
        foreach ($this->csv('customers_clean.csv') as $row) {
            $legacyId = $row['legacy_id'] ?: $row['name'];
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                $this->skip('customers', $legacyId ?: 'row', 'Missing customer name');

                continue;
            }

            $phone = $this->phone($row['phone'] ?? null);
            $customer = Customer::withTrashed()->firstOrNew([
                'name' => $name,
                'phone' => $phone,
            ]);
            $customer->fill([
                'email' => $this->nullable($row['email'] ?? null),
                'address' => $this->cleanLegacyText($row['address'] ?? null),
                'notes' => $this->notes($row['notes'] ?? null, $legacyId, [
                    'Legacy customer type' => $row['customer_type'] ?? null,
                    'Legacy tax id' => $row['tax_id'] ?? null,
                    'Legacy website' => $row['website'] ?? null,
                ]),
                'created_by' => $this->adminId,
            ]);
            $customer->deleted_at = null;
            $customer->save();

            $this->customerIds[$legacyId] = $customer->id;
            $this->record('customers', $legacyId, $customer);
        }
    }

    private function importPartners(): void
    {
        foreach ($this->csv('partners_clean.csv') as $row) {
            $legacyId = $row['legacy_id'] ?: $row['name'];
            $name = trim($row['name'] ?? '');

            if ($name === '') {
                $this->skip('partners', $legacyId ?: 'row', 'Missing partner name');

                continue;
            }

            $partner = Partner::withTrashed()->firstOrNew(['name' => $name]);
            $partner->fill([
                'type' => $this->partnerType($row['type'] ?? null),
                'contact_person' => null,
                'phone' => $this->phone($row['phone'] ?? null),
                'email' => $this->nullable($row['email'] ?? null),
                'address' => $this->nullable($row['address'] ?? null),
                'default_commission_type' => $this->commissionType($row['default_commission_type'] ?? null),
                'default_commission_value' => $this->decimal($row['default_commission_value'] ?? 0),
                'notes' => $this->notes($row['notes'] ?? null, $legacyId),
                'is_active' => $this->bool($row['is_active'] ?? true),
            ]);
            $partner->deleted_at = null;
            $partner->save();

            $this->partnerIds[$legacyId] = $partner->id;
            $this->record('partners', $legacyId, $partner);
        }
    }

    private function importProducts(): void
    {
        foreach ($this->csv('products_clean.csv') as $row) {
            $legacyId = $row['legacy_id'] ?: $row['sku'];
            $sku = trim($row['sku'] ?? '') ?: $legacyId;
            $name = trim($row['name'] ?? '') ?: $sku;

            if ($sku === '') {
                $this->skip('products', $legacyId ?: 'row', 'Missing product SKU');

                continue;
            }

            $categoryId = $this->categoryId($row['category_name'] ?? null);
            if (! $categoryId) {
                $this->skip('products', $legacyId, 'Missing category: '.($row['category_name'] ?? ''));

                continue;
            }

            $supplierId = $this->supplierId($row['primary_supplier_legacy_id'] ?? null, $row['primary_supplier_name'] ?? null);
            $salePrice = $this->decimal($row['sale_price'] ?? 0);
            $averageCost = $this->decimal($row['average_cost'] ?? 0);

            if ($salePrice <= 0 && isset($this->sellingPrices[$legacyId])) {
                $salePrice = $this->sellingPrices[$legacyId];
            }

            if ($averageCost <= 0 && isset($this->buyingPrices[$legacyId])) {
                $averageCost = $this->buyingPrices[$legacyId];
            }

            $product = Product::withTrashed()->firstOrNew(['internal_sku' => $sku]);
            $product->fill([
                'name' => $name,
                'barcode' => null,
                'image_path' => $this->nullable($row['image_path'] ?? null),
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'sale_price' => $salePrice,
                'current_average_cost' => $averageCost,
                'minimum_stock_alert_level' => $this->decimal($row['minimum_stock_alert'] ?? 0),
                'is_active' => $this->bool($row['is_active'] ?? true),
                'notes' => $this->notes($row['description'] ?? null, $legacyId, [
                    'Legacy UOM' => $row['uom'] ?? null,
                    'Legacy stock item' => $row['is_stock_item'] ?? null,
                    'Legacy current stock' => $row['current_stock'] ?? null,
                ]),
            ]);
            $product->deleted_at = null;
            $product->save();

            $this->productIds[$legacyId] = $product->id;

            if ($salePrice <= 0) {
                $this->logIssue('products', 'warning', $legacyId, 'Missing sale price; imported with 0.00');
            }

            $this->record('products', $legacyId, $product);
        }
    }

    private function importPurchaseInvoices(): void
    {
        foreach ($this->csv('purchase_invoices_clean.csv') as $row) {
            $legacyId = $row['legacy_id'];
            $invoiceNumber = $row['invoice_number'] ?: $legacyId;

            if (PurchaseInvoice::query()->where('invoice_number', $invoiceNumber)->exists()) {
                $this->skip('purchase_invoices', $legacyId, 'Invoice number already exists');
                $this->skipItemsFor('purchase_invoice_items_clean.csv', 'purchase_invoice_items', 'invoice_legacy_id', $legacyId, 'Parent purchase invoice already exists');

                continue;
            }

            $supplierId = $this->supplierId($row['supplier_legacy_id'] ?? null, $row['supplier_name'] ?? null);
            if (! $supplierId) {
                $this->skip('purchase_invoices', $legacyId, 'Missing supplier reference');

                continue;
            }

            $status = $this->purchaseStatus($row['status'] ?? null);
            $invoice = PurchaseInvoice::create([
                'invoice_number' => $invoiceNumber,
                'supplier_id' => $supplierId,
                'invoice_date' => $this->date($row['posting_date'] ?? null),
                'notes' => $this->notes($row['notes'] ?? null, $legacyId, [
                    'Legacy status' => $row['legacy_status'] ?? null,
                    'Legacy docstatus' => $row['docstatus'] ?? null,
                    'Legacy warehouse' => $row['warehouse'] ?? null,
                    'Legacy update_stock' => $row['update_stock'] ?? null,
                ]),
                'subtotal' => abs($this->decimal($row['subtotal'] ?? 0)),
                'total' => abs($this->decimal($row['grand_total'] ?? 0)),
                'status' => $status,
                'confirmed_at' => $status === PurchaseInvoiceStatus::Confirmed ? $this->date($row['posting_date'] ?? null) : null,
                'cancelled_at' => $status === PurchaseInvoiceStatus::Cancelled ? $this->date($row['posting_date'] ?? null) : null,
                'created_by' => $this->adminId,
                'confirmed_by' => $status === PurchaseInvoiceStatus::Confirmed ? $this->adminId : null,
            ]);

            $this->record('purchase_invoices', $legacyId, $invoice);
            $this->importPurchaseInvoiceItems($invoice, $legacyId);
        }
    }

    private function importPurchaseInvoiceItems(PurchaseInvoice $invoice, string $invoiceLegacyId): void
    {
        foreach ($this->csv('purchase_invoice_items_clean.csv') as $row) {
            if (($row['invoice_legacy_id'] ?? null) !== $invoiceLegacyId) {
                continue;
            }

            $legacyId = $row['legacy_id'];
            $productId = $this->productId($row['product_legacy_id'] ?? null);

            if (! $productId) {
                $this->skip('purchase_invoice_items', $legacyId, 'Missing product reference');

                continue;
            }

            $item = $invoice->items()->create([
                'product_id' => $productId,
                'quantity' => abs($this->decimal($row['qty'] ?? 0)),
                'unit_cost' => abs($this->decimal($row['unit_cost'] ?? 0)),
                'line_total' => abs($this->decimal($row['line_total'] ?? 0)),
            ]);
            $this->record('purchase_invoice_items', $legacyId, $item);
        }
    }

    private function importQuotations(): void
    {
        foreach ($this->csv('quotations_clean.csv') as $row) {
            $legacyId = $row['legacy_id'];
            $quotationNumber = $row['quotation_number'] ?: $legacyId;

            if (Quotation::query()->where('quotation_number', $quotationNumber)->exists()) {
                $this->skip('quotations', $legacyId, 'Quotation number already exists');
                $this->skipItemsFor('quotation_items_clean.csv', 'quotation_items', 'quotation_legacy_id', $legacyId, 'Parent quotation already exists');

                continue;
            }

            $customerId = $this->customerId($row['customer_legacy_id'] ?? null, $row['customer_name'] ?? null);
            if (! $customerId) {
                $this->skip('quotations', $legacyId, 'Missing customer reference');

                continue;
            }

            $discount = $this->invoiceDiscount($row);
            $status = $this->quotationStatus($row['status'] ?? null);
            $quotation = Quotation::create([
                'quotation_number' => $quotationNumber,
                'customer_id' => $customerId,
                'quotation_date' => $this->date($row['quotation_date'] ?? null),
                'notes' => $this->notes($row['notes'] ?? null, $legacyId, [
                    'Legacy status' => $row['legacy_status'] ?? null,
                    'Legacy valid till' => $row['valid_till'] ?? null,
                    'Legacy source' => $row['source'] ?? null,
                    'Legacy warehouse' => $row['warehouse'] ?? null,
                ]),
                'subtotal' => abs($this->decimal($row['subtotal_before_invoice_discount'] ?? 0)),
                'invoice_discount_type' => $discount['type'],
                'invoice_discount_value' => $discount['value'],
                'invoice_discount_amount' => abs($this->decimal($row['invoice_discount_amount'] ?? 0)),
                'total' => abs($this->decimal($row['grand_total'] ?? 0)),
                'status' => $status,
                'created_by' => $this->adminId,
            ]);

            $this->record('quotations', $legacyId, $quotation);
            $this->importQuotationItems($quotation, $legacyId);
        }
    }

    private function importQuotationItems(Quotation $quotation, string $quotationLegacyId): void
    {
        $sortOrder = 1;

        foreach ($this->csv('quotation_items_clean.csv') as $row) {
            if (($row['quotation_legacy_id'] ?? null) !== $quotationLegacyId) {
                continue;
            }

            $legacyId = $row['legacy_id'];
            $productId = $this->productId($row['product_legacy_id'] ?? null);

            if (! $productId) {
                $this->skip('quotation_items', $legacyId, 'Missing product reference');

                continue;
            }

            $discount = $this->itemDiscount($row);
            $item = $quotation->items()->create([
                'product_id' => $productId,
                'sort_order' => $sortOrder++,
                'quantity' => abs($this->decimal($row['qty'] ?? 0)),
                'unit_sale_price' => abs($this->decimal($row['unit_price'] ?? 0)),
                'item_discount_type' => $discount['type'],
                'item_discount_value' => $discount['value'],
                'item_discount_amount' => abs($this->decimal($row['source_item_discount_amount'] ?? 0)),
                'line_total' => abs($this->decimal($row['line_total_before_invoice_discount'] ?? 0)),
            ]);
            $this->record('quotation_items', $legacyId, $item);
        }
    }

    private function importSalesInvoices(): void
    {
        foreach ($this->csv('sales_invoices_clean.csv') as $row) {
            $legacyId = $row['legacy_id'];
            $invoiceNumber = $row['invoice_number'] ?: $legacyId;

            if (($row['status'] ?? null) === 'returned') {
                $this->skip('sales_invoices', $legacyId, 'Deferred: legacy returned sales invoices are not yet mapped into the current return workflow during import');
                $this->skipItemsFor('sales_invoice_items_clean.csv', 'sales_invoice_items', 'invoice_legacy_id', $legacyId, 'Parent returned sales invoice deferred until import mapping is added');

                continue;
            }

            if (SalesInvoice::query()->where('invoice_number', $invoiceNumber)->exists()) {
                $this->skip('sales_invoices', $legacyId, 'Invoice number already exists');
                $this->skipItemsFor('sales_invoice_items_clean.csv', 'sales_invoice_items', 'invoice_legacy_id', $legacyId, 'Parent sales invoice already exists');

                continue;
            }

            $customerId = $this->customerId($row['customer_legacy_id'] ?? null, $row['customer_name'] ?? null);
            if (! $customerId) {
                $this->skip('sales_invoices', $legacyId, 'Missing customer reference');

                continue;
            }

            $status = $this->salesStatus($row['status'] ?? null);
            $discount = $this->invoiceDiscount($row);
            $channel = ($row['sales_channel'] ?? 'direct') === SalesChannel::Partner->value ? SalesChannel::Partner : SalesChannel::Direct;
            $partnerId = $channel === SalesChannel::Partner ? $this->partnerId($row['partner_legacy_id'] ?? null) : null;
            $grossTotal = abs($this->decimal($row['grand_total'] ?? 0));
            $commissionAmount = abs($this->decimal($row['partner_commission_amount'] ?? 0));
            $netRevenue = abs($this->decimal($row['net_revenue_after_partner_commission'] ?? $grossTotal - $commissionAmount));

            if ($channel === SalesChannel::Partner && ! $partnerId) {
                $this->logIssue('sales_invoices', 'warning', $legacyId, 'Partner reference missing; imported as direct sale');
                $channel = SalesChannel::Direct;
            }

            $invoice = SalesInvoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $customerId,
                'sales_channel' => $channel,
                'partner_id' => $partnerId,
                'invoice_date' => $this->date($row['posting_date'] ?? null),
                'notes' => $this->notes($row['notes'] ?? null, $legacyId, [
                    'Legacy status' => $row['legacy_status'] ?? null,
                    'Legacy docstatus' => $row['docstatus'] ?? null,
                    'Legacy source' => $row['source'] ?? null,
                    'Legacy warehouse' => $row['warehouse'] ?? null,
                ]),
                'subtotal' => abs($this->decimal($row['subtotal_before_invoice_discount'] ?? 0)),
                'invoice_discount_type' => $discount['type'],
                'invoice_discount_value' => $discount['value'],
                'invoice_discount_amount' => abs($this->decimal($row['invoice_discount_amount'] ?? 0)),
                'gross_total' => $grossTotal,
                'partner_commission_type' => $this->commissionType($row['partner_commission_type'] ?? null),
                'partner_commission_value' => abs($this->decimal($row['partner_commission_value'] ?? 0)),
                'partner_commission_amount' => $channel === SalesChannel::Partner ? $commissionAmount : 0,
                'net_revenue_after_partner_commission' => $channel === SalesChannel::Partner ? $netRevenue : $grossTotal,
                'total_cost' => 0,
                'total_profit' => $channel === SalesChannel::Partner ? $netRevenue : $grossTotal,
                'status' => $status,
                'confirmed_at' => $status === SalesInvoiceStatus::Confirmed ? $this->date($row['posting_date'] ?? null) : null,
                'cancelled_at' => $status === SalesInvoiceStatus::Cancelled ? $this->date($row['posting_date'] ?? null) : null,
                'created_by' => $this->adminId,
                'confirmed_by' => $status === SalesInvoiceStatus::Confirmed ? $this->adminId : null,
            ]);

            $this->record('sales_invoices', $legacyId, $invoice);
            $this->importSalesInvoiceItems($invoice, $legacyId);
        }
    }

    private function importSalesInvoiceItems(SalesInvoice $invoice, string $invoiceLegacyId): void
    {
        $sortOrder = 1;

        foreach ($this->csv('sales_invoice_items_clean.csv') as $row) {
            if (($row['invoice_legacy_id'] ?? null) !== $invoiceLegacyId) {
                continue;
            }

            $legacyId = $row['legacy_id'];
            $productId = $this->productId($row['product_legacy_id'] ?? null);

            if (! $productId) {
                $this->skip('sales_invoice_items', $legacyId, 'Missing product reference');

                continue;
            }

            $discount = $this->itemDiscount($row);
            $product = Product::query()->find($productId);
            $quantity = abs($this->decimal($row['qty'] ?? 0));
            $lineTotal = abs($this->decimal($row['line_total_before_invoice_discount'] ?? 0));
            $costAtSale = (float) ($product?->current_average_cost ?? 0);
            $lineCost = round($quantity * $costAtSale, 2);

            $item = $invoice->items()->create([
                'product_id' => $productId,
                'sort_order' => $sortOrder++,
                'quantity' => $quantity,
                'unit_sale_price' => abs($this->decimal($row['unit_price'] ?? 0)),
                'item_discount_type' => $discount['type'],
                'item_discount_value' => $discount['value'],
                'item_discount_amount' => abs($this->decimal($row['source_item_discount_amount'] ?? 0)),
                'cost_at_sale_time' => $costAtSale,
                'line_total' => $lineTotal,
                'line_profit' => round($lineTotal - $lineCost, 2),
            ]);
            $this->record('sales_invoice_items', $legacyId, $item);
        }

        $totalCost = round($invoice->items()->get()->sum(fn (SalesInvoiceItem $item) => (float) $item->quantity * (float) $item->cost_at_sale_time), 2);
        $invoice->update([
            'total_cost' => $totalCost,
            'total_profit' => round((float) $invoice->net_revenue_after_partner_commission - $totalCost, 2),
        ]);
    }

    private function createLegacyTransactionStockMovements(): void
    {
        $events = [];

        PurchaseInvoiceItem::query()
            ->whereHas('purchaseInvoice', fn ($query) => $query->where('status', PurchaseInvoiceStatus::Confirmed->value))
            ->with('purchaseInvoice')
            ->get()
            ->each(function (PurchaseInvoiceItem $item) use (&$events) {
                $events[] = [
                    'date' => $item->purchaseInvoice->invoice_date,
                    'kind' => 'purchase',
                    'item' => $item,
                ];
            });

        SalesInvoiceItem::query()
            ->whereHas('salesInvoice', fn ($query) => $query->where('status', SalesInvoiceStatus::Confirmed->value))
            ->with('salesInvoice')
            ->get()
            ->each(function (SalesInvoiceItem $item) use (&$events) {
                $events[] = [
                    'date' => $item->salesInvoice->invoice_date,
                    'kind' => 'sale',
                    'item' => $item,
                ];
            });

        usort($events, fn (array $a, array $b) => [$a['date']->toDateString(), $a['kind'], $a['item']->id] <=> [$b['date']->toDateString(), $b['kind'], $b['item']->id]);

        foreach ($events as $event) {
            if ($event['kind'] === 'purchase') {
                $this->createPurchaseMovement($event['item']);
            } else {
                $this->createSalesMovement($event['item']);
            }
        }
    }

    private function createPurchaseMovement(PurchaseInvoiceItem $item): void
    {
        if (StockMovement::query()->where('source_type', StockMovement::SOURCE_PURCHASE_ITEM)->where('source_id', $item->id)->exists()) {
            $this->bump('stock_movements', 'skipped');

            return;
        }

        $product = Product::query()->findOrFail($item->product_id);
        $quantity = abs((float) $item->quantity);
        $currentQuantity = $product->current_stock_quantity;
        $balanceAfter = round($currentQuantity + $quantity, 2);

        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => StockMovement::TYPE_PURCHASE_IN,
            'source_type' => StockMovement::SOURCE_PURCHASE_ITEM,
            'source_id' => $item->id,
            'created_by' => $this->adminId,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'unit_cost' => abs((float) $item->unit_cost),
            'total_cost' => abs((float) $item->line_total),
            'movement_date' => $item->purchaseInvoice->invoice_date,
            'notes' => 'استيراد ERPNext - فاتورة شراء '.$item->purchaseInvoice->invoice_number,
        ]);

        $this->bump('stock_movements', 'imported');
    }

    private function createSalesMovement(SalesInvoiceItem $item): void
    {
        if (StockMovement::query()->where('source_type', StockMovement::SOURCE_SALES_ITEM)->where('source_id', $item->id)->exists()) {
            $this->bump('stock_movements', 'skipped');

            return;
        }

        $product = Product::query()->findOrFail($item->product_id);
        $quantity = -abs((float) $item->quantity);
        $balanceAfter = round($product->current_stock_quantity + $quantity, 2);
        $unitCost = abs((float) $item->cost_at_sale_time);

        StockMovement::create([
            'product_id' => $product->id,
            'movement_type' => StockMovement::TYPE_SALE_OUT,
            'source_type' => StockMovement::SOURCE_SALES_ITEM,
            'source_id' => $item->id,
            'created_by' => $this->adminId,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
            'unit_cost' => $unitCost,
            'total_cost' => round(abs((float) $item->quantity) * $unitCost, 2),
            'movement_date' => $item->salesInvoice->invoice_date,
            'notes' => 'استيراد ERPNext - فاتورة بيع '.$item->salesInvoice->invoice_number,
        ]);

        $this->bump('stock_movements', 'imported');
    }

    private function reconcileStockSnapshot(): void
    {
        $snapshot = [];
        foreach ($this->csv('stock_snapshot_current_from_bin.csv') as $row) {
            $snapshot[$row['product_legacy_id']] = $row;
        }

        foreach ($this->productIds as $legacyId => $productId) {
            $product = Product::query()->find($productId);
            if (! $product) {
                continue;
            }

            $targetQuantity = isset($snapshot[$legacyId]) ? $this->decimal($snapshot[$legacyId]['current_stock'] ?? 0) : 0.0;
            $targetCost = isset($snapshot[$legacyId]) ? $this->decimal($snapshot[$legacyId]['bin_valuation_rate'] ?? 0) : (float) $product->current_average_cost;
            $currentQuantity = $product->current_stock_quantity;
            $delta = round($targetQuantity - $currentQuantity, 2);

            if ($targetCost > 0) {
                $product->update(['current_average_cost' => $targetCost]);
            }

            if (abs($delta) < 0.01) {
                $this->bump('stock_snapshot', 'skipped');

                continue;
            }

            $movementType = $delta > 0 ? StockMovement::TYPE_ADJUSTMENT_IN : StockMovement::TYPE_ADJUSTMENT_OUT;
            StockMovement::create([
                'product_id' => $product->id,
                'movement_type' => $movementType,
                'source_type' => StockMovement::SOURCE_ADJUSTMENT,
                'source_id' => $product->id,
                'created_by' => $this->adminId,
                'quantity' => $delta,
                'balance_after' => $targetQuantity,
                'unit_cost' => max($targetCost, 0),
                'total_cost' => round(abs($delta) * max($targetCost, 0), 2),
                'movement_date' => now()->toDateString(),
                'notes' => 'استيراد ERPNext - تسوية إلى رصيد Bin الحالي',
            ]);
            $this->bump('stock_snapshot', 'imported');
        }
    }

    private function deferSalesReturns(): void
    {
        foreach ($this->csv('sales_returns_clean.csv') as $row) {
            $this->skip('sales_returns', $row['legacy_id'] ?: $row['invoice_number'], 'Deferred: current app now supports full invoice returns, but legacy sales return import mapping is not implemented yet');
        }

        foreach ($this->csv('sales_return_items_clean.csv') as $row) {
            $this->skip('sales_return_items', $row['legacy_id'], 'Deferred: current app now supports full invoice returns, but legacy sales return item import mapping is not implemented yet');
        }
    }

    private function skipItemsFor(string $fileName, string $entity, string $parentColumn, string $parentLegacyId, string $message): void
    {
        foreach ($this->csv($fileName) as $row) {
            if (($row[$parentColumn] ?? null) === $parentLegacyId) {
                $this->skip($entity, $row['legacy_id'] ?? $parentLegacyId, $message);
            }
        }
    }

    private function loadItemPrices(): void
    {
        foreach ($this->csv('item_prices_clean.csv') as $row) {
            $productLegacyId = $row['product_legacy_id'] ?? null;
            $rate = $this->decimal($row['price_list_rate'] ?? 0);

            if (! $productLegacyId || $rate <= 0) {
                continue;
            }

            if ($this->bool($row['selling'] ?? false)) {
                $this->sellingPrices[$productLegacyId] = $rate;
            }

            if ($this->bool($row['buying'] ?? false)) {
                $this->buyingPrices[$productLegacyId] = $rate;
            }
        }
    }

    private function record(string $entity, string $legacyId, Model $model, string $status = 'imported', ?string $message = null): void
    {
        DB::table('legacy_import_records')->updateOrInsert(
            [
                'source' => self::SOURCE,
                'entity' => $entity,
                'legacy_id' => $legacyId,
            ],
            [
                'model_type' => $model::class,
                'model_id' => $model->getKey(),
                'status' => $status,
                'message' => $message,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->bump($entity, $status);
    }

    private function skip(string $entity, string $legacyId, string $message): void
    {
        DB::table('legacy_import_records')->updateOrInsert(
            [
                'source' => self::SOURCE,
                'entity' => $entity,
                'legacy_id' => $legacyId,
            ],
            [
                'model_type' => null,
                'model_id' => null,
                'status' => 'skipped',
                'message' => $message,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->bump($entity, 'skipped');
        $this->logIssue($entity, 'skipped', $legacyId, $message);
    }

    private function csv(string $fileName): iterable
    {
        $path = $this->basePath.DIRECTORY_SEPARATOR.$fileName;

        if (! is_file($path)) {
            throw new \RuntimeException("Missing import file: {$path}");
        }

        $file = new SplFileObject($path, 'rb');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
        $headers = [];

        foreach ($file as $index => $row) {
            if ($row === [null] || $row === false) {
                continue;
            }

            if ($index === 0) {
                $headers = array_map(fn ($header) => $this->stripBom((string) $header), $row);

                continue;
            }

            if ($headers === []) {
                continue;
            }

            $values = array_pad($row, count($headers), null);
            yield array_combine($headers, array_slice($values, 0, count($headers)));
        }
    }

    private function categoryId(?string $name): ?int
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        if (! isset($this->categoryIds[$name])) {
            $category = Category::query()->firstOrCreate(['name' => $name]);
            $this->categoryIds[$name] = $category->id;
        }

        return $this->categoryIds[$name];
    }

    private function supplierId(?string $legacyId, ?string $name): ?int
    {
        $legacyId = trim((string) $legacyId);
        $name = trim((string) $name);

        if ($legacyId !== '' && isset($this->supplierIds[$legacyId])) {
            return $this->supplierIds[$legacyId];
        }

        if ($name !== '') {
            $supplier = Supplier::query()->where('name', $name)->first();
            if ($supplier) {
                return $supplier->id;
            }
        }

        return null;
    }

    private function customerId(?string $legacyId, ?string $name): ?int
    {
        $legacyId = trim((string) $legacyId);
        $name = trim((string) $name);

        if ($legacyId !== '' && isset($this->customerIds[$legacyId])) {
            return $this->customerIds[$legacyId];
        }

        if ($name !== '') {
            $customer = Customer::query()->where('name', $name)->first();
            if ($customer) {
                return $customer->id;
            }
        }

        return null;
    }

    private function partnerId(?string $legacyId): ?int
    {
        $legacyId = trim((string) $legacyId);

        return $legacyId !== '' ? ($this->partnerIds[$legacyId] ?? null) : null;
    }

    private function productId(?string $legacyId): ?int
    {
        $legacyId = trim((string) $legacyId);

        return $legacyId !== '' ? ($this->productIds[$legacyId] ?? null) : null;
    }

    private function purchaseStatus(?string $status): PurchaseInvoiceStatus
    {
        return match ($status) {
            PurchaseInvoiceStatus::Confirmed->value => PurchaseInvoiceStatus::Confirmed,
            PurchaseInvoiceStatus::Cancelled->value => PurchaseInvoiceStatus::Cancelled,
            default => PurchaseInvoiceStatus::Draft,
        };
    }

    private function salesStatus(?string $status): SalesInvoiceStatus
    {
        return match ($status) {
            SalesInvoiceStatus::Confirmed->value => SalesInvoiceStatus::Confirmed,
            SalesInvoiceStatus::Cancelled->value => SalesInvoiceStatus::Cancelled,
            default => SalesInvoiceStatus::Draft,
        };
    }

    private function quotationStatus(?string $status): QuotationStatus
    {
        return match ($status) {
            QuotationStatus::Expired->value => QuotationStatus::Expired,
            QuotationStatus::Sent->value => QuotationStatus::Sent,
            QuotationStatus::Approved->value => QuotationStatus::Approved,
            QuotationStatus::Rejected->value, 'cancelled' => QuotationStatus::Rejected,
            QuotationStatus::Converted->value => QuotationStatus::Converted,
            default => QuotationStatus::Draft,
        };
    }

    private function partnerType(?string $type): string
    {
        return match ($type) {
            PartnerType::EngineeringOffice->value => PartnerType::EngineeringOffice->value,
            PartnerType::Individual->value => PartnerType::Individual->value,
            default => PartnerType::Company->value,
        };
    }

    private function commissionType(?string $type): string
    {
        return $type === CommissionType::Percentage->value
            ? CommissionType::Percentage->value
            : CommissionType::Fixed->value;
    }

    private function invoiceDiscount(array $row): array
    {
        $percentage = abs($this->decimal($row['invoice_discount_percentage'] ?? 0));
        $amount = abs($this->decimal($row['invoice_discount_amount'] ?? 0));
        $type = ($row['invoice_discount_type'] ?? '') === SalesInvoice::DISCOUNT_PERCENTAGE || $percentage > 0
            ? SalesInvoice::DISCOUNT_PERCENTAGE
            : SalesInvoice::DISCOUNT_FIXED;

        return [
            'type' => $type,
            'value' => $type === SalesInvoice::DISCOUNT_PERCENTAGE ? $percentage : $amount,
        ];
    }

    private function itemDiscount(array $row): array
    {
        $percentage = abs($this->decimal($row['source_item_discount_percentage'] ?? 0));
        $amount = abs($this->decimal($row['source_item_discount_amount'] ?? 0));
        $type = $percentage > 0 ? SalesInvoice::DISCOUNT_PERCENTAGE : SalesInvoice::DISCOUNT_FIXED;

        return [
            'type' => $type,
            'value' => $type === SalesInvoice::DISCOUNT_PERCENTAGE ? $percentage : $amount,
        ];
    }

    private function decimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        return round((float) str_replace(',', '', (string) $value), 2);
    }

    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function date(?string $value): string
    {
        return $value ? Carbon::parse($value)->toDateString() : now()->toDateString();
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function phone(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_substr($value, 0, 20);
    }

    private function cleanLegacyText(?string $value): ?string
    {
        $value = $this->nullable($value);

        if ($value === null) {
            return null;
        }

        return trim(str_replace(["\r\n", "\r", "\nn"], "\n", $value));
    }

    private function notes(?string $base, string $legacyId, array $extra = []): string
    {
        $parts = [];

        if ($this->nullable($base)) {
            $parts[] = $this->cleanLegacyText($base);
        }

        $parts[] = 'Imported from ERPNext legacy id: '.$legacyId;

        foreach ($extra as $label => $value) {
            if ($this->nullable((string) $value)) {
                $parts[] = $label.': '.$value;
            }
        }

        return implode("\n", $parts);
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }

    private function bump(string $entity, string $status): void
    {
        $this->summary[$entity][$status] = ($this->summary[$entity][$status] ?? 0) + 1;
    }

    private function openLog(): void
    {
        $this->logPath = storage_path('logs/erpnext_import_'.now()->format('Ymd_His').($this->dryRun ? '_dry_run' : '').'.csv');
        $this->logHandle = fopen($this->logPath, 'wb');
        fputcsv($this->logHandle, ['entity', 'status', 'legacy_id', 'message']);
    }

    private function logIssue(string $entity, string $status, string $legacyId, string $message): void
    {
        if ($status === 'warning') {
            $this->bump($entity, 'warning');
        }

        if (is_resource($this->logHandle)) {
            fputcsv($this->logHandle, [$entity, $status, $legacyId, $message]);
        }
    }

    private function printSummary(): void
    {
        $rows = [];

        foreach ($this->summary as $entity => $counts) {
            $rows[] = [
                $entity,
                (string) ($counts['imported'] ?? 0),
                (string) ($counts['skipped'] ?? 0),
                (string) ($counts['warning'] ?? 0),
            ];
        }

        $this->table(['Entity', 'Imported', 'Skipped', 'Warnings'], $rows);
    }
}
