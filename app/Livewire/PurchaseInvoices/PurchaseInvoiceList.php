<?php

namespace App\Livewire\PurchaseInvoices;

use App\Enums\PurchaseInvoiceStatus;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseInvoiceList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $supplierFilter = '';
    public string $statusFilter = '';
    public string $sortField = 'invoice_date';
    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSupplierFilter(): void
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

    public function render()
    {
        $invoices = PurchaseInvoice::query()
            ->with(['supplier'])
            ->withCount('items')
            ->when($this->search, fn ($query) => $query->where('invoice_number', 'like', "%{$this->search}%"))
            ->when($this->supplierFilter, fn ($query) => $query->where('supplier_id', $this->supplierFilter))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter));

        $this->applySorting($invoices);

        $invoices = $invoices->paginate(15);

        return view('livewire.purchase-invoices.purchase-invoice-list', [
            'invoices' => $invoices,
            'suppliers' => Supplier::query()->orderBy('name')->get(),
            'statuses' => PurchaseInvoiceStatus::cases(),
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'فواتير الشراء']);
    }

    private function applySorting($query): void
    {
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        match ($this->sortField) {
            'invoice_number' => $query->orderBy('invoice_number', $direction),
            'supplier' => $query->orderBy(Supplier::query()->select('name')->whereColumn('suppliers.id', 'purchase_invoices.supplier_id'), $direction),
            'status' => $query->orderBy('status', $direction),
            'items_count' => $query->orderBy('items_count', $direction),
            'total' => $query->orderBy('total', $direction),
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
            'supplier' => 'المورد',
            'status' => 'الحالة',
            'items_count' => 'البنود',
            'total' => 'الإجمالي',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ];
    }
}
