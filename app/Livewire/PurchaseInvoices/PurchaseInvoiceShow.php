<?php

namespace App\Livewire\PurchaseInvoices;

use App\Models\PurchaseInvoice;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PurchaseInvoiceShow extends Component
{
    public PurchaseInvoice $invoice;

    public function mount(PurchaseInvoice $purchaseInvoice): void
    {
        $this->invoice = $purchaseInvoice;
    }

    public function confirm(): void
    {
        try {
            $this->invoice->confirm(auth()->user());
            session()->flash('success', 'تم تأكيد فاتورة الشراء. تم زيادة المخزون وتحديث متوسط التكلفة.');
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }

        $this->refreshInvoice();
    }

    public function cancelDraft(): void
    {
        try {
            $this->invoice->cancelDraft();
            session()->flash('success', 'تم إلغاء مسودة فاتورة الشراء.');
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }

        $this->refreshInvoice();
    }

    private function refreshInvoice(): void
    {
        $this->invoice->refresh()->load(['supplier', 'items.product']);
    }

    public function render()
    {
        $this->invoice->load(['supplier', 'items.product', 'creator', 'confirmer']);
        $itemIds = $this->invoice->items->pluck('id');

        $stockMovements = StockMovement::query()
            ->with(['product', 'creator'])
            ->where('source_type', StockMovement::SOURCE_PURCHASE_ITEM)
            ->whereIn('source_id', $itemIds)
            ->orderBy('id')
            ->get();

        return view('livewire.purchase-invoices.purchase-invoice-show', [
            'stockMovements' => $stockMovements,
        ])->layout('layouts.app', ['header' => 'فاتورة شراء: ' . $this->invoice->invoice_number]);
    }
}
