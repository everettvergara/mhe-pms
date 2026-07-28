<?php

namespace App\Services;

use App\Enums\ChecklistAnswer;
use App\Enums\PmsActionPlanStatus;
use App\Enums\PmsStatus;
use App\Enums\RecordStatus;
use App\Models\ChecklistItem;
use App\Models\PmsDetail;
use App\Models\PmsHeader;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class PmsService
{
    public function __construct(
        protected NumberSequenceService $numberSequenceService,
        protected ActivityLogService $activityLogService,
        protected UserDataScopeService $userDataScopeService,
    ) {}

    /**
     * @param  array<string, mixed>  $headerData
     */
    public function createDraft(User $user, array $headerData): PmsHeader
    {
        if (! $user->isSupplier()) {
            throw new RuntimeException('Only supplier users can create PMS records.');
        }

        $assignedSupplierIds = $user->assignedSupplierIds();

        if ($assignedSupplierIds === []) {
            throw new RuntimeException('No suppliers are assigned to this user.');
        }

        $assignedSiteIds = $user->assignedSiteIds();

        if ($assignedSiteIds === []) {
            throw new RuntimeException('No sites are assigned to this user.');
        }

        $siteId = (int) ($headerData['site_id'] ?? 0);
        $supplierId = (int) ($headerData['supplier_id'] ?? ($assignedSupplierIds[0] ?? 0));

        if (count($assignedSupplierIds) > 1 && ! isset($headerData['supplier_id'])) {
            throw new InvalidArgumentException('Please select a supplier for this PMS.');
        }

        if (! in_array($supplierId, $assignedSupplierIds, true)) {
            throw new InvalidArgumentException('The selected supplier is not assigned to this user.');
        }

        if (! $this->userDataScopeService->canAccessPmsHeader($user, $supplierId, $siteId)) {
            throw new InvalidArgumentException('The selected site is not assigned to this user.');
        }

        return DB::transaction(function () use ($user, $headerData, $supplierId) {
            $pmsHeader = PmsHeader::query()->create([
                'pms_no' => $this->numberSequenceService->nextNumber('pms'),
                'supplier_id' => $supplierId,
                'site_id' => $headerData['site_id'],
                'technician_name' => $headerData['technician_name'] ?? '',
                'date_from' => $headerData['date_from'] ?? null,
                'date_to' => $headerData['date_to'] ?? null,
                'next_schedule_date' => $headerData['next_schedule_date'] ?? null,
                'mhe_type_id' => $headerData['mhe_type_id'] ?? null,
                'unit_number' => $headerData['unit_number'] ?? '',
                'serial_number' => $headerData['serial_number'] ?? '',
                'status' => PmsStatus::Draft,
                'action_plan_status' => PmsActionPlanStatus::None,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->seedChecklistDetails($pmsHeader, $user);

            $this->activityLogService->log(
                $user,
                'pms',
                'create',
                $pmsHeader->id,
                "Created draft PMS {$pmsHeader->pms_no}.",
            );

            return $pmsHeader->load(['pmsDetails.checklistItem.checklistGroup', 'supplier', 'site', 'mheType']);
        });
    }

    /**
     * @param  array<string, mixed>  $headerData
     * @param  array<int, array<string, mixed>>  $detailsData
     */
    public function saveDraft(PmsHeader $pmsHeader, User $user, array $headerData, array $detailsData = []): PmsHeader
    {
        $this->assertEditableDraft($pmsHeader, $user);

        return DB::transaction(function () use ($pmsHeader, $user, $headerData, $detailsData) {
            $this->applyDraftUpdates($pmsHeader, $user, $headerData, $detailsData);

            $this->activityLogService->log(
                $user,
                'pms',
                'update',
                $pmsHeader->id,
                "Updated draft PMS {$pmsHeader->pms_no}.",
            );

            return $pmsHeader->refresh()->load(['pmsDetails.checklistItem.checklistGroup', 'supplier', 'site', 'mheType']);
        });
    }

    /**
     * @param  array<string, mixed>  $headerData
     * @param  array<int, array<string, mixed>>  $detailsData
     */
    protected function applyDraftUpdates(PmsHeader $pmsHeader, User $user, array $headerData, array $detailsData): void
    {
        $pmsHeader->update([
            'site_id' => $headerData['site_id'] ?? $pmsHeader->site_id,
            'technician_name' => $headerData['technician_name'] ?? $pmsHeader->technician_name,
            'date_from' => $headerData['date_from'] ?? $pmsHeader->date_from,
            'date_to' => $headerData['date_to'] ?? $pmsHeader->date_to,
            'next_schedule_date' => $headerData['next_schedule_date'] ?? $pmsHeader->next_schedule_date,
            'mhe_type_id' => $headerData['mhe_type_id'] ?? $pmsHeader->mhe_type_id,
            'unit_number' => $headerData['unit_number'] ?? $pmsHeader->unit_number,
            'serial_number' => $headerData['serial_number'] ?? $pmsHeader->serial_number,
            'updated_by' => $user->id,
        ]);

        foreach ($detailsData as $detailData) {
            $detail = PmsDetail::query()
                ->where('pms_header_id', $pmsHeader->id)
                ->where('id', $detailData['id'] ?? null)
                ->first();

            if ($detail === null) {
                continue;
            }

            $answer = $detailData['answer'] ?? $detail->answer;
            $remarks = $detailData['remarks'] ?? $detail->remarks;

            $detail->update([
                'answer' => $answer,
                'remarks' => $remarks,
                'updated_by' => $user->id,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $headerData
     * @param  array<int, array<string, mixed>>  $detailsData
     */
    public function saveAndFinalize(PmsHeader $pmsHeader, User $user, array $headerData, array $detailsData = []): PmsHeader
    {
        $this->assertEditableDraft($pmsHeader, $user);

        return DB::transaction(function () use ($pmsHeader, $user, $headerData, $detailsData) {
            $this->applyDraftUpdates($pmsHeader, $user, $headerData, $detailsData);

            $pmsHeader->refresh()->load(['pmsDetails.checklistItem', 'pmsDetails.actionPlans']);

            $this->validateHeaderForSubmission($pmsHeader);
            $this->validateChecklistForSubmission($pmsHeader);

            $hasFindings = $pmsHeader->pmsDetails->contains(
                fn (PmsDetail $detail) => $detail->answer === ChecklistAnswer::NoGood,
            );

            $pmsHeader->update([
                'status' => $hasFindings ? PmsStatus::WithFindings : PmsStatus::NoFindings,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'pms',
                'finalize',
                $pmsHeader->id,
                "Saved PMS {$pmsHeader->pms_no} as final.",
            );

            return $pmsHeader->refresh()->load(['pmsDetails.checklistItem.checklistGroup', 'supplier', 'site', 'mheType']);
        });
    }

    public function revertToDraft(PmsHeader $pmsHeader, User $user): PmsHeader
    {
        if (! $pmsHeader->isSubmitted()) {
            throw new RuntimeException('Only finalized PMS records can be reverted to draft.');
        }

        if (! $this->userDataScopeService->canAccessPmsHeader($user, (int) $pmsHeader->supplier_id, (int) $pmsHeader->site_id)) {
            throw new RuntimeException('You are not allowed to revert this PMS record.');
        }

        return DB::transaction(function () use ($pmsHeader, $user) {
            $pmsHeader->update([
                'status' => PmsStatus::Draft,
                'submitted_by' => null,
                'submitted_at' => null,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'pms',
                'revert_to_draft',
                $pmsHeader->id,
                "Reverted PMS {$pmsHeader->pms_no} to draft.",
            );

            return $pmsHeader->refresh();
        });
    }

    public function cancel(PmsHeader $pmsHeader, User $user): PmsHeader
    {
        if ($pmsHeader->isCancelled()) {
            throw new RuntimeException('This PMS record is already cancelled.');
        }

        if (! $this->userDataScopeService->canAccessPmsHeader($user, (int) $pmsHeader->supplier_id, (int) $pmsHeader->site_id)) {
            throw new RuntimeException('You are not allowed to cancel this PMS record.');
        }

        return DB::transaction(function () use ($pmsHeader, $user) {
            $pmsHeader->update([
                'status' => PmsStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'pms',
                'cancel',
                $pmsHeader->id,
                "Cancelled PMS {$pmsHeader->pms_no}.",
            );

            return $pmsHeader->refresh();
        });
    }

    protected function seedChecklistDetails(PmsHeader $pmsHeader, User $user): void
    {
        $checklistItems = ChecklistItem::query()
            ->where('status', RecordStatus::Active)
            ->whereHas('checklistGroup', fn ($query) => $query->where('status', RecordStatus::Active))
            ->with('checklistGroup')
            ->get()
            ->sortBy([
                fn (ChecklistItem $item) => $item->checklistGroup?->sequence ?? 0,
                fn (ChecklistItem $item) => $item->sequence,
            ]);

        foreach ($checklistItems as $checklistItem) {
            PmsDetail::query()->create([
                'pms_header_id' => $pmsHeader->id,
                'checklist_item_id' => $checklistItem->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
    }

    protected function assertEditableDraft(PmsHeader $pmsHeader, User $user): void
    {
        if (! $pmsHeader->isDraft()) {
            throw new RuntimeException('Only draft PMS records can be edited.');
        }

        if (! $this->userDataScopeService->canAccessPmsHeader($user, (int) $pmsHeader->supplier_id, (int) $pmsHeader->site_id)) {
            throw new RuntimeException('You are not allowed to modify this PMS record.');
        }
    }

    protected function validateHeaderForSubmission(PmsHeader $pmsHeader): void
    {
        if (
            blank($pmsHeader->site_id)
            || blank($pmsHeader->technician_name)
            || blank($pmsHeader->date_from)
            || blank($pmsHeader->date_to)
            || blank($pmsHeader->next_schedule_date)
            || blank($pmsHeader->mhe_type_id)
            || blank($pmsHeader->unit_number)
            || blank($pmsHeader->serial_number)
        ) {
            throw new InvalidArgumentException('PMS header information is incomplete.');
        }

        if ($pmsHeader->date_to < $pmsHeader->date_from) {
            throw new InvalidArgumentException('Date To must not be earlier than Date From.');
        }
    }

    protected function validateChecklistForSubmission(PmsHeader $pmsHeader): void
    {
        if ($pmsHeader->pmsDetails->isEmpty()) {
            throw new InvalidArgumentException('Checklist details are missing.');
        }

        foreach ($pmsHeader->pmsDetails as $detail) {
            if ($detail->answer === null) {
                throw new InvalidArgumentException('Every checklist item must have an answer before saving as final.');
            }

            if ($detail->answer === ChecklistAnswer::NoGood) {
                if (blank($detail->remarks)) {
                    throw new InvalidArgumentException('Remarks are required for every No Good checklist answer.');
                }

                if ($detail->actionPlans->isEmpty()) {
                    $itemLabel = $detail->checklistItem?->description ?? 'checklist item';

                    throw new InvalidArgumentException("At least one action item is required for No Good answer: {$itemLabel}.");
                }
            }
        }
    }
}
