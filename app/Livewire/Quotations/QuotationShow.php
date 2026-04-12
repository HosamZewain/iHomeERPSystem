<?php

namespace App\Livewire\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuotationShow extends Component
{
    public Quotation $quotation;
    public string $status = '';

    public function mount(Quotation $quotation): void
    {
        $this->quotation = $quotation;
        $this->status = $quotation->status->value;
    }

    public function updateStatus(): void
    {
        $this->validate([
            'status' => ['required', Rule::in(array_column(QuotationStatus::cases(), 'value'))],
        ]);

        if ($this->status === QuotationStatus::Converted->value && $this->quotation->status !== QuotationStatus::Converted) {
            session()->flash('error', 'استخدم زر التحويل لإنشاء فاتورة بيع وربطها بعرض السعر.');
            $this->status = $this->quotation->status->value;
            return;
        }

        $this->quotation->update(['status' => $this->status]);
        $this->quotation->refresh();

        session()->flash('success', 'تم تحديث حالة عرض السعر.');
    }

    public function convertToInvoice(): void
    {
        try {
            $invoice = $this->quotation->convertToSalesInvoice(auth()->user());
            session()->flash('success', 'تم تحويل عرض السعر إلى فاتورة بيع مسودة.');
            $this->redirect(route('sales-invoices.show', $invoice), navigate: true);
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
            $this->quotation->refresh();
            $this->status = $this->quotation->status->value;
        }
    }

    public function render()
    {
        $this->quotation->load(['customer', 'items.product', 'creator', 'salesInvoice']);

        return view('livewire.quotations.quotation-show', [
            'statuses' => QuotationStatus::cases(),
            'discountTypes' => Quotation::discountTypes(),
        ])->layout('layouts.app', ['header' => 'عرض سعر: ' . $this->quotation->quotation_number]);
    }
}
