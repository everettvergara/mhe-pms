<?php

namespace App\Http\Requests\Concerns;

use App\Models\Role;

trait ValidatesUserAssignments
{
    protected function isSuperAdminInput(): bool
    {
        return $this->boolean('is_super_admin');
    }

    protected function isSupplierRoleInput(): bool
    {
        $supplierRoleId = Role::query()->where('slug', Role::SLUG_SUPPLIER_USER)->value('id');

        return (int) $this->input('role_id') === (int) $supplierRoleId;
    }

    protected function isFastAdminRoleInput(): bool
    {
        $fastAdminRoleId = Role::query()->where('slug', Role::SLUG_FAST_ADMINISTRATOR)->value('id');

        return (int) $this->input('role_id') === (int) $fastAdminRoleId;
    }

    protected function requiresSiteAssignment(): bool
    {
        return ! $this->isSuperAdminInput()
            && ($this->isSupplierRoleInput() || $this->isFastAdminRoleInput());
    }

    protected function requiresSupplierAssignment(): bool
    {
        return ! $this->isSuperAdminInput() && $this->isSupplierRoleInput();
    }
}
