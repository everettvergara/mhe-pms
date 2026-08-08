<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

class FastAdminResolver
{
    /**
     * @return Collection<int, User>
     */
    public function resolveForSite(?int $siteId): Collection
    {
        if ($siteId === null) {
            return collect();
        }

        return User::query()
            ->where('status', UserStatus::Active)
            ->whereHas('role', fn ($query) => $query->where('slug', Role::SLUG_FAST_ADMINISTRATOR))
            ->whereHas('sites', fn ($query) => $query->where('sites.id', $siteId))
            ->get();
    }
}
