<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\DowntimeActionPlanStatus;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ActionPlan;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheInventory;
use App\Models\PmsHeader;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forAdmin(User $user): array
    {
        $actionPlanQuery = ActionPlan::query()
            ->whereHas('pmsDetail.pmsHeader');
        $this->userDataScopeService->scopeActionPlan($actionPlanQuery, $user);

        $downtimeActionPlanQuery = MheDowntimeActionPlan::query()
            ->whereHas('mheDowntime');
        $this->userDataScopeService->scopeMheDowntimeActionPlan($downtimeActionPlanQuery, $user);

        $siteUnits = $this->adminSiteUnits($user);

        return [
            'pms_month_done' => $siteUnits['done'],
            'pms_month_total' => $siteUnits['total'],
            'waiting_confirmation' => (clone $actionPlanQuery)
                ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
                ->wherePmsSubmitted()
                ->count(),
            'currently_down_units' => $this->currentlyDownUnitCount($user),
            'downtime_waiting_confirmation' => (clone $downtimeActionPlanQuery)
                ->where('status', DowntimeActionPlanStatus::WaitingForFastConfirmation)
                ->count(),
            'pending_confirmations' => $this->pendingConfirmations($user),
            'downtime_pending_confirmations' => $this->downtimePendingConfirmations($user),
            'site_units' => $siteUnits['top'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forSupplier(User $user): array
    {
        $units = $this->supplierInventoryUnits($user);
        $pmsWaitingQuery = $this->pmsWaitingImplementationQuery($user);
        $downtimeWaitingQuery = $this->downtimeWaitingImplementationQuery($user);

        return [
            'kpis' => [
                'pms_month_done' => $units->where('pms_this_month', true)->count(),
                'pms_month_total' => $units->count(),
                'pms_waiting_implementation' => (clone $pmsWaitingQuery)->count(),
                'currently_down_units' => $this->currentlyDownUnitCount($user),
                'downtime_waiting_implementation' => (clone $downtimeWaitingQuery)->count(),
            ],
            'pms_waiting_implementation' => (clone $pmsWaitingQuery)
                ->with(['pmsDetail.pmsHeader.site'])
                ->orderBy('timeline_to')
                ->limit(10)
                ->get(),
            'units' => $units->take(10)->values(),
            'downtime_waiting_implementation' => (clone $downtimeWaitingQuery)
                ->with(['mheDowntime.site', 'mheDowntime.mheInventory'])
                ->orderBy('timeline_to')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function supplierUnits(User $user): array
    {
        $units = $this->supplierInventoryUnits($user);

        return [
            'units' => $units,
            'kpis' => [
                'pms_month_done' => $units->where('pms_this_month', true)->count(),
                'pms_month_total' => $units->count(),
            ],
        ];
    }

    /**
     * @return Collection<int, MheInventory>
     */
    protected function upcomingPmsSchedule(?User $user = null, int $limit = 10): Collection
    {
        return $this->scheduledInventoryQuery($user)
            ->orderBy('next_pms_date')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<MheInventory>
     */
    protected function scheduledInventoryQuery(?User $user = null): Builder
    {
        $query = MheInventory::query()
            ->whereNotNull('next_pms_date')
            ->where('equipment_status', RecordStatus::Active)
            ->with(['supplier', 'siteRelation', 'mheType', 'lastPmsHeader']);

        if ($user !== null) {
            $this->userDataScopeService->scopeMheInventory($query, $user);
        }

        return $query;
    }

    /**
     * @return Collection<int, ActionPlan>
     */
    protected function pendingConfirmations(User $user): Collection
    {
        $query = ActionPlan::query()
            ->where('status', ActionPlanStatus::WaitingForFastConfirmation)
            ->wherePmsSubmitted()
            ->with([
                'pmsDetail.checklistItem',
                'pmsDetail.pmsHeader.supplier',
                'pmsDetail.pmsHeader.site',
                'pmsDetail.pmsHeader.mheType',
            ]);

        $this->userDataScopeService->scopeActionPlan($query, $user);

        return $query
            ->latest('updated_at')
            ->limit(10)
            ->get();
    }

    /**
     * @return array{done: int, total: int, top: Collection<int, MheInventory>}
     */
    protected function adminSiteUnits(User $user): array
    {
        $query = MheInventory::query()
            ->where('equipment_status', RecordStatus::Active)
            ->with(['supplier', 'siteRelation']);

        $this->userDataScopeService->scopeMheInventory($query, $user);

        $doneKeys = $this->pmsDoneThisMonthKeys($user);

        $units = $query->get()->each(function (MheInventory $unit) use ($doneKeys): void {
            $key = $this->unitMonthKey((int) $unit->site_id, (string) $unit->unit_no);
            $unit->setAttribute('pms_this_month', isset($doneKeys[$key]));
        });

        $top = $units->sort(function (MheInventory $a, MheInventory $b): int {
            $done = ((int) (bool) $a->getAttribute('pms_this_month')) <=> ((int) (bool) $b->getAttribute('pms_this_month'));
            if ($done !== 0) {
                return $done;
            }

            $site = strcasecmp(
                (string) ($a->siteRelation?->site_name ?? ''),
                (string) ($b->siteRelation?->site_name ?? ''),
            );
            if ($site !== 0) {
                return $site;
            }

            return strcasecmp((string) $a->unit_no, (string) $b->unit_no);
        })->take(10)->values();

        return [
            'done' => $units->filter(fn (MheInventory $unit): bool => (bool) $unit->getAttribute('pms_this_month'))->count(),
            'total' => $units->count(),
            'top' => $top,
        ];
    }

    /**
     * @return array<string, true>
     */
    protected function pmsDoneThisMonthKeys(User $user): array
    {
        $query = PmsHeader::query()
            ->whereIn('status', [PmsStatus::WithFindings, PmsStatus::NoFindings])
            ->whereBetween('date_from', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);

        $this->userDataScopeService->scopePmsHeader($query, $user);

        $keys = [];

        foreach ($query->get(['site_id', 'unit_number']) as $pms) {
            $keys[$this->unitMonthKey((int) $pms->site_id, (string) $pms->unit_number)] = true;
        }

        return $keys;
    }

    protected function unitMonthKey(int $siteId, string $unitNo): string
    {
        return $siteId.'|'.strtolower(trim($unitNo));
    }

    /**
     * @return Collection<int, MheDowntimeActionPlan>
     */
    protected function downtimePendingConfirmations(User $user): Collection
    {
        $query = MheDowntimeActionPlan::query()
            ->where('status', DowntimeActionPlanStatus::WaitingForFastConfirmation)
            ->with(['mheDowntime.supplier', 'mheDowntime.site', 'mheDowntime.mheInventory']);

        $this->userDataScopeService->scopeMheDowntimeActionPlan($query, $user);

        return $query->latest('updated_at')->limit(10)->get();
    }

    protected function supplierScopeIsEmpty(User $user): bool
    {
        if (! $user->isSupplier()) {
            return false;
        }

        return $user->assignedSupplierIds() === [] || $user->assignedSiteIds() === [];
    }

    /**
     * Active inventory in the user's site and supplier, with this month's PMS flag.
     * Units without a submitted PMS this month come first.
     *
     * @return Collection<int, MheInventory>
     */
    protected function supplierInventoryUnits(User $user): Collection
    {
        if ($this->supplierScopeIsEmpty($user)) {
            return collect();
        }

        $query = MheInventory::query()
            ->where('equipment_status', RecordStatus::Active)
            ->with(['supplier', 'siteRelation']);

        $this->userDataScopeService->scopeMheInventory($query, $user);

        $doneKeys = $this->pmsDoneUnitKeys($user);

        return $query->get()
            ->map(function (MheInventory $unit) use ($doneKeys): MheInventory {
                $key = $this->inventoryPmsKey(
                    (int) $unit->site_id,
                    (int) $unit->supplier_id,
                    (string) $unit->unit_no,
                );
                $unit->setAttribute('pms_this_month', isset($doneKeys[$key]));

                return $unit;
            })
            ->sortBy(fn (MheInventory $unit) => sprintf(
                '%d-%s-%s',
                $unit->pms_this_month ? 1 : 0,
                mb_strtolower((string) ($unit->siteRelation?->site_name ?? $unit->site ?? '')),
                mb_strtolower((string) $unit->unit_no),
            ))
            ->values();
    }

    /**
     * @return array<string, true>
     */
    protected function pmsDoneUnitKeys(User $user): array
    {
        if ($this->supplierScopeIsEmpty($user)) {
            return [];
        }

        $query = PmsHeader::query()
            ->whereIn('status', [PmsStatus::WithFindings, PmsStatus::NoFindings])
            ->whereBetween('date_from', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);

        $this->userDataScopeService->scopePmsHeader($query, $user);

        $keys = [];

        foreach ($query->get(['site_id', 'supplier_id', 'unit_number']) as $header) {
            $keys[$this->inventoryPmsKey(
                (int) $header->site_id,
                (int) $header->supplier_id,
                (string) $header->unit_number,
            )] = true;
        }

        return $keys;
    }

    protected function inventoryPmsKey(int $siteId, int $supplierId, string $unitNo): string
    {
        return $siteId.'|'.$supplierId.'|'.mb_strtolower(trim($unitNo));
    }

    /**
     * @return Builder<ActionPlan>
     */
    protected function pmsWaitingImplementationQuery(User $user): Builder
    {
        $query = ActionPlan::query()
            ->whereHas('pmsDetail.pmsHeader')
            ->whereIn('status', [ActionPlanStatus::Pending, ActionPlanStatus::Rejected]);

        if ($this->supplierScopeIsEmpty($user)) {
            return $query->whereRaw('0 = 1');
        }

        $this->userDataScopeService->scopeActionPlan($query, $user);

        return $query;
    }

    /**
     * @return Builder<MheDowntimeActionPlan>
     */
    protected function downtimeWaitingImplementationQuery(User $user): Builder
    {
        $query = MheDowntimeActionPlan::query()
            ->whereHas('mheDowntime')
            ->whereIn('status', [DowntimeActionPlanStatus::Pending, DowntimeActionPlanStatus::Rejected]);

        if ($this->supplierScopeIsEmpty($user)) {
            return $query->whereRaw('0 = 1');
        }

        $this->userDataScopeService->scopeMheDowntimeActionPlan($query, $user);

        return $query;
    }

    protected function currentlyDownUnitCount(User $user): int
    {
        if ($this->supplierScopeIsEmpty($user)) {
            return 0;
        }

        $query = MheDowntime::query()->currentlyDown();
        $this->userDataScopeService->scopeMheDowntime($query, $user);

        return $query
            ->get(['mhe_inventory_id', 'site_id', 'supplier_id', 'ref_unit_no'])
            ->unique(fn (MheDowntime $downtime) => $downtime->mhe_inventory_id
                ? 'i:'.$downtime->mhe_inventory_id
                : 'u:'.$downtime->site_id.':'.$downtime->supplier_id.':'.mb_strtolower(trim((string) $downtime->ref_unit_no)))
            ->count();
    }
}
