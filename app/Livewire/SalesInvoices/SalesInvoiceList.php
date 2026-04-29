<?php

namespace App\Livewire\SalesInvoices;

use App\Enums\SalesChannel;
use App\Enums\SalesInvoiceStatus;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\SalesInvoice;
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

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingChannelFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPartnerFilter(): void
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
        $invoices = SalesInvoice::query()
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

        $this->applySorting($invoices);

        $invoices = $invoices->paginate(15);

        return view('livewire.sales-invoices.sales-invoice-list', [
            'invoices' => $invoices,
            'statuses' => SalesInvoiceStatus::cases(),
            'channels' => SalesChannel::cases(),
            'partners' => Partner::query()->orderBy('name')->get(),
            'sortableFields' => $this->sortableFields(),
        ])->layout('layouts.app', ['header' => 'فواتير البيع']);
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
            'items_count' => 'البنود',
            'gross_total' => 'إجمالي العميل',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'آخر تحديث',
        ];
    }
}
