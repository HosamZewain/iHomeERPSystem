<?php

namespace App\Livewire\SalesInvoices;

use App\Models\SalesInvoicePayment;
use App\Models\SalesInvoice;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SalesInvoiceShow extends Component
{
    public SalesInvoice $invoice;
    public bool $showReturnForm = false;
    public string $return_reason = '';
    public string $return_confirmation = '';
    public string $refund_date = '';
    public string $refund_method = 'cash';
    public string $refund_reference_number = '';
    public string $refund_notes = '';
    public string $payment_date = '';
    public string $payment_amount = '';
    public string $payment_method = 'cash';
    public string $reference_number = '';
    public string $payment_notes = '';

    public function mount(SalesInvoice $salesInvoice): void
    {
        $this->invoice = $salesInvoice;
        $this->invoice->syncPaymentSummaryIfNeeded();
        $this->invoice->refresh();
        $this->resetPaymentForm();
    }

    public function confirm(): void
    {
        try {
            $this->invoice->confirm(auth()->user());
            session()->flash('success', 'تم تأكيد فاتورة البيع. تم خصم المخزون وتثبيت تكلفة البيع والربح.');
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }

        $this->refreshInvoice();
    }

    public function cancelDraft(): void
    {
        try {
            $this->invoice->cancelDraft();
            session()->flash('success', 'تم إلغاء مسودة فاتورة البيع.');
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }

        $this->refreshInvoice();
    }

    public function startReturn(): void
    {
        $this->showReturnForm = true;
        $this->return_reason = '';
        $this->return_confirmation = '';
        $this->refund_date = now()->toDateString();
        $this->refund_method = 'cash';
        $this->refund_reference_number = '';
        $this->refund_notes = '';
        $this->resetValidation(['return_reason', 'return_confirmation', 'refund_date', 'refund_method', 'refund_reference_number', 'refund_notes']);
    }

    public function cancelReturn(): void
    {
        $this->showReturnForm = false;
        $this->return_reason = '';
        $this->return_confirmation = '';
        $this->refund_date = '';
        $this->refund_method = 'cash';
        $this->refund_reference_number = '';
        $this->refund_notes = '';
        $this->resetValidation(['return_reason', 'return_confirmation', 'refund_date', 'refund_method', 'refund_reference_number', 'refund_notes']);
    }

    public function reverseConfirmed(): void
    {
        $data = $this->validate([
            'return_reason' => ['required', 'string', 'max:2000'],
            'return_confirmation' => ['required', 'string'],
            'refund_date' => ['nullable', 'date'],
            'refund_method' => ['nullable', 'in:'.implode(',', array_keys(SalesInvoicePayment::methods()))],
            'refund_reference_number' => ['nullable', 'string', 'max:255'],
            'refund_notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'return_reason' => 'سبب المرتجع',
            'return_confirmation' => 'تأكيد المرتجع',
            'refund_date' => 'تاريخ الاسترداد',
            'refund_method' => 'طريقة الاسترداد',
            'refund_reference_number' => 'مرجع الاسترداد',
            'refund_notes' => 'ملاحظات الاسترداد',
        ]);

        if (! in_array(trim($data['return_confirmation']), ['مرتجع', 'RETURN'], true)) {
            $this->addError('return_confirmation', 'اكتب كلمة "مرتجع" لتأكيد تنفيذ المرتجع الكامل.');

            return;
        }

        try {
            $this->invoice->reverseConfirmed(trim($data['return_reason']), auth()->user(), [
                'refund_date' => $data['refund_date'] ?? null,
                'refund_method' => $data['refund_method'] ?? null,
                'refund_reference_number' => $data['refund_reference_number'] ?? null,
                'refund_notes' => $data['refund_notes'] ?? null,
            ]);
            session()->flash('success', 'تم تنفيذ مرتجع الفاتورة وإرجاع الكميات إلى المخزون.');
            $this->cancelReturn();
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }

        $this->refreshInvoice();
    }

    public function fillRemainingAmount(): void
    {
        $this->payment_amount = (string) $this->invoice->remaining_amount;
    }

    public function savePayment(): void
    {
        $data = $this->validate([
            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(SalesInvoicePayment::methods()))],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'payment_date' => 'تاريخ الدفعة',
            'payment_amount' => 'قيمة الدفعة',
            'payment_method' => 'طريقة الدفع',
            'reference_number' => 'المرجع',
            'payment_notes' => 'ملاحظات الدفعة',
        ]);

        try {
            $this->invoice->recordPayment([
                'payment_date' => $data['payment_date'],
                'amount' => $data['payment_amount'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['payment_notes'] ?? null,
            ], auth()->user());
            session()->flash('success', 'تم تسجيل الدفعة وتحديث حالة السداد.');
            $this->resetPaymentForm();
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }

        $this->refreshInvoice();
    }

    private function refreshInvoice(): void
    {
        $this->invoice->refresh()->load(['customer', 'partner', 'items.product', 'payments.creator', 'payments.receiver', 'refunds.creator', 'returner']);
    }

    private function resetPaymentForm(): void
    {
        $this->payment_date = now()->toDateString();
        $this->payment_amount = '';
        $this->payment_method = 'cash';
        $this->reference_number = '';
        $this->payment_notes = '';
        $this->resetValidation([
            'payment_date',
            'payment_amount',
            'payment_method',
            'reference_number',
            'payment_notes',
        ]);
    }

    public function render()
    {
        $this->invoice->syncPaymentSummaryIfNeeded();
        $this->invoice->refresh();
        $this->invoice->load(['customer', 'partner', 'quotation', 'items.product', 'creator', 'confirmer', 'returner', 'payments.creator', 'payments.receiver', 'refunds.creator']);
        $itemIds = $this->invoice->items->pluck('id');

        $stockMovements = StockMovement::query()
            ->with(['product', 'creator'])
            ->whereIn('source_type', [StockMovement::SOURCE_SALES_ITEM, StockMovement::SOURCE_RETURN])
            ->whereIn('source_id', $itemIds)
            ->orderBy('id')
            ->get();

        return view('livewire.sales-invoices.sales-invoice-show', [
            'stockMovements' => $stockMovements,
            'paymentMethods' => SalesInvoicePayment::methods(),
            'refundMethods' => SalesInvoicePayment::methods(),
            'showProfit' => auth()->user()->hasPermission('sales.view_profit'),
        ])->layout('layouts.app', ['header' => 'فاتورة بيع: ' . $this->invoice->invoice_number]);
    }
}
