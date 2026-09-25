<?php

namespace App\Services;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\DowntimeStatus;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheInventory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class MheDowntimeService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected UserDataScopeService $userDataScopeService,
        protected DowntimeNotificationService $downtimeNotificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDraft(User $user, array $data): MheDowntime
    {
        $this->assertSiteAccess($user, (int) $data['site_id'], $this->payloadFromData($data)['supplier_id']);

        return DB::transaction(function () use ($user, $data) {
            $downtime = MheDowntime::query()->create([
                ...$this->payloadFromData($data),
                'status' => DowntimeStatus::Draft,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtimes',
                'create',
                $downtime->id,
                "Created draft MHE downtime #{$downtime->id}.",
            );

            return $downtime->load(['site.district', 'mheType', 'mheCategory']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveDraft(MheDowntime $downtime, User $user, array $data): MheDowntime
    {
        $this->assertEditableDraft($downtime, $user);
        $payload = $this->payloadFromData($data);
        $this->assertSiteAccess($user, (int) $payload['site_id'], $payload['supplier_id']);

        return DB::transaction(function () use ($downtime, $user, $data) {
            $downtime->update([
                ...$this->payloadFromData($data),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtimes',
                'update',
                $downtime->id,
                "Updated draft MHE downtime #{$downtime->id}.",
            );

            return $downtime->refresh()->load(['site.district', 'mheType', 'mheCategory', 'actionPlans', 'attachments']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function savePostedDatetimes(MheDowntime $downtime, User $user, array $data): MheDowntime
    {
        if (! $downtime->isPosted()) {
            throw new RuntimeException('Only posted downtime records support datetime updates.');
        }

        if ($user->isSupplier()) {
            throw new RuntimeException('Suppliers cannot update posted downtime records.');
        }

        $this->assertSiteAccess($user, (int) $downtime->site_id, $downtime->supplier_id);

        return DB::transaction(function () use ($downtime, $user, $data) {
            $dateOfIncident = isset($data['date_of_incident']) ? Carbon::parse($data['date_of_incident']) : null;

            $downtime->update([
                'date_of_incident' => $dateOfIncident,
                'hours_down' => self::computeHoursDown($dateOfIncident, $downtime->uptime),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtimes',
                'update',
                $downtime->id,
                "Updated posted downtime datetimes for #{$downtime->id}.",
            );

            return $downtime->refresh()->load(['site.district', 'mheType', 'mheCategory', 'actionPlans', 'attachments']);
        });
    }

    public static function findInventoryForUnit(int $siteId, string $refUnitNo): ?MheInventory
    {
        $unit = trim($refUnitNo);

        if ($unit === '') {
            return null;
        }

        return MheInventory::findBySiteAndUnit($siteId, $refUnitNo, activeOnly: true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveAndPost(MheDowntime $downtime, User $user, array $data): MheDowntime
    {
        $this->assertEditableDraft($downtime, $user);
        $payload = $this->payloadFromData($data);
        $this->assertSiteAccess($user, (int) $payload['site_id'], $payload['supplier_id']);

        return DB::transaction(function () use ($downtime, $user, $data) {
            $downtime->update([
                ...$this->payloadFromData($data),
                'status' => DowntimeStatus::Posted,
                'posted_by' => $user->id,
                'posted_at' => now(),
                'cancelled_by' => null,
                'cancelled_at' => null,
                'updated_by' => $user->id,
            ]);

            $this->downtimeNotificationService->notifyPosted($downtime, $user);

            $this->activityLogService->log(
                $user,
                'mhe_downtimes',
                'post',
                $downtime->id,
                "Posted MHE downtime #{$downtime->id}.",
            );

            return $downtime->refresh()->load(['site.district', 'mheType', 'mheCategory', 'actionPlans', 'attachments']);
        });
    }

    public function cancel(MheDowntime $downtime, User $user): MheDowntime
    {
        if ($downtime->isCancelled()) {
            throw new RuntimeException('Downtime record is already cancelled.');
        }

        $this->assertSiteAccess($user, (int) $downtime->site_id, $downtime->supplier_id);

        return DB::transaction(function () use ($downtime, $user) {
            $downtime->update([
                'status' => DowntimeStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtimes',
                'cancel',
                $downtime->id,
                "Cancelled MHE downtime #{$downtime->id}.",
            );

            return $downtime->refresh()->load(['site.district', 'mheType', 'mheCategory', 'actionPlans', 'attachments']);
        });
    }

    public function revertToDraft(MheDowntime $downtime, User $user): MheDowntime
    {
        if ($downtime->isDraft()) {
            throw new RuntimeException('Only posted or cancelled downtime records can be reverted to draft.');
        }

        $this->assertSiteAccess($user, (int) $downtime->site_id, $downtime->supplier_id);

        return DB::transaction(function () use ($downtime, $user) {
            $downtime->update([
                'status' => DowntimeStatus::Draft,
                'posted_by' => null,
                'posted_at' => null,
                'cancelled_by' => null,
                'cancelled_at' => null,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtimes',
                'revert',
                $downtime->id,
                "Reverted MHE downtime #{$downtime->id} to draft.",
            );

            return $downtime->refresh()->load(['site.district', 'mheType', 'mheCategory', 'actionPlans', 'attachments']);
        });
    }

    public function syncUptimeFromActionItems(MheDowntime $downtime): MheDowntime
    {
        $plans = $downtime->actionPlans()
            ->where('status', '!=', DowntimeActionPlanStatus::Cancelled)
            ->get();

        $implemented = [
            DowntimeActionPlanStatus::WaitingForFastConfirmation,
            DowntimeActionPlanStatus::Confirmed,
        ];

        $complete = $plans->isNotEmpty()
            && $plans->every(fn (MheDowntimeActionPlan $plan) => in_array($plan->status, $implemented, true));

        $uptime = null;

        if ($complete) {
            $latest = $plans->pluck('date_implemented')->filter()->max();
            $uptime = $latest instanceof Carbon ? $latest : ($latest ? Carbon::parse($latest) : null);
        }

        $downtime->update([
            'uptime' => $uptime,
            'hours_down' => self::computeHoursDown($downtime->date_of_incident, $uptime),
        ]);

        return $downtime;
    }

    public static function computeHoursDown(?Carbon $from, ?Carbon $to): ?float
    {
        if ($from === null || $to === null) {
            return null;
        }

        return round($from->diffInMinutes($to) / 60, 2);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function payloadFromData(array $data): array
    {
        $dateOfIncident = isset($data['date_of_incident']) ? Carbon::parse($data['date_of_incident']) : null;
        $refUnitNo = trim((string) ($data['ref_unit_no'] ?? ''));
        $inventory = $refUnitNo !== ''
            ? self::findInventoryForUnit((int) $data['site_id'], $refUnitNo)
            : null;

        return [
            'title' => $data['title'],
            'site_id' => $data['site_id'],
            'mhe_type_id' => $data['mhe_type_id'],
            'mhe_category_id' => $data['mhe_category_id'],
            'mhe_inventory_id' => $inventory?->id,
            'supplier_id' => $inventory?->supplier_id ?? ($data['supplier_id'] ?? null),
            'ref_unit_no' => $refUnitNo,
            'date_of_incident' => $dateOfIncident,
            'uptime' => null,
            'hours_down' => null,
            'root_cause' => $data['root_cause'] ?? null,
            'description' => $data['description'] ?? null,
            'w_spare_unit' => (bool) ($data['w_spare_unit'] ?? false),
        ];
    }

    protected function assertEditableDraft(MheDowntime $downtime, User $user): void
    {
        if (! $downtime->isDraft()) {
            throw new RuntimeException('Only draft downtime records can be edited.');
        }

        $this->assertSiteAccess($user, (int) $downtime->site_id, $downtime->supplier_id);

        if ($user->isSupplier() && ! $this->userDataScopeService->canSupplierEditMheDowntime($user, $downtime)) {
            throw new RuntimeException('You are not allowed to edit this downtime record.');
        }
    }

    protected function assertSiteAccess(User $user, int $siteId, ?int $supplierId = null): void
    {
        if (! $this->userDataScopeService->canAccessMheDowntime($user, $siteId, $supplierId)) {
            throw new InvalidArgumentException('The selected site or supplier is not assigned to this user.');
        }
    }
}
