<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Livewire\Component;

class ProductShow extends Component
{
    public Product $product;

    public function mount(Product $product): void
    {
        $this->product = $product->load(['category', 'supplier']);
    }

    public function toggleActive(): void
    {
        $this->product->update(['is_active' => ! $this->product->is_active]);
        $this->product->refresh()->load(['category', 'supplier']);

        session()->flash('success', 'تم ' . ($this->product->is_active ? 'تفعيل ' : 'إيقاف ') . $this->product->name . '.');
    }

    public function delete(): void
    {
        if (! $this->product->canDelete()) {
            session()->flash('error', 'لا يمكن حذف "' . $this->product->name . '" لأنه مرتبط بحركات مخزون أو بنود فواتير. يمكن إيقافه بدلًا من الحذف.');
            return;
        }

        $this->product->delete();
        session()->flash('success', 'تم حذف المنتج.');

        $this->redirect(route('products.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.products.product-show')
            ->layout('layouts.app', ['header' => 'المنتج: ' . $this->product->name]);
    }
}
