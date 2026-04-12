<?php

namespace App\Livewire\Quotations;

use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Quotation;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class QuotationList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $customerFilter = '';
    public string $statusFilter = '';
    public string $sortField = 'quotation_date';
    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCustomerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSortField(): void
    {
        $this->resetPage();
    }

    public function updatingSortDirection(): void
    {
        $this->resetPage();
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

    public function convert(int $quotationId): void
    {
        $quotation = Quotation::query()->findOrFail($quotationId);

        try {
            $invoice = $quotation->convertToSalesInvoice(auth()->user());
            session()->flash('success', 'تم تحويل عرض السعر إلى فاتورة بيع مسودة.');
            $this->redirect(route('sales-invoices.show', $invoice), navigate: true);
        } catch (ValidationException $exception) {
            session()->flash('error', collect($exception->errors())->flatten()->first());
        }
    }

    public function render()
    {
        $quotations = Quotation::query()
            ->with(['customer', 'salesInvoice'])
            ->withCount('items')
            ->when($this->search, function ($query) {
                $query->where(function ($query) {
                    $query->where('quotation_number', 'like', "%{$this->search}%")
                        ->orWhereHas('customer', function ($query) {
                            $query->where('name', 'like', "%{$this->search}%")
                                ->orWhere('phone', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->customerFilter, fn ($query) => $query->where('customer_id', $this->customerFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter));

        $this->applySorting($quotations);

        $quotations = $quotations->paginate(15);

        return view('livewire.quotations.quotation-list', [
            'quotations' => $quotations,
            'customers' => Customer::query()->orderBy('name')->get(),
            'statuses' => QuotationStatus::cases(),
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'عروض الأسعار']);
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'quotation_number' => $query->orderBy('quotation_number', $direction),
            'customer' => $query->orderBy(Customer::query()->select('name')->whereColumn('customers.id', 'quotations.customer_id'), $direction),
            'status' => $query->orderBy('status', $direction),
            'items_count' => $query->orderBy('items_count', $direction),
            'total' => $query->orderBy('total', $direction),
            default => $query->orderBy('quotation_date', $direction),
        };

        $query->orderBy('id', $direction);
    }

    private function sortableFields(): array
    {
        return [
            'quotation_date' => 'التاريخ',
            'quotation_number' => 'رقم العرض',
            'customer' => 'العميل',
            'status' => 'الحالة',
            'items_count' => 'البنود',
            'total' => 'الإجمالي',
        ];
    }
}
