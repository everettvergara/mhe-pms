<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\ChecklistAnswer;
use App\Enums\PmsStatus;
use App\Enums\ProgressStatus;
use App\Models\ActionPlan;
use App\Models\ActionPlanComment;
use App\Models\PmsDetail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class ActionPlanService
{
    public function __construct(
        protected NumberSequenceService $numberSequenceService,
        protected ActivityLogService $activityLogService,
        protected PmsActionPlanStatusSyncService $pmsActionPlanStatusSyncService,
        protected UserDataScopeService $userDataScopeService,
        protected PmsNotificationService $pmsNotificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, PmsDetail $pmsDetail, array $data): ActionPlan
    {
        $this->assertSupplierOwnership($user, $pmsDetail);

        $pmsDetail->loadMissing('pmsHeader');
        $pmsHeader = $pmsDetail->pmsHeader;

        if ($pmsHeader === null || (! $pmsHeader->isDraft() && $pmsHeader->status !== PmsStatus::WithFindings)) {
            throw new RuntimeException('Action plans cannot be added to this PMS.');
        }

        $this->validateTimeline($data);

        return DB::transaction(function () use ($user, $pmsDetail, $data) {
            $actionPlan = ActionPlan::query()->create([
                'action_plan_no' => $this->numberSequenceService->nextNumber('action_plan'),
                'pms_detail_id' => $pmsDetail->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'responsible_person' => $data['responsible_person'],
                'timeline_from' => $data['timeline_from'],
                'timeline_to' => $data['timeline_to'],
                'status' => ActionPlanStatus::Pending,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->syncPmsHeader($pmsDetail);

            $this->activityLogService->log(
                $user,
                'action_plan',
                'create',
                $actionPlan->id,
                "Created action plan {$actionPlan->action_plan_no}.",
            );

            $this->pmsNotificationService->notifyActionPlanCreated($actionPlan);

            return $actionPlan->refresh()->load(['pmsDetail.pmsHeader', 'comments.creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, ActionPlan $actionPlan, array $data): ActionPlan
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');
        $this->assertSupplierOwnership($user, $actionPlan->pmsDetail);
        $this->assertEditableStatus($actionPlan);

        $this->validateTimeline($data);

        return DB::transaction(function () use ($user, $actionPlan, $data) {
            $actionPlan->update([
                'title' => $data['title'] ?? $actionPlan->title,
                'description' => $data['description'] ?? $actionPlan->description,
                'responsible_person' => $data['responsible_person'] ?? $actionPlan->responsible_person,
                'timeline_from' => $data['timeline_from'] ?? $actionPlan->timeline_from,
                'timeline_to' => $data['timeline_to'] ?? $actionPlan->timeline_to,
                'updated_by' => $user->id,
            ]);

            if ($actionPlan->status === ActionPlanStatus::Rejected) {
                $actionPlan->update([
                    'status' => ActionPlanStatus::Pending,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_remarks' => null,
                ]);

                $this->syncPmsHeader($actionPlan->pmsDetail);
            }

            $this->activityLogService->log(
                $user,
                'action_plan',
                'update',
                $actionPlan->id,
                "Updated action plan {$actionPlan->action_plan_no}.",
            );

            return $actionPlan->refresh()->load(['pmsDetail.pmsHeader', 'comments.creator']);
        });
    }

    public function addComment(
        User $user,
        ActionPlan $actionPlan,
        string $comment,
        ProgressStatus $progressStatus,
    ): ActionPlanComment {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');
        $this->assertSupplierOwnership($user, $actionPlan->pmsDetail);

        if (blank($comment)) {
            throw new InvalidArgumentException('Comment is required.');
        }

        return DB::transaction(function () use ($user, $actionPlan, $comment, $progressStatus) {
            $actionPlanComment = ActionPlanComment::query()->create([
                'action_plan_id' => $actionPlan->id,
                'comment' => $comment,
                'progress_status' => $progressStatus,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            $this->activityLogService->log(
                $user,
                'action_plan',
                'add_comment',
                $actionPlan->id,
                "Added progress comment to action plan {$actionPlan->action_plan_no}.",
            );

            return $actionPlanComment->load('creator');
        });
    }

    public function markImplemented(User $user, ActionPlan $actionPlan, bool $unitSafeGuaranteed): ActionPlan
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');
        $this->assertSupplierOwnership($user, $actionPlan->pmsDetail);

        if (! in_array($actionPlan->status, [ActionPlanStatus::Pending, ActionPlanStatus::Rejected], true)) {
            throw new RuntimeException('Only pending or rejected action plans can be marked as implemented.');
        }

        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null || ! $pmsHeader->isSubmitted()) {
            throw new RuntimeException('Action plans can only be marked as implemented after the PMS is finalized.');
        }

        if (! $unitSafeGuaranteed) {
            throw new RuntimeException('You must guarantee that the unit is safe to use.');
        }

        return DB::transaction(function () use ($user, $actionPlan) {
            $actionPlan->update([
                'status' => ActionPlanStatus::WaitingForFastConfirmation,
                'unit_safe_guaranteed' => true,
                'unit_safe_guaranteed_by' => $user->id,
                'unit_safe_guaranteed_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->syncPmsHeader($actionPlan->pmsDetail);

            $this->activityLogService->log(
                $user,
                'action_plan',
                'mark_implemented',
                $actionPlan->id,
                "Marked action plan {$actionPlan->action_plan_no} as implemented.",
            );

            $this->pmsNotificationService->notifyActionPlanImplemented($actionPlan);

            return $actionPlan->refresh()->load(['pmsDetail.pmsHeader', 'comments.creator']);
        });
    }

    public function confirm(User $user, ActionPlan $actionPlan): ActionPlan
    {
        if (! $user->isFastAdmin()) {
            throw new RuntimeException('Only FAST administrators can confirm action plans.');
        }

        if ($actionPlan->status !== ActionPlanStatus::WaitingForFastConfirmation) {
            throw new RuntimeException('Only action plans waiting for FAST confirmation can be confirmed.');
        }

        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        if ($actionPlan->pmsDetail?->pmsHeader?->isSubmitted() !== true) {
            throw new RuntimeException('Action plans can only be confirmed when the parent PMS is finalized.');
        }

        return DB::transaction(function () use ($user, $actionPlan) {
            $actionPlan->update([
                'status' => ActionPlanStatus::Confirmed,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->syncPmsHeader($actionPlan->pmsDetail);

            $this->activityLogService->log(
                $user,
                'action_plan',
                'confirm',
                $actionPlan->id,
                "Confirmed action plan {$actionPlan->action_plan_no}.",
            );

            $this->pmsNotificationService->notifyActionPlanConfirmed($actionPlan);

            return $actionPlan->refresh()->load(['pmsDetail.pmsHeader', 'comments.creator']);
        });
    }

    public function reject(User $user, ActionPlan $actionPlan, string $rejectionRemarks): ActionPlan
    {
        if (! $user->isFastAdmin()) {
            throw new RuntimeException('Only FAST administrators can reject action plans.');
        }

        if ($actionPlan->status !== ActionPlanStatus::WaitingForFastConfirmation) {
            throw new RuntimeException('Only action plans waiting for FAST confirmation can be rejected.');
        }

        if (blank($rejectionRemarks)) {
            throw new InvalidArgumentException('Rejection remarks are required.');
        }

        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        if ($actionPlan->pmsDetail?->pmsHeader?->isSubmitted() !== true) {
            throw new RuntimeException('Action plans can only be rejected when the parent PMS is finalized.');
        }

        return DB::transaction(function () use ($user, $actionPlan, $rejectionRemarks) {
            $actionPlan->update([
                'status' => ActionPlanStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_remarks' => $rejectionRemarks,
                'unit_safe_guaranteed' => false,
                'unit_safe_guaranteed_by' => null,
                'unit_safe_guaranteed_at' => null,
                'updated_by' => $user->id,
            ]);

            $this->syncPmsHeader($actionPlan->pmsDetail);

            $this->activityLogService->log(
                $user,
                'action_plan',
                'reject',
                $actionPlan->id,
                "Rejected action plan {$actionPlan->action_plan_no}.",
            );

            $this->pmsNotificationService->notifyActionPlanRejected($actionPlan, $rejectionRemarks);

            return $actionPlan->refresh()->load(['pmsDetail.pmsHeader', 'comments.creator']);
        });
    }

    public function cancel(User $user, ActionPlan $actionPlan): ActionPlan
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');
        $this->assertSupplierOwnership($user, $actionPlan->pmsDetail);

        if ($actionPlan->status === ActionPlanStatus::Cancelled) {
            throw new RuntimeException('This action plan is already cancelled.');
        }

        if ($actionPlan->status === ActionPlanStatus::Confirmed) {
            throw new RuntimeException('Confirmed action plans cannot be cancelled.');
        }

        return DB::transaction(function () use ($user, $actionPlan) {
            $actionPlan->update([
                'status' => ActionPlanStatus::Cancelled,
                'updated_by' => $user->id,
            ]);

            $this->syncPmsHeader($actionPlan->pmsDetail);

            $this->activityLogService->log(
                $user,
                'action_plan',
                'cancel',
                $actionPlan->id,
                "Cancelled action plan {$actionPlan->action_plan_no}.",
            );

            return $actionPlan->refresh()->load(['pmsDetail.pmsHeader', 'comments.creator']);
        });
    }

    public function destroy(User $user, ActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');
        $this->assertSupplierOwnership($user, $actionPlan->pmsDetail);

        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null || ! $pmsHeader->isDraft()) {
            throw new RuntimeException('Action plans can only be deleted while the PMS is a draft.');
        }

        DB::transaction(function () use ($user, $actionPlan, $pmsHeader): void {
            $actionPlanNo = $actionPlan->action_plan_no;
            $pmsDetail = $actionPlan->pmsDetail;

            $actionPlan->delete();

            if ($pmsDetail !== null) {
                $this->syncPmsHeader($pmsDetail);
            }

            $this->activityLogService->log(
                $user,
                'action_plan',
                'delete',
                $pmsHeader->id,
                "Deleted action plan {$actionPlanNo}.",
            );
        });
    }

    protected function syncPmsHeader(PmsDetail $pmsDetail): void
    {
        $pmsDetail->loadMissing('pmsHeader');

        if ($pmsDetail->pmsHeader !== null) {
            $this->pmsActionPlanStatusSyncService->sync($pmsDetail->pmsHeader);
        }
    }

    protected function assertSupplierOwnership(User $user, PmsDetail $pmsDetail): void
    {
        if (! $user->isSupplier()) {
            throw new RuntimeException('Only supplier users can manage action plans.');
        }

        $pmsDetail->loadMissing('pmsHeader');

        $pmsHeader = $pmsDetail->pmsHeader;

        if ($pmsHeader === null) {
            throw new RuntimeException('You are not allowed to manage this action plan.');
        }

        if (! $this->userDataScopeService->canAccessPmsHeader(
            $user,
            (int) $pmsHeader->supplier_id,
            (int) $pmsHeader->site_id,
        )) {
            throw new RuntimeException('You are not allowed to manage this action plan.');
        }
    }

    protected function assertFinding(PmsDetail $pmsDetail): void
    {
        if ($pmsDetail->answer !== ChecklistAnswer::NoGood) {
            throw new RuntimeException('Action plans can only be created for findings.');
        }
    }

    protected function assertEditableStatus(ActionPlan $actionPlan): void
    {
        if (! in_array($actionPlan->status, [ActionPlanStatus::Pending, ActionPlanStatus::Rejected], true)) {
            throw new RuntimeException('Only pending or rejected action plans can be updated.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function validateTimeline(array $data): void
    {
        if (! isset($data['timeline_from'], $data['timeline_to'])) {
            throw new InvalidArgumentException('Timeline From and Timeline To are required.');
        }

        if ($data['timeline_to'] < $data['timeline_from']) {
            throw new InvalidArgumentException('Timeline To must not be earlier than Timeline From.');
        }
    }
}
