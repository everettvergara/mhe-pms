<?php

namespace App\Services;

use App\Enums\DowntimeActionPlanStatus;
use App\Enums\ProgressStatus;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\MheDowntimeActionPlanComment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class MheDowntimeActionPlanService
{
    public function __construct(
        protected NumberSequenceService $numberSequenceService,
        protected ActivityLogService $activityLogService,
        protected UserDataScopeService $userDataScopeService,
        protected DowntimeNotificationService $downtimeNotificationService,
        protected MheDowntimeService $downtimeService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(MheDowntime $downtime, User $user, array $data): MheDowntimeActionPlan
    {
        $this->assertSupplierOnly($user);
        $this->assertDowntimeAccess($user, $downtime);
        $this->assertPostedDowntime($downtime);
        $this->validateTimeline($data);

        return DB::transaction(function () use ($downtime, $user, $data) {
            $actionPlan = $downtime->actionPlans()->create([
                'action_plan_no' => $this->numberSequenceService->nextNumber('downtime_action_plan'),
                'title' => $data['title'],
                'description' => $data['description'],
                'responsible_person' => $data['responsible_person'],
                'timeline_from' => $data['timeline_from'],
                'timeline_to' => $data['timeline_to'],
                'status' => DowntimeActionPlanStatus::Pending,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'create',
                $actionPlan->id,
                "Created action item {$actionPlan->action_plan_no} for downtime #{$downtime->id}.",
            );

            $this->downtimeNotificationService->notifyActionPlanCreated($actionPlan);
            $this->downtimeService->syncUptimeFromActionItems($downtime);

            return $actionPlan->load(['attachments', 'comments.creator']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(MheDowntimeActionPlan $actionPlan, User $user, array $data): MheDowntimeActionPlan
    {
        $this->assertSupplierOnly($user);
        $this->assertDowntimeAccess($user, $actionPlan->mheDowntime);
        $this->assertEditableStatus($actionPlan);
        $this->validateTimeline($data);

        return DB::transaction(function () use ($actionPlan, $user, $data) {
            $actionPlan->update([
                'title' => $data['title'] ?? $actionPlan->title,
                'description' => $data['description'] ?? $actionPlan->description,
                'responsible_person' => $data['responsible_person'] ?? $actionPlan->responsible_person,
                'timeline_from' => $data['timeline_from'] ?? $actionPlan->timeline_from,
                'timeline_to' => $data['timeline_to'] ?? $actionPlan->timeline_to,
                'updated_by' => $user->id,
            ]);

            if ($actionPlan->status === DowntimeActionPlanStatus::Rejected) {
                $actionPlan->update([
                    'status' => DowntimeActionPlanStatus::Pending,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'rejection_remarks' => null,
                ]);
            }

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'update',
                $actionPlan->id,
                "Updated action item {$actionPlan->action_plan_no}.",
            );

            return $actionPlan->refresh()->load(['attachments', 'comments.creator']);
        });
    }

    public function addComment(
        User $user,
        MheDowntimeActionPlan $actionPlan,
        string $comment,
        ProgressStatus $progressStatus,
    ): MheDowntimeActionPlanComment {
        $this->assertSupplierOnly($user);
        $this->assertDowntimeAccess($user, $actionPlan->mheDowntime);

        if (blank($comment)) {
            throw new InvalidArgumentException('Comment is required.');
        }

        return DB::transaction(function () use ($user, $actionPlan, $comment, $progressStatus) {
            $actionPlanComment = MheDowntimeActionPlanComment::query()->create([
                'mhe_downtime_action_plan_id' => $actionPlan->id,
                'comment' => $comment,
                'progress_status' => $progressStatus,
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'add_comment',
                $actionPlan->id,
                "Added progress comment to action item {$actionPlan->action_plan_no}.",
            );

            return $actionPlanComment->load('creator');
        });
    }

    public function markImplemented(MheDowntimeActionPlan $actionPlan, User $user): MheDowntimeActionPlan
    {
        $this->assertSupplierOnly($user);
        $this->assertDowntimeAccess($user, $actionPlan->mheDowntime);
        $this->assertPostedDowntime($actionPlan->mheDowntime);

        if (! in_array($actionPlan->status, [DowntimeActionPlanStatus::Pending, DowntimeActionPlanStatus::Rejected], true)) {
            throw new RuntimeException('Only pending or rejected action items can be marked as implemented.');
        }

        return DB::transaction(function () use ($actionPlan, $user) {
            $actionPlan->update([
                'status' => DowntimeActionPlanStatus::WaitingForFastConfirmation,
                'date_implemented' => now(),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'mark_implemented',
                $actionPlan->id,
                "Marked action item {$actionPlan->action_plan_no} as implemented.",
            );

            $this->downtimeNotificationService->notifyActionPlanImplemented($actionPlan);
            $this->downtimeService->syncUptimeFromActionItems($actionPlan->mheDowntime);

            return $actionPlan->refresh()->load(['attachments', 'comments.creator']);
        });
    }

    public function confirm(User $user, MheDowntimeActionPlan $actionPlan, Carbon $dateImplemented): MheDowntimeActionPlan
    {
        if (! $user->isFastAdmin()) {
            throw new RuntimeException('Only FAST administrators can confirm action items.');
        }

        if ($actionPlan->status !== DowntimeActionPlanStatus::WaitingForFastConfirmation) {
            throw new RuntimeException('Only action items waiting for FAST confirmation can be confirmed.');
        }

        $this->assertPostedDowntime($actionPlan->mheDowntime);

        return DB::transaction(function () use ($user, $actionPlan, $dateImplemented) {
            $actionPlan->update([
                'status' => DowntimeActionPlanStatus::Confirmed,
                'date_implemented' => $dateImplemented,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'confirm',
                $actionPlan->id,
                "Confirmed action item {$actionPlan->action_plan_no}.",
            );

            $this->downtimeNotificationService->notifyActionPlanConfirmed($actionPlan);
            $this->downtimeService->syncUptimeFromActionItems($actionPlan->mheDowntime);

            return $actionPlan->refresh()->load(['attachments', 'comments.creator', 'mheDowntime']);
        });
    }

    public function reject(User $user, MheDowntimeActionPlan $actionPlan, string $rejectionRemarks): MheDowntimeActionPlan
    {
        if (! $user->isFastAdmin()) {
            throw new RuntimeException('Only FAST administrators can reject action items.');
        }

        if ($actionPlan->status !== DowntimeActionPlanStatus::WaitingForFastConfirmation) {
            throw new RuntimeException('Only action items waiting for FAST confirmation can be rejected.');
        }

        if (blank($rejectionRemarks)) {
            throw new InvalidArgumentException('Rejection remarks are required.');
        }

        $this->assertPostedDowntime($actionPlan->mheDowntime);

        return DB::transaction(function () use ($user, $actionPlan, $rejectionRemarks) {
            $actionPlan->update([
                'status' => DowntimeActionPlanStatus::Rejected,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_remarks' => $rejectionRemarks,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'reject',
                $actionPlan->id,
                "Rejected action item {$actionPlan->action_plan_no}.",
            );

            $this->downtimeNotificationService->notifyActionPlanRejected($actionPlan, $rejectionRemarks);
            $this->downtimeService->syncUptimeFromActionItems($actionPlan->mheDowntime);

            return $actionPlan->refresh()->load(['attachments', 'comments.creator', 'mheDowntime']);
        });
    }

    public function cancel(MheDowntimeActionPlan $actionPlan, User $user): MheDowntimeActionPlan
    {
        $this->assertSupplierOnly($user);
        $this->assertDowntimeAccess($user, $actionPlan->mheDowntime);

        if ($actionPlan->status === DowntimeActionPlanStatus::Cancelled) {
            throw new RuntimeException('This action item is already cancelled.');
        }

        if ($actionPlan->status === DowntimeActionPlanStatus::Confirmed) {
            throw new RuntimeException('Confirmed action items cannot be cancelled.');
        }

        return DB::transaction(function () use ($actionPlan, $user) {
            $actionPlan->update([
                'status' => DowntimeActionPlanStatus::Cancelled,
                'updated_by' => $user->id,
            ]);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'cancel',
                $actionPlan->id,
                "Cancelled action item {$actionPlan->action_plan_no}.",
            );

            $this->downtimeService->syncUptimeFromActionItems($actionPlan->mheDowntime);

            return $actionPlan->refresh()->load(['attachments', 'comments.creator']);
        });
    }

    public function delete(MheDowntimeActionPlan $actionPlan, User $user): void
    {
        $this->assertSupplierOnly($user);
        $this->assertDowntimeAccess($user, $actionPlan->mheDowntime);

        if ($actionPlan->status !== DowntimeActionPlanStatus::Pending) {
            throw new RuntimeException('Only pending action items can be deleted.');
        }

        DB::transaction(function () use ($actionPlan, $user) {
            $actionPlanNo = $actionPlan->action_plan_no;
            $downtime = $actionPlan->mheDowntime;
            $actionPlan->update(['updated_by' => $user->id]);
            $actionPlan->delete();
            $this->downtimeService->syncUptimeFromActionItems($downtime);

            $this->activityLogService->log(
                $user,
                'mhe_downtime_action_plans',
                'delete',
                $actionPlan->id,
                "Deleted action item {$actionPlanNo}.",
            );
        });
    }

    protected function assertSupplierOnly(User $user): void
    {
        if (! $user->isSupplier()) {
            throw new RuntimeException('Only supplier users can manage downtime action items.');
        }
    }

    protected function assertDowntimeAccess(User $user, MheDowntime $downtime): void
    {
        if (! $this->userDataScopeService->canAccessMheDowntime(
            $user,
            (int) $downtime->site_id,
            $downtime->supplier_id,
        )) {
            throw new InvalidArgumentException('You do not have access to this downtime record.');
        }
    }

    protected function assertPostedDowntime(MheDowntime $downtime): void
    {
        if (! $downtime->isPosted()) {
            throw new RuntimeException('Action items can only be managed on posted downtime records.');
        }
    }

    protected function assertEditableStatus(MheDowntimeActionPlan $actionPlan): void
    {
        if (! in_array($actionPlan->status, [DowntimeActionPlanStatus::Pending, DowntimeActionPlanStatus::Rejected], true)) {
            throw new RuntimeException('Only pending or rejected action items can be updated.');
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
