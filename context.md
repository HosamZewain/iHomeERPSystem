# iHome Showroom System Context

## How Future Tools Should Use This File

- Read this file before making any code, database, UI, deployment, or import changes.
- Treat this file as the persistent project memory for future tools and AI agents.
- Verify the current codebase before changing behavior; this file is a guide, not a substitute for inspection.
- Keep changes focused on the requested task and preserve existing architecture unless a change is strictly necessary.
- After implementing a change, update this file with the new current state, new rules, and any known limitations.

## Context Maintenance Rule

Every future change must:

- Read `context.md` first.
- Understand the current project constraints and business rules.
- Inspect the relevant current code before editing.
- Make focused, Laravel-native changes.
- Avoid unrelated refactors or redesigns.
- Update `context.md` after implementation so the next tool starts from accurate context.

## Project Overview

- Project name: iHome Showroom System.
- Purpose: Arabic-first showroom management system for iHome smart home products.
- Target use case: manage master data, products, suppliers, customers, partners, quotations, purchase invoices, sales invoices, stock, reports, and printed business documents.
- Primary users:
  - Admins and managers.
  - Sales staff creating quotations and sales invoices on desktop/mobile.
  - Purchasing/inventory users managing purchase invoices and stock visibility.
  - Managers reviewing dashboard and reports.

## Tech Stack

- Backend: Laravel 13.
- UI: Livewire 4, Blade, Tailwind CSS 4, Vite.
- Database: MySQL.
- Auth/roles: Laravel auth model with custom role/permission enum logic.
- Locale/UI: Arabic-first.
- Layout direction: RTL-first.
- Currency: EGP displayed as `ج.م` through `App\Support\Money::format()`.
- Responsive expectation: desktop and mobile flows must stay usable, especially invoice/quotation entry.
- PWA: basic PWA support is implemented with `public/manifest.json`, `public/sw.js`, `public/offline.html`, and icons under `public/images`.
- Deployment note: Hostinger shared hosting may not have NPM; compiled Vite assets in `public/build` are tracked so GitHub deployment can include them.

## Deployment Context: Hostinger Shared Hosting

### Current Production Target

- Hosting: Hostinger shared hosting.
- Production domain: `https://erp.ihome-store.com`.
- Server user seen during deployment: `u470070883`.
- Production domain root:
  - `/home/u470070883/domains/erp.ihome-store.com`
- Production Laravel app folder:
  - `/home/u470070883/domains/erp.ihome-store.com/app`
- Production public web root:
  - `/home/u470070883/domains/erp.ihome-store.com/public_html`
- Important structure:
  - `app` contains the Laravel project except the web public files.
  - `public_html` contains the contents of Laravel `public/` only.
  - `public_html/index.php` is edited to load `../app/vendor/autoload.php` and `../app/bootstrap/app.php`.
- Hostinger CLI PHP:
  - Default `php` may be PHP 8.2 and is not sufficient.
  - Use `/opt/alt/php84/usr/bin/php` for Artisan/Composer.
- Web PHP handler:
  - `public_html/.htaccess` should include PHP 8.4 handler on Hostinger.
- NPM:
  - NPM was not available on the Hostinger shared account during deployment.
  - Keep built frontend files in `public/build` committed to Git for deployment.

### Required Production `.env` Shape

Use real Hostinger database credentials and do not commit `.env`.

```env
APP_NAME="iHome Showroom"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://erp.ihome-store.com
APP_TIMEZONE=Africa/Cairo

APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ar_EG

DB_CONNECTION=mysql
DB_HOST=HOSTINGER_DB_HOST
DB_PORT=3306
DB_DATABASE=HOSTINGER_DB_NAME
DB_USERNAME=HOSTINGER_DB_USER
DB_PASSWORD=HOSTINGER_DB_PASSWORD
DB_BACKUP_MYSQLDUMP_BINARY=mysqldump
DB_BACKUP_MYSQL_BINARY=mysql

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
FILESYSTEM_DISK=local

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error
```

### Production `public_html/index.php` Path Requirements

The production `public_html/index.php` should point to the app folder:

```php
if (file_exists($maintenance = __DIR__.'/../app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../app/vendor/autoload.php';

$app = require_once __DIR__.'/../app/bootstrap/app.php';
```

### Production `public_html/.htaccess` Requirements

Keep Laravel rewrite rules and include `DirectoryIndex` plus PHP 8.4 handler:

```apache
DirectoryIndex index.php

<FilesMatch ".(php4|php5|php3|php2|php|phtml)$">
    SetHandler application/x-lsphp84
</FilesMatch>
```

### Exact GitHub Redeploy Commands On Hostinger

Run these on Hostinger SSH after pushing to GitHub:

```bash
cd /home/u470070883/domains/erp.ihome-store.com/app
git pull origin main
```

Copy public assets from the deployed repo into the actual web root:

```bash
cd /home/u470070883/domains/erp.ihome-store.com
rsync -a app/public/build public_html/
rsync -a app/public/images public_html/
rsync -a app/public/manifest.json app/public/offline.html app/public/robots.txt app/public/sw.js app/public/favicon.ico public_html/
```

Refresh Laravel caches with PHP 8.4:

```bash
cd /home/u470070883/domains/erp.ihome-store.com/app
/opt/alt/php84/usr/bin/php artisan config:clear
/opt/alt/php84/usr/bin/php artisan view:clear
/opt/alt/php84/usr/bin/php artisan cache:clear
/opt/alt/php84/usr/bin/php artisan config:cache
/opt/alt/php84/usr/bin/php artisan view:cache
```

Do not run route cache yet:

```bash
# Do not run this until closure routes are replaced:
# /opt/alt/php84/usr/bin/php artisan route:cache
```

Reason: `routes/web.php` currently contains closure routes for logout and print pages.

### Storage Link On Hostinger

The real browser-facing storage symlink should point from `public_html/storage` to app storage:

```bash
cd /home/u470070883/domains/erp.ihome-store.com
rm -f public_html/storage
ln -s /home/u470070883/domains/erp.ihome-store.com/app/storage/app/public public_html/storage
```

The standard `artisan about` storage-link check may still be misleading in this custom shared-hosting structure because Laravel expects `app/public/storage`; the actual web path is `public_html/storage`.

### Database Backup / Restore Requirements

- The in-app database backup feature is MySQL/MariaDB only.
- Server must have both `mysqldump` and `mysql` client binaries available.
- Binary override environment variables:
  - `DB_BACKUP_MYSQLDUMP_BINARY`
  - `DB_BACKUP_MYSQL_BINARY`
- If binaries are not on the default PATH, point those env values to absolute binary paths.
- Generated and uploaded database backups are stored on the Laravel `local` disk under:
  - `storage/app/database-backups`
- Generated backups are compressed as `.sql.gz`.
- Accepted restore/upload formats are:
  - `.sql`
  - `.gz` containing SQL dump content
- Restoring from the admin UI or Artisan command is destructive:
  - the app enters maintenance mode
  - current database contents are replaced from the selected dump
  - Laravel reconnects to the database
  - current migrations are re-run with `php artisan migrate --force`
  - caches are cleared with `php artisan optimize:clear`
- Backups are tracked in the `database_backups` table with metadata and restore audit fields.

### Permissions On Hostinger

```bash
cd /home/u470070883/domains/erp.ihome-store.com
chmod -R 775 app/storage app/bootstrap/cache
find public_html -type d -exec chmod 755 {} \;
find public_html -type f -exec chmod 644 {} \;
```

### Production Smoke Tests

Expected responses:

```bash
curl -I https://erp.ihome-store.com
curl -I https://erp.ihome-store.com/login
curl -I https://erp.ihome-store.com/build/manifest.json
```

- `/` should return `302` to `/login` for guests.
- `/login` should return `200`.
- `/build/manifest.json` should return `200`.
- `x-powered-by` should show PHP 8.4 if Hostinger exposes that header.

### Local Build / GitHub Build Asset Rule

Because Hostinger shared hosting did not have NPM:

```bash
cd /Users/hosam/Desktop/dev/ihome-System-new
npm run build
git add public/build
git commit -m "Track built frontend assets"
git push origin main
```

`public/build` is intentionally not ignored so Hostinger GitHub deployment can receive Vite assets.

### Local Database Dump For Production Import

Local database is Docker MySQL:

- Database: `ihome`.
- Username: `ihome`.
- Password: `secret`.
- Docker service: `mysql`.

Dump local DB from the local project folder:

```bash
cd /Users/hosam/Desktop/dev/ihome-System-new
docker compose exec -T mysql mysqldump --no-tablespaces -uihome -psecret ihome > ihome_local_export.sql
```

The `--no-tablespaces` flag is required because the MySQL user does not have `PROCESS` privilege.

Upload dump to Hostinger:

```bash
scp ihome_local_export.sql u470070883@erp.ihome-store.com:/home/u470070883/domains/erp.ihome-store.com/
```

Before importing on production, always back up the online DB:

```bash
cd /home/u470070883/domains/erp.ihome-store.com/app
grep '^DB_' .env
mysqldump -h HOSTINGER_DB_HOST -u HOSTINGER_DB_USER -p HOSTINGER_DB_NAME > /home/u470070883/domains/erp.ihome-store.com/online_backup_before_local_import.sql
```

Import local dump into Hostinger DB only after confirming the backup:

```bash
mysql -h HOSTINGER_DB_HOST -u HOSTINGER_DB_USER -p HOSTINGER_DB_NAME < /home/u470070883/domains/erp.ihome-store.com/ihome_local_export.sql
```

After import, clear/cache config and verify counts:

```bash
cd /home/u470070883/domains/erp.ihome-store.com/app
/opt/alt/php84/usr/bin/php artisan config:clear
/opt/alt/php84/usr/bin/php artisan cache:clear
/opt/alt/php84/usr/bin/php artisan view:clear
/opt/alt/php84/usr/bin/php artisan config:cache
/opt/alt/php84/usr/bin/php artisan view:cache
/opt/alt/php84/usr/bin/php artisan tinker --execute="echo 'Products: '.App\Models\Product::count().PHP_EOL; echo 'Customers: '.App\Models\Customer::count().PHP_EOL; echo 'Sales invoices: '.App\Models\SalesInvoice::count().PHP_EOL; echo 'Quotations: '.App\Models\Quotation::count().PHP_EOL;"
```

## Core Modules

### Dashboard

- Status: implemented.
- Component: `App\Livewire\Dashboard`.
- Shows sales/profit/quotation/stock KPI cards, recent invoices/quotations, top products/customers/partners, low stock products, and simple chart-style sections.
- Dashboard sales/profit metrics use confirmed sales invoices only for operational sales figures.
- Dashboard now distinguishes between:
  - gross sales profit
  - period expenses
  - net profit after expenses
- Expense deduction is period-based by `expenses.expense_date`, not allocated per invoice.

### Users / Roles

- Status: implemented.
- Components: `UserList`, `UserCreate`, `UserEdit`, `RoleList`.
- Model: `App\Models\User`.
- Roles: `admin`, `manager`, `sales`, `inventory`, `purchasing` in `App\Enums\UserRole`.
- Permissions are role-derived in `UserRole::permissions()`.
- Middleware: `CheckPermission`, `CheckRole`, `EnsureUserIsActive`.
- Seeder creates default users with password `password`; do not rely on that in production without changing passwords.
- Admin-only system operations such as database backup/restore should stay behind `role:admin` routes, not merely general settings permissions.
- Expenses module permission:
  - `expenses.manage`
  - currently granted to `admin`, `manager`, and `purchasing`

### Database Backups

- Status: implemented.
- Model: `App\Models\DatabaseBackup`.
- Service: `App\Support\DatabaseBackupManager`.
- Livewire admin page: `App\Livewire\Settings\DatabaseBackups\DatabaseBackupList`.
- Artisan commands:
  - `php artisan ihome:db-backup-create`
  - `php artisan ihome:db-backup-restore {backup}`
- Purpose:
  - create full database backups
  - download generated/uploaded backups
  - upload external SQL dumps
  - restore the full database from a selected backup
- Admin access only:
  - routes live under the admin role group
  - normal users must not see or use backup/restore actions
- Metadata tracked in `database_backups`:
  - file name
  - original file name
  - file path
  - file size
  - source type (`generated` or `uploaded`)
  - created by
  - restored at / restored by
  - optional notes
- Restore safety:
  - restore is destructive and requires explicit confirmation
  - UI confirmation word currently accepts `استعادة` or `RESTORE`
  - restore uses maintenance mode and current MySQL client tools
- This feature backs up the database only, not uploaded files or other filesystem assets.

### Categories

- Status: implemented.
- Livewire CRUD: `App\Livewire\Categories\CategoryList`.
- Soft delete / delete safety should be preserved where implemented.

### Suppliers

- Status: implemented.
- Livewire CRUD: `App\Livewire\Suppliers\SupplierList`.
- Used by purchase invoices and optionally products.
- Dedicated detail page: `App\Livewire\Suppliers\SupplierShow`.
- Supplier detail page shows supplier info, purchase invoice history, aggregate purchase totals/quantities, related products, and top purchased products from that supplier.

### Customers

- Status: implemented.
- Livewire CRUD: `App\Livewire\Customers\CustomerList`.
- Dedicated detail page: `App\Livewire\Customers\CustomerShow`.
- Sales invoices allow nullable customer for cash-customer behavior; quotations require a customer.
- Quotations and sales invoices support inline customer creation from the same form screen; the new customer is auto-selected after save.
- Customer list supports search, contact filters, sorting, mobile cards, and visible `created_at` / `updated_at` columns with sorting.
- Customer detail page shows customer info, sales invoice history, invoice/payment summary stats, and aggregated confirmed products received by the customer.

### Partners

- Status: implemented.
- Livewire CRUD: `App\Livewire\Partners\PartnerList`.
- Dedicated detail page: `App\Livewire\Partners\PartnerShow`.
- Partner fields include type, phone/contact data, default commission type/value, notes, active/inactive status.
- Partner commission is separate from customer discount and must remain separate.
- Partner detail page shows partner sales invoices, confirmed sales/collection summary, commission totals, invoice-level commission history, and top customers linked to that partner.

### Expense Categories

- Status: implemented.
- Livewire CRUD: `App\Livewire\Expenses\ExpenseCategoryList`.
- Model: `App\Models\ExpenseCategory`.
- Fields: `name`, `is_active`, `notes`.
- Soft deletes enabled.
- Category deletion is blocked while linked expenses exist.

### Expenses

- Status: implemented.
- Livewire CRUD: `App\Livewire\Expenses\ExpenseList`.
- Model: `App\Models\Expense`.
- Fields include category, title, amount, expense date, expense type, recurring frequency, payment status, paid amount, remaining amount, vendor/payee name, notes, creator, and optional `generated_from_expense_id`.
- Soft deletes enabled.
- Payment status is normalized automatically from `paid_amount` versus `amount`.
- Expenses are operational period records only; they do not affect stock, invoice totals, or invoice-level sales profit calculations.
- For dashboard and sales report profitability:
  - expenses are counted by `expense_date`
  - the system currently uses the full recorded `amount`
  - `payment_status` is tracked operationally but is not used to exclude expenses from net profit
- Recurring behavior is manual:
  - recurring expenses store frequency (`monthly`, `quarterly`, `yearly`)
  - the UI supports “generate next occurrence”
  - the new occurrence is linked through `generated_from_expense_id`
  - duplicate next-period generation from the same source expense is blocked

### Products

- Status: implemented.
- Components: `ProductList`, `ProductShow`.
- Model: `App\Models\Product`.
- Fields include name, internal SKU, barcode, image path/upload, category, optional supplier, sale price, current average cost, minimum stock alert, active flag, notes.
- Products use soft deletes.
- Product image is visible in product UI and can be shown in quotation/sales invoice print output based on template settings.
- Current stock is not directly edited on the product form; it is derived from stock movements.
- Product list supports search, filters, sorting, mobile cards, desktop table, and visible `created_at` / `updated_at` columns with sorting.

### Purchase Invoices

- Status: implemented.
- Components: `PurchaseInvoiceList`, `PurchaseInvoiceCreate`, `PurchaseInvoiceShow`.
- Models: `PurchaseInvoice`, `PurchaseInvoiceItem`.
- Statuses: draft, confirmed, cancelled.
- Draft purchase invoices do not affect stock.
- Confirmed purchase invoices create stock movement records and update product average cost.
- Confirmed/cancelled safety: current code cancels drafts only; confirmed purchase invoice cancellation is blocked with a message instructing use of a future safe workflow.
- List supports search, filters, sorting, mobile cards, desktop table, and visible `created_at` / `updated_at` columns with sorting.

### Stock Movements / Stock Visibility

- Status: implemented for purchase/sales movement tracking and stock reports.
- Models: `StockMovement`, `Product`.
- Stock is movement-based:
  - Product current stock is computed from `SUM(stock_movements.quantity)`.
  - `Product::withStockQuantity()` adds a stock subquery.
  - `Product::stockQuantitySubquerySql()` supports stock sorting/report expressions.
- Movement types include purchase in, sale out, adjustment in/out, return in/out.
- Current implemented sources:
  - Purchase invoice item.
  - Sales invoice item.
  - Sales return movement linked to invoice item through `source_type = stock_return`.
  - Legacy import stock adjustment.
- Stock summary and product movement history are implemented.
- Stock summary now supports controlled stock adjustment from the stock page for authorized users with `products.manage`:
  - user enters the target stock quantity
  - system calculates delta automatically
  - system records `adjustment_in` or `adjustment_out`
  - adjustment reason is required
  - this is still movement-based stock control, not silent direct quantity editing
- Stock report is implemented with search, filters, sorting, valuation by average cost and sale price, low stock count, zero/negative stock counts.
- Direct uncontrolled stock editing is not allowed.
- Stock adjustments/returns are represented in constants but there is no full operational adjustment/returns UI yet.

### Quotations

- Status: implemented.
- Components: `QuotationList`, `QuotationForm`, `QuotationShow`.
- Models: `Quotation`, `QuotationItem`.
- Statuses: draft, sent, approved, rejected, expired, converted.
- Quotations do not affect stock.
- Quotations are separate from sales invoices.
- Quotation items use persisted `sort_order` so manual row entry order is preserved across save, edit, show, print, and conversion.
- Quotation rows now support two row types:
  - `product`
  - `section`
- Section rows are quotation-only structural rows:
  - stored in the same ordered `quotation_items` stream
  - use `row_type = section`
  - store `section_title`
  - do not affect subtotal, discounts, or total
- Product rows can store an optional quotation-only `description`:
  - specific to that quotation row
  - shown on quotation show/print when present
  - not copied to sales invoice rows
- Quotation-to-sales-invoice conversion is implemented via `Quotation::convertToSalesInvoice()`.
- Conversion creates a draft sales invoice, copies customer, product rows only, prices, item discounts, invoice discount, notes, and installation fields, then marks quotation converted.
- Section rows are ignored during quotation-to-invoice conversion.
- Conversion does not confirm stock or create stock movements.
- Quotation list supports search, filters, sorting, mobile cards, desktop table, and visible `created_at` / `updated_at` columns with sorting.
- Quotation form has searchable customer/product selection integrated into the same selector UI.
- Quotation form supports creating a new customer inline without leaving the screen.
- Quotation form supports adding named section rows, moving rows up/down, and saving optional per-product quotation descriptions.
- Quotation create/edit cards that contain searchable dropdowns use visible card overflow so result lists are not clipped inside the card container.

### Sales Invoices

- Status: implemented.
- Components: `SalesInvoiceList`, `SalesInvoiceCreate`, `SalesInvoiceShow`.
- Models: `SalesInvoice`, `SalesInvoiceItem`, `SalesInvoicePayment`.
- Additional collection audit model: `SalesInvoiceRefund`.
- Statuses: draft, confirmed, cancelled, returned.
- Sales channels: direct and partner.
- Draft sales invoices do not affect stock and can be edited.
- Confirmed sales invoices reduce stock and create stock movement records.
- Sales invoice items use persisted `sort_order` so manual item entry order is preserved across save, edit, show, and print.
- Confirmation requires sufficient stock.
- `cost_at_sale_time` is frozen on confirmation using the product current average cost.
- Confirmed sales invoice editing is blocked.
- Draft cancellation is supported.
- Confirmed sales invoices are not cancelled directly; they now support a safe full return workflow:
  - full return is allowed only while the invoice is `confirmed`.
  - full return requires a reason and explicit confirmation from the UI.
  - status changes to `returned`.
  - `returned_at`, `returned_by`, and `return_reason` are stored for auditability.
  - stock is restored through `return_in` stock movements.
  - if collected payments exist, the system records a full refund entry before completing the return.
  - refund records are stored separately from payment records in `sales_invoice_refunds`.
  - invoice payment summary uses net collected amount = payments - refunds.
  - current implementation is full-invoice return only, not partial line-item return.
- Payment collection is invoice-based, not invoice-splitting:
  - one sale stays one sales invoice.
  - multiple payment records can be attached to the same invoice.
  - invoice payment status is tracked as unpaid, partially paid, or paid.
  - invoice stores `paid_amount`, `remaining_amount`, and optional `due_date`.
  - payments are allowed on confirmed invoices only.
  - payments do not affect stock, quotation conversion, installation logic, or partner commission formulas.
- Sales invoice list now supports constrained bulk actions on the currently visible page:
  - bulk sync payment summary recalculates `paid_amount`, `remaining_amount`, and `payment_status` from recorded payments/refunds without creating new records.
  - bulk mark as paid creates one balancing payment record per selected confirmed invoice for its full remaining amount; it does not directly flip `payment_status`.
  - bulk invoice status changes are limited to safe existing transitions only: confirm selected drafts or cancel selected drafts.
  - destructive/financial bulk actions require typed confirmation (`تنفيذ`) in the UI.
- Payment receipt printing is implemented per payment record.
- List supports search, filters, sorting, mobile cards, desktop table, visible payment status, and visible `created_at` / `updated_at` columns with sorting.
- Sales invoice form has searchable customer/product selection integrated into the same selector UI.
- Sales invoice form supports creating a new customer inline without leaving the screen; cash customer flow is still allowed by leaving the customer empty.
- Sales invoice create/edit cards that contain searchable dropdowns use visible card overflow so customer/product result lists can expand fully.

### Installation / Service Charges

- Status: implemented as dedicated quotation/sales invoice fields.
- Installation is not a product and must not affect stock.
- Installation can be disabled, fixed amount, or percentage of product subtotal.
- Quotation installation fields:
  - enabled, pricing mode, percentage value, fixed amount, total, notes.
- Sales invoice installation fields:
  - enabled, pricing mode, percentage value, fixed amount, total.
  - party type, party reference, payout amount, installation profit, product profit, notes.
- Installation appears in print as a final item-like row in the product list; the display name is configurable per print template.
- No separate installation provider module exists yet; party type/reference is simple text/enum-style data.

### Print Templates / Printing

- Status: implemented for quotation and sales invoice; partner settlement print is implemented separately and is not fully template-driven.
- Models/support:
  - `App\Models\PrintTemplate`.
  - `App\Support\PrintTemplateSettings`.
- Admin UI:
  - `PrintTemplateList`.
  - `PrintTemplateForm`.
- Multiple templates are supported for quotations and sales invoices.
- Default template per document type is supported.
- Template settings are database-backed:
  - `print_templates.settings` JSON.
  - global print settings in `settings` table using `print.*` keys.
- Template selection at print time is supported through a toolbar dropdown on print pages.
- Supported per-template settings include:
  - company/showroom identity.
  - logo, header image, footer image uploads.
  - content visibility for customer info, document number/date/status, creator, item discounts, invoice discount, subtotal/total, product images, notes.
  - layout options: A4, header alignment, spacing, font size, table density, logo size, margin.
  - warranty terms second page (`شروط الضمان`) with enabled flag, title, body, footer.
  - configurable installation item name for quotation and sales invoice prints.
- Shared print layout: `resources/views/print/layout.blade.php`.
- Document-specific print views:
  - `resources/views/quotations/print.blade.php`.
  - `resources/views/sales-invoices/print.blade.php`.
- Partner settlement print:
  - `resources/views/sales-invoices/partner-settlement-print.blade.php`.
  - Only available for partner sales.
  - Shows partner commission/internal settlement values, separate from customer-facing invoice.

### Reports

- Status: implemented.
- Sales report:
  - `App\Livewire\Reports\SalesReport`.
  - Month/date-range filtering.
  - Confirmed sales invoices only.
  - Gross sales, confirmed invoice count, partner commissions, net revenue, gross sales profit, period expenses, net profit after expenses, average invoice value, top products/customers/partners, channel breakdown.
  - Expense deduction uses `expenses.expense_date` within the selected report range.
- Stock report:
  - `App\Livewire\Reports\StockReport`.
  - Search/filters/sorting.
  - Movement-based current stock.
  - Valuation by average cost and sale price.
  - Low/zero/negative stock counts.

### ERPNext Migration / Import

- Status: implemented as a dedicated command; actual data import depends on running it or importing a DB dump.
- Command: `php artisan ihome:import-erpnext`.
- Options:
  - `--path=` folder containing cleaned ERPNext CSV files.
  - `--dry-run`.
  - `--skip-stock`.
- Tracking table: `legacy_import_records`.
- Source label: `erpnext_cleaned_v2`.
- Expected cleaned CSV files include categories, suppliers, customers, partners, products, item prices, purchase invoices/items, quotations/items, sales invoices/items, sales returns/items, stock snapshot.
- Import order in command:
  - item prices loaded first for product enrichment.
  - categories, suppliers, customers, partners, products.
  - purchase invoices/items.
  - quotations/items.
  - sales invoices/items.
  - sales returns are deferred.
  - stock movements from confirmed purchase/sales documents.
  - stock snapshot reconciliation through adjustment movements.
- Sales returns are still deferred in the legacy ERP import command because legacy return rows are not yet mapped into the current in-app return workflow.
- Stock snapshot is treated carefully via movement/reconciliation logic, not direct uncontrolled product stock overwrites.

### PWA

- Status: basic support implemented.
- Files:
  - `public/manifest.json`.
  - `public/sw.js`.
  - `public/offline.html`.
  - PWA icons in `public/images`.
- Scope limitation: no advanced offline transaction sync. Do not implement offline quotation/invoice writes without a dedicated design.

## Core Business Rules

- Arabic is the primary UI language.
- RTL is the default layout direction.
- EGP is the default currency; use `App\Support\Money::format()` for monetary display.
- Stock is movement-based and must not be directly edited in uncontrolled ways.
- Stock can be corrected from the stock summary page only through a recorded adjustment movement with a required reason and authorized permission.
- Purchase invoice confirmation increases stock and updates product average cost.
- Sales invoice confirmation decreases stock and creates stock movements.
- Draft purchase/sales invoices do not affect stock.
- Confirmed invoice cancellation must not silently corrupt stock; direct cancellation remains blocked.
- Confirmed sales invoices can instead be fully returned through the dedicated return flow, which restores stock with explicit return movements and audit metadata.
- Quotations are separate from sales invoices.
- Quotations do not affect stock.
- Quotations can be converted to draft sales invoices; the sales invoice still follows normal confirmation/stock logic.
- Quotation section rows are structural only and never affect totals or stock.
- Only quotation product rows affect quotation pricing, discounts, and total.
- Quotation-only item descriptions do not affect pricing and do not flow into sales invoice items.
- Item-level discounts apply to product rows.
- Invoice-level discounts apply to product totals only.
- Installation is separate from products and does not affect stock.
- Invoice-level discount must not reduce installation amount.
- Installation can be fixed or percentage of product subtotal.
- Installation revenue, payout/cost, and profit are tracked separately on sales invoices.
- Partner commission is not a customer discount.
- Customer invoice and partner settlement are separate outputs.
- Partner commission currently calculates from gross total in `SalesInvoice::confirm()` when sales channel is partner.
- Payments are collections against the final customer invoice total only:
  - they do not create extra invoices.
  - they do not change invoice totals.
  - they only update paid/remaining/payment-status fields and create payment history records.
- Refunds are tracked separately from collections:
  - they do not change invoice totals.
  - they reduce net collected amount for payment-summary purposes.
  - they are currently created automatically as part of full returned invoices when collected payments already exist.
- Expenses are operational business records:
  - they are not stock items
  - they do not affect stock
  - they do not affect invoice totals
  - they reduce only period-level net profit reporting
- Payment collection is currently allowed on confirmed sales invoices only; draft/cancelled invoices do not accept payments.
- Database backup and restore does not change invoice business rules, but a full restore replaces the stored state of invoices, payments, stock, and all other database-backed records.
- Mobile usability matters for quotation/invoice entry and all list/report pages.

## Financial / Calculation Rules

### Shared Discount Logic

- Discount types: fixed and percentage.
- `discountAmount(base, type, value)` caps percentage at 100 and caps discount at base amount.
- Product line total:
  - gross line = quantity * unit price.
  - item discount is applied to that line.
  - line total = max(gross - item discount, 0).

### Quotation Totals

- `subtotal` is the sum of quotation product-row line totals only.
- Invoice discount applies to products subtotal only.
- Net products total = `subtotal - invoice_discount_amount`.
- Quotation section rows contribute `0` to all pricing calculations.
- Installation total:
  - 0 if disabled.
  - fixed amount if fixed.
  - percentage of products subtotal if percentage.
- Quotation total = net products total + installation total.
- Quotations do not calculate profit and do not affect stock.

### Sales Invoice Totals and Profit

- Draft invoices store totals from form calculations; confirmation recalculates stock/cost/profit-critical values.
- On confirmation:
  - Each item cost is frozen from product `current_average_cost`.
  - Each item line profit = line total - line cost.
  - Product subtotal = sum item line totals.
  - Invoice-level discount applies to products subtotal only.
  - Net products total = product subtotal - invoice discount amount.
  - Installation total = fixed amount or percentage of product subtotal.
  - Gross total = net products total + installation total.
  - Partner commission amount = commission on gross total for partner-channel invoices.
  - Net revenue after partner commission = gross total - partner commission amount.
  - Total product cost = sum frozen line costs.
  - Product profit = net products total - total product cost.
- Installation profit = installation total - installation payout amount.
- Total invoice profit = product profit + installation profit - partner commission amount.
- Do not change these formulas without explicitly reviewing dashboard/report/print impact.

### Sales Invoice Payment Summary

- The sales invoice keeps the full final `gross_total`.
- Payments are stored separately in `sales_invoice_payments`.
- Payment summary rules:
  - if `paid_amount = 0` => `unpaid`.
  - if `paid_amount > 0` and `< gross_total` => `partially_paid`.
  - if `paid_amount >= gross_total` => `paid`.
- `remaining_amount = max(gross_total - paid_amount, 0)`.
- Overpayment is blocked.
- Payment amount must be positive.
- Each payment stores its own `remaining_amount_after` for receipt/history accuracy.
- Payments do not change `gross_total`, `partner_commission_amount`, `net_revenue_after_partner_commission`, `total_cost`, or `total_profit`.
- Refund records reduce net paid amount for payment summary purposes.
- Legacy confirmed invoices created before payment-summary fields were backfilled may carry stale `remaining_amount` / `payment_status` values; a normalization migration exists and invoice show/print flows also self-heal stale summaries by recalculating from recorded payments and `gross_total`.

### Expense Impact On Profit Reporting

- Gross sales profit remains the existing sales-operation profit already calculated from confirmed sales invoices.
- Period expenses are calculated from `expenses.amount` for rows whose `expense_date` falls inside the selected period.
- Current chosen rule:
  - include all recorded expenses by date, regardless of expense `payment_status`
  - this is intentional for operational profitability, not cash-flow accounting
- Net profit for dashboard and sales report:
  - `net profit = gross sales profit - period expenses`
- Expenses are not allocated to invoice rows, products, installation lines, or partners.
- Do not change sales invoice profit formulas when working on expenses/reporting unless explicitly requested.

### Purchase Average Cost

- On purchase invoice confirmation:
  - New quantity = current quantity + purchased quantity.
  - New average cost = ((current quantity * current average cost) + (purchased quantity * unit cost)) / new quantity.
  - Product `current_average_cost` is updated.
  - Stock movement is created with quantity in and balance after.

## Printing / Document Rules

- Quotation and sales invoice print output is Arabic/RTL and browser-print/PDF oriented.
- A shared print layout is used with document-specific content sections.
- Print templates control content visibility and layout style.
- Template selector is available on print pages when active templates exist.
- Quotation print supports section headers inside the item table.
- Quotation print shows quotation item descriptions under the product name only when a description exists.
- Sales invoice print now shows payment status, paid amount, remaining amount, and due date when available.
- Printable payment receipt (`إيصال استلام`) exists per recorded payment and reuses the shared print layout with invoice/company branding.
- Product images can be shown/hidden per template.
- Installation is printed as the last item-like row in the product list when enabled and shown by template settings.
- Installation row name is configurable per template.
- Item discount column and invoice-level discount summary are independently configurable per template.
- Warranty terms second page (`شروط الضمان`) is configurable per template and prints as page 2 when enabled.
- Partner settlement print must remain separate from customer-facing invoice and should not expose partner commission on the customer invoice unless explicitly required by a future business rule.

## Current Implementation Status

### Implemented

- Authentication and active-user check.
- Role/permission-based access.
- Arabic-first UI and RTL layouts.
- EGP money formatting helper.
- Dashboard.
- Categories, suppliers, customers, partners CRUD plus read-only detail pages with summary stats and linked invoice/product/commission data.
- Expense categories and expenses CRUD with manual recurring occurrence generation.
- Products CRUD with images and stock visibility.
- Purchase invoices with confirmation stock increase and average cost update.
- Movement-based stock model, summary, movement history, and stock report.
- Quotations with discounts, installation, edit, print, and conversion to draft sales invoice.
- Quotations also support section/group rows and quotation-only per-item descriptions.
- Sales invoices with direct/partner channel, discounts, installation, partner commission, draft editing, confirmation stock decrease, full confirmed-invoice return, profit calculation, payment collection tracking, print, payment receipt print, and partner settlement print.
- Multiple print templates for quotations and sales invoices.
- Print template branding image uploads and warranty terms page.
- Sales and stock reports.
- Dashboard and sales report now distinguish gross sales profit from net profit after expenses.
- ERPNext cleaned CSV import command with dry-run and import logging.
- Admin-only database backup creation, download, upload, and full restore using tracked backup metadata.
- Basic PWA files.
- Sortable list/report tables for products, purchase invoices, sales invoices, quotations, stock summary, movement history, and stock report.
- Built frontend assets committed under `public/build` for shared hosting deployment.
- Product, quotation, sales invoice, purchase invoice, stock summary, and report pages use a single white filter toolbar panel with a proper search icon, stable desktop search width, and a separate responsive filters row where applicable.

### Partial / Future-Friendly

- Partner settlement template type exists in `PrintTemplate`, but partner settlement print is not fully template-driven.
- Stock adjustment UI exists from the stock summary page and writes `adjustment_in` / `adjustment_out` movements, but there is not yet an approval or dual-control layer around those manual adjustments.
- Sales invoice return currently supports full-invoice reversal only; partial line-item returns/credit-note style flows are not implemented.
- Installation provider tracking is simple fields only; no separate technician/employee/company provider module exists.
- Payment data is report-ready, but there is not yet a dedicated collections/receivables report screen.
- Expenses are practical operational tracking only; there is no approval workflow, attachment storage, vendor ledger, or accounting journal integration.
- PWA is installable/basic shell-oriented only; offline transaction sync is not implemented.
- Deployment to Hostinger shared hosting works with PHP 8.4 and copied/served `public_html`, but this is environment-specific and should be verified during deployment.

### Pending / Not Implemented

- Partial sales return workflow.
- Supplier return workflow.
- Supplier-side or standalone return document workflow.
- Stock adjustment approval/audit controls beyond the current direct adjustment UI.
- Expense approval workflow, file attachments, and cash-flow/accounting reconciliation.
- Advanced PDF generation engine; current print flow is browser print.
- Full multilingual language switcher; Arabic is primary and English can be added later.
- Advanced offline/PWA transaction sync.
- Dedicated installation service provider module.

## Project Conventions / Development Rules

- Keep architecture simple and production-safe.
- Use Laravel/Livewire/Blade patterns already present in the codebase.
- Do not re-scaffold auth, layouts, or completed modules.
- Do not redesign unrelated modules while implementing a targeted change.
- Do not mutate stock directly; use stock movements or a safe dedicated workflow.
- If the user asks to “edit stock”, implement it as a stock adjustment workflow that creates `StockMovement` records; do not write silent stock quantities onto products.
- Do not make expenses affect stock, invoice totals, installation totals, or partner commission formulas.
- Expense reporting is period-level only; do not allocate expenses artificially across invoice rows unless the business rule explicitly changes.
- Do not treat installation as a product.
- Do not apply invoice-level discount to installation.
- Do not treat partner commission as a customer discount.
- Preserve customer invoice and partner settlement as separate outputs.
- Preserve Arabic-first, RTL-first UX.
- Keep forms and tables mobile-friendly.
- Keep list and report filter/search toolbars responsive; search inputs should keep a usable width on desktop, use consistent control sizing, and stack cleanly on smaller screens.
- Keep create/edit flows self-contained where practical; avoid forcing users to leave quotation/invoice screens for common master-data actions like adding a customer.
- The shared `x-card` component clips overflow by default; enable visible overflow for forms that contain absolute-positioned search dropdowns.
- Keep print output clean, RTL, and browser-print friendly.
- Use whitelisted sorting fields for Livewire table sorting.
- Avoid route caching until closure routes are replaced with controllers.
- For Hostinger shared hosting, keep compiled Vite assets available because NPM may not be available server-side.
- For the database backup feature, keep `mysqldump` and `mysql` client binaries available on the target server and configure `DB_BACKUP_MYSQLDUMP_BINARY` / `DB_BACKUP_MYSQL_BINARY` if PATH discovery is not reliable.

## How Future Tools Should Work On This Project

Use this checklist for each future task:

1. Read `context.md`.
2. Inspect the current relevant code before editing.
3. Summarize the current implementation state for the requested area.
4. Explain the planned change before coding when the task is substantial.
5. Make focused, minimal changes.
6. Preserve existing business rules, especially stock, discount, installation, profit, and partner commission logic.
7. Run relevant tests or explain why tests could not be run.
8. Update `context.md` with any changed status, rules, files, limitations, or next priorities.
9. Report assumptions and limitations clearly.

## Open Items / Next Priorities

- Import or synchronize local migrated data into production database safely, with backups.
- Extend the current full-invoice return flow into partial/line-item sales returns before importing legacy sales return rows.
- Add safe stock adjustment workflow if manual stock corrections are needed.
- Consider replacing closure routes with controllers so `php artisan route:cache` can be used in production.
- Consider making partner settlement print template-driven using the existing print template pattern.
- Improve deployment documentation for Hostinger shared hosting, including PHP 8.4, `public_html` structure, and committed `public/build` assets.
- Add focused tests for table sorting if sorting behavior becomes business-critical.
