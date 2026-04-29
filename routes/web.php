<?php

use App\Enums\SalesChannel;
use App\Livewire\Auth\Login;
use App\Livewire\Categories\CategoryList;
use App\Livewire\Customers\CustomerList;
use App\Livewire\Customers\CustomerShow;
use App\Livewire\Dashboard;
use App\Livewire\Partners\PartnerList;
use App\Livewire\Partners\PartnerShow;
use App\Livewire\Products\ProductList;
use App\Livewire\Products\ProductShow;
use App\Livewire\PurchaseInvoices\PurchaseInvoiceCreate;
use App\Livewire\PurchaseInvoices\PurchaseInvoiceList;
use App\Livewire\PurchaseInvoices\PurchaseInvoiceShow;
use App\Livewire\Quotations\QuotationForm;
use App\Livewire\Quotations\QuotationList;
use App\Livewire\Quotations\QuotationShow;
use App\Livewire\Reports\SalesReport;
use App\Livewire\Reports\StockReport;
use App\Livewire\SalesInvoices\SalesInvoiceCreate;
use App\Livewire\SalesInvoices\SalesInvoiceList;
use App\Livewire\SalesInvoices\SalesInvoiceShow;
use App\Livewire\Settings\PrintTemplates\PrintTemplateForm;
use App\Livewire\Settings\PrintTemplates\PrintTemplateList;
use App\Livewire\Settings\DatabaseBackups\DatabaseBackupList;
use App\Livewire\Stock\ProductMovementHistory;
use App\Livewire\Stock\StockSummary;
use App\Livewire\Suppliers\SupplierList;
use App\Livewire\Suppliers\SupplierShow;
use App\Livewire\Users\RoleList;
use App\Livewire\Users\UserCreate;
use App\Livewire\Users\UserEdit;
use App\Livewire\Users\UserList;
use App\Models\PrintTemplate;
use App\Models\DatabaseBackup;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

// Auth routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');

    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    Route::middleware('permission:categories.manage')->group(function () {
        Route::get('/categories', CategoryList::class)->name('categories.index');
    });

    Route::middleware('permission:products.manage')->group(function () {
        Route::get('/products', ProductList::class)->name('products.index');
        Route::get('/products/{product}', ProductShow::class)->name('products.show');
    });

    Route::middleware('permission:suppliers.manage')->group(function () {
        Route::get('/suppliers', SupplierList::class)->name('suppliers.index');
        Route::get('/suppliers/{supplier}', SupplierShow::class)->name('suppliers.show');
    });

    Route::middleware('permission:purchases.manage')->group(function () {
        Route::get('/purchase-invoices', PurchaseInvoiceList::class)->name('purchase-invoices.index');
        Route::get('/purchase-invoices/create', PurchaseInvoiceCreate::class)->name('purchase-invoices.create');
        Route::get('/purchase-invoices/{purchaseInvoice}', PurchaseInvoiceShow::class)->name('purchase-invoices.show');
    });

    Route::middleware('permission:quotations.create')->group(function () {
        Route::get('/quotations', QuotationList::class)->name('quotations.index');
        Route::get('/quotations/create', QuotationForm::class)->name('quotations.create');
        Route::get('/quotations/{quotation}/print', function (Quotation $quotation) {
            $quotation->load(['customer', 'items.product', 'creator']);
            $selectedTemplate = PrintTemplate::resolveForDocument(PrintTemplate::TYPE_QUOTATION, request()->integer('template') ?: null);
            $availableTemplates = PrintTemplate::query()
                ->forDocumentType(PrintTemplate::TYPE_QUOTATION)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            $printSettings = $selectedTemplate->resolvedSettings();

            return view('quotations.print', [
                'quotation' => $quotation,
                'selectedTemplate' => $selectedTemplate,
                'availableTemplates' => $availableTemplates,
                'printSettings' => $printSettings,
                'documentTitle' => $selectedTemplate->title,
                'htmlTitle' => $selectedTemplate->title.' '.$quotation->quotation_number,
                'backRoute' => route('quotations.show', $quotation),
                'documentFooterText' => $printSettings['quotation']['footer_text'],
                'discountTypes' => Quotation::discountTypes(),
            ]);
        })->name('quotations.print');
        Route::get('/quotations/{quotation}', QuotationShow::class)->name('quotations.show');
    });

    Route::middleware('permission:quotations.edit')->group(function () {
        Route::get('/quotations/{quotation}/edit', QuotationForm::class)->name('quotations.edit');
    });

    Route::middleware('permission:sales.create')->group(function () {
        Route::get('/sales-invoices', SalesInvoiceList::class)->name('sales-invoices.index');
        Route::get('/sales-invoices/create', SalesInvoiceCreate::class)->name('sales-invoices.create');
        Route::get('/sales-invoices/{salesInvoice}/edit', SalesInvoiceCreate::class)->name('sales-invoices.edit');
        Route::get('/sales-invoices/{salesInvoice}/print', function (SalesInvoice $salesInvoice) {
            $salesInvoice->syncPaymentSummaryIfNeeded();
            $salesInvoice->refresh();
            $salesInvoice->load(['customer', 'items.product', 'creator', 'quotation']);
            $selectedTemplate = PrintTemplate::resolveForDocument(PrintTemplate::TYPE_SALES_INVOICE, request()->integer('template') ?: null);
            $availableTemplates = PrintTemplate::query()
                ->forDocumentType(PrintTemplate::TYPE_SALES_INVOICE)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            $printSettings = $selectedTemplate->resolvedSettings();

            return view('sales-invoices.print', [
                'invoice' => $salesInvoice,
                'selectedTemplate' => $selectedTemplate,
                'availableTemplates' => $availableTemplates,
                'printSettings' => $printSettings,
                'documentTitle' => $selectedTemplate->title,
                'htmlTitle' => $selectedTemplate->title.' '.$salesInvoice->invoice_number,
                'backRoute' => route('sales-invoices.show', $salesInvoice),
                'documentFooterText' => $printSettings['sales_invoice']['footer_text'],
                'discountTypes' => SalesInvoice::discountTypes(),
            ]);
        })->name('sales-invoices.print');
        Route::get('/sales-invoices/{salesInvoice}/payments/{salesInvoicePayment}/print', function (SalesInvoice $salesInvoice, SalesInvoicePayment $salesInvoicePayment) {
            abort_unless($salesInvoicePayment->sales_invoice_id === $salesInvoice->id, 404);

            $salesInvoice->syncPaymentSummaryIfNeeded();
            $salesInvoice->refresh();
            $salesInvoice->load(['customer', 'creator', 'quotation']);
            $salesInvoicePayment->load(['creator', 'receiver']);
            $selectedTemplate = PrintTemplate::resolveForDocument(PrintTemplate::TYPE_SALES_INVOICE, request()->integer('template') ?: null);
            $availableTemplates = PrintTemplate::query()
                ->forDocumentType(PrintTemplate::TYPE_SALES_INVOICE)
                ->active()
                ->orderByDesc('is_default')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
            $printSettings = $selectedTemplate->resolvedSettings();
            data_set($printSettings, 'warranty.enabled', false);

            return view('sales-invoices.payment-receipt-print', [
                'invoice' => $salesInvoice,
                'payment' => $salesInvoicePayment,
                'selectedTemplate' => $selectedTemplate,
                'availableTemplates' => $availableTemplates,
                'printSettings' => $printSettings,
                'documentTitle' => 'إيصال استلام',
                'htmlTitle' => 'إيصال استلام '.$salesInvoicePayment->receipt_number,
                'backRoute' => route('sales-invoices.show', $salesInvoice),
                'documentFooterText' => 'هذا الإيصال يثبت استلام المبلغ الموضح أعلاه من العميل.',
            ]);
        })->name('sales-invoices.payments.print');
        Route::get('/sales-invoices/{salesInvoice}/partner-settlement/print', function (SalesInvoice $salesInvoice) {
            abort_unless($salesInvoice->sales_channel === SalesChannel::Partner && $salesInvoice->partner_id, 404);

            $salesInvoice->load(['customer', 'partner', 'creator', 'quotation']);

            return view('sales-invoices.partner-settlement-print', [
                'invoice' => $salesInvoice,
                'company' => Setting::companyProfile(),
                'commissionTypes' => SalesInvoice::commissionTypes(),
            ]);
        })->name('sales-invoices.partner-settlement.print');
        Route::get('/sales-invoices/{salesInvoice}', SalesInvoiceShow::class)->name('sales-invoices.show');
    });

    Route::middleware('permission:stock.view')->group(function () {
        Route::get('/stock', StockSummary::class)->name('stock.index');
        Route::get('/stock/products/{product}/movements', ProductMovementHistory::class)->name('stock.movements.product');
    });

    Route::middleware('permission:customers.create')->group(function () {
        Route::get('/customers', CustomerList::class)->name('customers.index');
        Route::get('/customers/{customer}', CustomerShow::class)->name('customers.show');
    });

    Route::middleware('permission:partners.manage')->group(function () {
        Route::get('/partners', PartnerList::class)->name('partners.index');
        Route::get('/partners/{partner}', PartnerShow::class)->name('partners.show');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
        Route::get('/reports/stock', StockReport::class)->name('reports.stock');
    });

    // User management — admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users', UserList::class)->name('users.index');
        Route::get('/users/create', UserCreate::class)->name('users.create');
        Route::get('/users/{user}/edit', UserEdit::class)->name('users.edit');
        Route::get('/users/roles', RoleList::class)->name('users.roles');
        Route::get('/settings/backups', DatabaseBackupList::class)->name('settings.backups');
        Route::get('/settings/backups/{databaseBackup}/download', function (DatabaseBackup $databaseBackup) {
            abort_unless(\Illuminate\Support\Facades\Storage::disk(\App\Support\DatabaseBackupManager::STORAGE_DISK)->exists($databaseBackup->file_path), 404);

            return response()->download(
                \Illuminate\Support\Facades\Storage::disk(\App\Support\DatabaseBackupManager::STORAGE_DISK)->path($databaseBackup->file_path),
                $databaseBackup->original_file_name ?: $databaseBackup->file_name,
            );
        })->name('settings.backups.download');
    });

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings/print', PrintTemplateList::class)->name('settings.print');
        Route::get('/settings/print/create', PrintTemplateForm::class)->name('settings.print.create');
        Route::get('/settings/print/{printTemplate}/edit', PrintTemplateForm::class)->name('settings.print.edit');
    });
});
