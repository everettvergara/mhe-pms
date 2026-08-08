<?php

namespace App\Services;

use App\Enums\RecordStatus;
use App\Models\District;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Collection;

class UserAssignmentService
{
    /**
     * @return Collection<int, District>
     */
    public function districtsWithActiveSites(): Collection
    {
        return District::query()
            ->with(['sites' => fn ($query) => $query
                ->where('status', RecordStatus::Active)
                ->orderBy('site_name'),
            ])
            ->whereHas('sites', fn ($query) => $query->where('status', RecordStatus::Active))
            ->orderBy('district_name')
            ->get();
    }

    /**
     * @return Collection<int, Collection<int, Site>>
     */
    public function groupSitesByDistrict(User $user): Collection
    {
        $sites = $user->relationLoaded('sites')
            ? $user->sites
            : $user->sites()->with('district')->orderBy('site_name')->get();

        return $sites
            ->sortBy(fn (Site $site) => sprintf(
                '%s|%s',
                $site->district?->district_name ?? 'ZZZ',
                $site->site_name,
            ))
            ->groupBy(fn (Site $site) => $site->district?->district_name ?? 'Unassigned');
    }

    /**
     * @return list<string>
     */
    public function assignedDistrictNames(User $user): array
    {
        return $this->groupSitesByDistrict($user)
            ->keys()
            ->filter(fn (string $name) => $name !== 'Unassigned')
            ->sort()
            ->values()
            ->all();
    }
}
