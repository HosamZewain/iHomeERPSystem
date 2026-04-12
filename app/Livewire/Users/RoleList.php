<?php

namespace App\Livewire\Users;

use App\Enums\UserRole;
use App\Models\User;
use Livewire\Component;

class RoleList extends Component
{
    public function render()
    {
        $roles = collect(UserRole::cases())->map(function (UserRole $role) {
            return [
                'role' => $role,
                'user_count' => User::where('role', $role->value)->count(),
                'permissions' => $role->permissions(),
            ];
        });

        return view('livewire.users.role-list', [
            'roles' => $roles,
        ])->layout('layouts.app', ['header' => 'الأدوار والصلاحيات']);
    }
}
