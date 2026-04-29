<?php

namespace App\Livewire\SalesInvoices;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\SalesInvoice;
use App\Models\SalesInvoicePayment;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class SalesInvoiceList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $channelFilter = '';
    public string $partnerFilter = '';
    public string $sortField = 'invoice_date';
    public string $sortDirection = 'desc';
    public array $selectedInvoices = [];
    public array $pageInvoiceIds = [];
    public bool $selectPage = false;
    public string $bulkAction = '';
    public string $bulkPaymentDate = '';
    public string $bulkPaymentMethod = 'cash';
    public string $bulkReferenceNumber = '';
    public string $bulkNotes = '';
    public string $bulkConfirmation = '';

    public function mount(): void
    {
        $this->bulkPaymentDate = now()->toDateString();
    }

    public function updatingSearch(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingPartnerFilter(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingSortField(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->clearSelection();
        $this->resetPage();
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selectedInvoices = $value ? $this->pageInvoiceIds : [];
    }

    public function updatedSelectedInvoices(): void
    {
        $this->selectedInvoices = $this->normalizedSelectedInvoiceIds();
        $this->selectPage = ! empty($this->pageInvoiceIds)
            && empty(array_diff($this->pageInvoiceIds, $this->selectedInvoices));
    }

    public function sortBy(string $field): void
    {
        if (! array_key_exists($field, $this->sortableFields())) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function clearSelection(): void
    {
        $this->selectedInvoices = [];
        $this->pageInvoiceIds = [];
        $this->selectPage = false;
        $this->bulkAction = '';
        $this->bulkConfirmation = '';
        $this->resetValidation();
    }

    public function performBulkAction(): void
    {
        $selectedIds = $this->normalizedSelectedInvoiceIds();

        if ($selectedIds === []) {
            session()->flash('error', 'اختر فاتورة بيع واحدة على الأقل قبل تنفيذ الإجراء الجماعي.');

            return;
        }

        if (! array_key_exists($this->bulkAction, $this->bulkActions())) {
            session()->flash('error', 'اختر إجراءً جماعيًا صالحًا أولًا.');

            return;
        }

        $rules = $this->bulkValidationRules();

        if ($rules !== []) {
            $this->validate($rules, [], $this->bulkValidationAttributes());
        }

        if ($this->requiresBulkConfirmation() && trim($this->bulkConfirmation) !== 'تنفيذ') {
            $this->addError('bulkConfirmation', 'اكتب كلمة "تنفيذ" لتأكيد الإجراء الجماعي.');

            return;
        }

        $processed = 0;
        $skipped = 0;
        $errorMessages = [];

        $invoices = SalesInvoice::query()
            ->whereKey($selectedIds)
            ->get();

        foreach ($invoices as $invoice) {
            try {
                match ($this->bulkAction) {
                    'sync_payment_summary' => $this->bulkSyncPaymentSummary($invoice),
                    'mark_as_paid' => $this->bulkMarkInvoiceAsPaid($invoice),
                    'confirm_drafts' => $this->bulkConfirmDraftInvoice($invoice),
                    'cancel_drafts' => $this->bulkCancelDraftInvoice($invoice),
                    default => null,
                };

                $processed++;
            } catch (ValidationException $exception) {
                $skipped++;
                $errorMessages[] = collect($exception->errors())->flatten()->first() ?: 'تعذر تنفيذ الإجراء على إحدى الفواتير المحددة.';
            }
        }

        $message = $processed > 0
            ? 'تم تنفيذ الإجراء على ' . $processed . ' فاتورة.'
            : 'لم يتم تنفيذ الإجراء على أي فاتورة.';

        if ($skipped > 0) {
            $message .= ' تم تجاوز ' . $skipped . ' فاتورة. ' . collect($errorMessages)->filter()->unique()->take(2)->implode(' ');
        }

        session()->flash($processed > 0 ? 'success' : 'error', trim($message));

        $this->clearSelection();
    }

    public function render()
    {
        $invoices = $this->baseQuery();

        $this->applySorting($invoices);

        $invoices = $invoices->paginate(15);
        $this->pageInvoiceIds = $invoices->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->selectedInvoices = array_values(array_intersect($this->selectedInvoices, $this->pageInvoiceIds));
        $this->selectPage = ! empty($this->pageInvoiceIds)
            && empty(array_diff($this->pageInvoiceIds, $this->selectedInvoices));

        return view('livewire.sales-invoices.sales-invoice-list', [
            'invoices' => $invoices,
            'statuses' => SalesInvoiceStatus::cases(),
            'channels' => SalesChannel::cases(),
            'partners' => Partner::query()->orderBy('name')->get(),
            'sortableFields' => $this->sortableFields(),
            'bulkActions' => $this->bulkActions(),
            'paymentMethods' => SalesInvoicePayment::methods(),
        ])->layout('layouts.app', ['header' => 'فواتير البيع']);
    }

    private function baseQuery()
    {
        return SalesInvoice::query()
            ->with(['customer', 'partner'])
            ->withCount('items')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('invoice_number', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('phone', 'like', "%{$this->search}%"))
                        ->orWhereHas('partner', fn ($query) => $query->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->channelFilter, fn ($query) => $query->where('sales_channel', $this->channelFilter))
            ->when($this->partnerFilter, fn ($query) => $query->where('partner_id', $this->partnerFilter));
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'invoice_number' => $query->orderBy('invoice_number', $direction),
            'customer' => $query->orderBy(Customer::query()->select('name')->whereColumn('customers.id', 'sales_invoices.customer_id'), $direction),
            'channel' => $query->orderBy('sales_channel', $direction),
            'partner' => $query->orderBy(Partner::query()->select('name')->whereColumn('partners.id', 'sales_invoices.partner_id'), $direction),
            'status' => $query->orderBy('status', $direction),
            'payment_status' => $query->orderBy('payment_status', $direction),
            'items_count' => $query->orderBy('items_count', $direction),
            'gross_total' => $query->orderBy('gross_total', $direction),
            'created_at' => $query->orderBy('created_at', $direction),
            'updated_at' => $query->orderBy('updated_at', $direction),
            default => $query->orderBy('invoice_date', $direction),
        };

        $query->orderBy('id', $direction);
    }

    private function sortableFields(): array
    {
        return [
            'invoice_date' => 'التاريخ',
            'invoice_number' => 'رقم الفاتورة',
            'customer' => 'العميل',
            'channel' => 'القناة',
            'partner' => 'الشريك',
            'status' => 'الحالة',
            'payment_status' => 'حالة السداد',
            'items_count' => 'البنود',
            'gross_total' => 'إجمالي العميل',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ];
    }

    private function bulkActions(): array
    {
        return [
            'sync_payment_summary' => 'مزامنة حالات السداد المحددة',
            'mark_as_paid' => 'تسديد المحدد بالكامل',
            'confirm_drafts' => 'تأكيد المسودات المحددة',
            'cancel_drafts' => 'إلغاء المسودات المحددة',
        ];
    }

    private function bulkValidationRules(): array
    {
        $rules = [];

        if ($this->bulkAction === 'mark_as_paid') {
            $rules['bulkPaymentDate'] = ['required', 'date'];
            $rules['bulkPaymentMethod'] = ['required', 'in:' . implode(',', array_keys(SalesInvoicePayment::methods()))];
            $rules['bulkReferenceNumber'] = ['nullable', 'string', 'max:255'];
            $rules['bulkNotes'] = ['nullable', 'string', 'max:2000'];
        }

        if ($this->requiresBulkConfirmation()) {
            $rules['bulkConfirmation'] = ['required', 'string'];
        }

        return $rules;
    }

    private function bulkValidationAttributes(): array
    {
        return [
            'bulkPaymentDate' => 'تاريخ التحصيل',
            'bulkPaymentMethod' => 'طريقة التحصيل',
            'bulkReferenceNumber' => 'المرجع',
            'bulkNotes' => 'الملاحظات',
            'bulkConfirmation' => 'تأكيد التنفيذ',
        ];
    }

    private function requiresBulkConfirmation(): bool
    {
        return in_array($this->bulkAction, ['mark_as_paid', 'confirm_drafts', 'cancel_drafts'], true);
    }

    private function normalizedSelectedInvoiceIds(): array
    {
        return collect($this->selectedInvoices)
            ->map(fn ($id) => (string) $id)
            ->filter(fn ($id) => $id !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function bulkSyncPaymentSummary(SalesInvoice $invoice): void
    {
        $invoice->syncPaymentSummaryIfNeeded();
    }

    private function bulkMarkInvoiceAsPaid(SalesInvoice $invoice): void
    {
        $invoice->refresh();
        $invoice->syncPaymentSummaryIfNeeded();
        $invoice->refresh();

        if ($invoice->status !== SalesInvoiceStatus::Confirmed) {
            throw ValidationException::withMessages([
                'bulkAction' => 'يمكن تنفيذ التسديد الجماعي فقط على فواتير البيع المؤكدة.',
            ]);
        }

        if (round((float) $invoice->remaining_amount, 2) <= 0) {
            throw ValidationException::withMessages([
                'bulkAction' => 'إحدى الفواتير المحددة لا تحتوي على رصيد متبقٍ للتحصيل.',
            ]);
        }

        $invoice->recordPayment([
            'payment_date' => $this->bulkPaymentDate,
            'amount' => (float) $invoice->remaining_amount,
            'payment_method' => $this->bulkPaymentMethod,
            'reference_number' => $this->bulkReferenceNumber,
            'notes' => $this->bulkNotes !== ''
                ? $this->bulkNotes
                : 'تسوية تحصيل جماعية من شاشة فواتير البيع.',
        ], auth()->user());
    }

    private function bulkConfirmDraftInvoice(SalesInvoice $invoice): void
    {
        $invoice->confirm(auth()->user());
    }

    private function bulkCancelDraftInvoice(SalesInvoice $invoice): void
    {
        $invoice->cancelDraft();
    }
}
