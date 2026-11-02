<?php

namespace App\Concerns;

use App\Enums\Role;

/** Add `use HasRole;` to App\Models\User and cast 'role' => Role::class, 'is_active' => 'boolean'. */
trait HasRole
{
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $role = $this->role instanceof Role ? $this->role : Role::tryFrom((string) $this->role);

        return $role?->can($permission) ?? false;
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('*');
    }
}
