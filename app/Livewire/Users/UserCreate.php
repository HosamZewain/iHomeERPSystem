<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Component;

class UserCreate extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $role = 'sales';
    public bool $is_active = true;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', array_column(UserRole::cases(), 'value')),
            'is_active' => 'boolean',
        ];
    }

    public function save(): void
    {
        $this->validate();

        User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ]);

        session()->flash('success', 'تم إنشاء المستخدم بنجاح.');

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.users.user-form', [
            'roles' => UserRole::cases(),
            'isEditing' => false,
            'pageTitle' => 'إنشاء مستخدم',
        ])->layout('layouts.app', ['header' => 'إنشاء مستخدم']);
    }
}
