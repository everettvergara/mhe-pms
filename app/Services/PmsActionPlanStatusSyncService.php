<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\PmsActionPlanStatus;
use App\Models\ActionPlan;
use App\Models\PmsHeader;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PmsActionPlanStatusSyncService
{
    public function sync(PmsHeader $pmsHeader): PmsHeader
    {
        return DB::transaction(function () use ($pmsHeader) {
            $pmsHeader->refresh();

            $statuses = $this->collectActionPlanStatuses($pmsHeader);
            $newStatus = $this->resolveStatus($statuses);

            if ($pmsHeader->action_plan_status !== $newStatus) {
                $pmsHeader->update([
                    'action_plan_status' => $newStatus,
                ]);
            }

            return $pmsHeader->refresh();
        });
    }

    /**
     * @return Collection<int, ActionPlanStatus>
     */
    protected function collectActionPlanStatuses(PmsHeader $pmsHeader): Collection
    {
        return ActionPlan::query()
            ->whereHas('pmsDetail', fn ($query) => $query->where('pms_header_id', $pmsHeader->id))
            ->pluck('status');
    }

    /**
     * @param  Collection<int, ActionPlanStatus>  $statuses
     */
    protected function resolveStatus(Collection $statuses): PmsActionPlanStatus
    {
        if ($statuses->isEmpty()) {
            return PmsActionPlanStatus::None;
        }

        if ($statuses->contains(ActionPlanStatus::Pending)) {
            return PmsActionPlanStatus::Pending;
        }

        if ($statuses->contains(ActionPlanStatus::WaitingForFastConfirmation)) {
            return PmsActionPlanStatus::WaitingForFastConfirmation;
        }

        if ($statuses->contains(ActionPlanStatus::Rejected)) {
            return PmsActionPlanStatus::Rejected;
        }

        if ($statuses->every(fn (ActionPlanStatus $status) => $status === ActionPlanStatus::Confirmed)) {
            return PmsActionPlanStatus::Confirmed;
        }

        if ($statuses->every(fn (ActionPlanStatus $status) => $status === ActionPlanStatus::Cancelled)) {
            return PmsActionPlanStatus::Cancelled;
        }

        return PmsActionPlanStatus::Pending;
    }
}
