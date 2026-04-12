<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Component;

class UserEdit extends Component
{
    public User $user;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = '';
    public bool $is_active = true;

    public function mount(User $user): void
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role->value;
        $this->is_active = $user->is_active;
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', array_column(UserRole::cases(), 'value')),
            'is_active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];

        // Prevent users from demoting or deactivating themselves.
        if ($this->user->id === auth()->id()) {
            if ($this->role !== $this->user->role->value) {
                $this->addError('role', 'لا يمكنك تغيير دور حسابك الحالي.');
                return;
            }
            if (! $this->is_active) {
                $this->addError('is_active', 'لا يمكنك إيقاف حسابك الحالي.');
                return;
            }
        }

        if (filled($this->password)) {
            $data['password'] = $this->password;
        }

        $this->user->update($data);

        session()->flash('success', 'تم تحديث المستخدم بنجاح.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.user-form', [
            'roles' => UserRole::cases(),
            'isEditing' => true,
            'pageTitle' => 'تعديل مستخدم',
        ])->layout('layouts.app', ['header' => 'تعديل مستخدم: ' . $this->user->name]);
    }
}
