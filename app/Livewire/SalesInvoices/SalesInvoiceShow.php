<?php

namespace App\Livewire\SalesInvoices;

use App\Models\SalesInvoice;
use App\Models\StockMovement;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SalesInvoiceShow extends Component
{
    public SalesInvoice $invoice;

    public function mount(SalesInvoice $salesInvoice): void
    {
        $this->invoice = $salesInvoice;
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

    private function refreshInvoice(): void
    {
        $this->invoice->refresh()->load(['customer', 'partner', 'items.product']);
    }

    public function render()
    {
        $this->invoice->load(['customer', 'partner', 'quotation', 'items.product', 'creator', 'confirmer']);
        $itemIds = $this->invoice->items->pluck('id');

        $stockMovements = StockMovement::query()
            ->with(['product', 'creator'])
            ->where('source_type', StockMovement::SOURCE_SALES_ITEM)
            ->whereIn('source_id', $itemIds)
            ->orderBy('id')
            ->get();

        return view('livewire.sales-invoices.sales-invoice-show', [
            'stockMovements' => $stockMovements,
            'showProfit' => auth()->user()->hasPermission('sales.view_profit'),
        ])->layout('layouts.app', ['header' => 'فاتورة بيع: ' . $this->invoice->invoice_number]);
    }
}
