<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('sites.view');
    }

    public function view(User $user, Site $site): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('sites.manage');
    }

    public function update(User $user, Site $site): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Site $site): bool
    {
        return $this->create($user);
    }
}
