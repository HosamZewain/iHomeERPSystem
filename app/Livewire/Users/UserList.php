<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'لا يمكنك إيقاف حسابك الحالي.');
            return;
        }

        $user->update(['is_active' => ! $user->is_active]);

        session()->flash('success', 'تم ' . ($user->is_active ? 'تفعيل ' : 'إيقاف ') . $user->name . '.');
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            }))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.users.user-list', [
            'users' => $users,
            'roles' => UserRole::cases(),
        ])->layout('layouts.app', ['header' => 'المستخدمون']);
    }
}
