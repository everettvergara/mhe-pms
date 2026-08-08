<?php

namespace App\Services;

use App\Enums\WorkflowNotificationEvent;
use App\Models\MheDowntime;
use App\Models\MheDowntimeActionPlan;
use App\Models\User;
use App\Notifications\DowntimeActionPlanWorkflowNotification;
use App\Notifications\DowntimePostedNotification;
use App\Notifications\DowntimeSupplierPostedNotification;

class DowntimeNotificationService
{
    public function __construct(
        protected SupplierInchargeResolver $supplierInchargeResolver,
        protected FastAdminResolver $fastAdminResolver,
        protected WorkflowNotificationDispatcher $dispatcher,
    ) {}

    public function notifyPosted(MheDowntime $downtime, User $actor): void
    {
        $downtime->loadMissing(['site', 'mheInventory', 'supplier']);

        if ($actor->isFastAdmin()) {
            $this->dispatcher->notifyUsers(
                $this->supplierInchargeResolver->resolve($downtime),
                new DowntimePostedNotification($downtime),
                'downtime_posted_supplier',
                ['downtime_id' => $downtime->id, 'site_id' => $downtime->site_id, 'supplier_id' => $downtime->supplier_id],
            );

            return;
        }

        if ($actor->isSupplier()) {
            $this->dispatcher->notifyUsers(
                $this->fastAdminResolver->resolveForSite($downtime->site_id),
                new DowntimeSupplierPostedNotification($downtime),
                'downtime_posted_fast',
                ['downtime_id' => $downtime->id, 'site_id' => $downtime->site_id],
            );
        }
    }

    public function notifyActionPlanCreated(MheDowntimeActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('mheDowntime');

        $this->dispatcher->notifyUsers(
            $this->fastAdminResolver->resolveForSite($actionPlan->mheDowntime?->site_id),
            new DowntimeActionPlanWorkflowNotification($actionPlan, WorkflowNotificationEvent::ActionPlanCreated),
            'downtime_action_plan_created',
            ['action_plan_id' => $actionPlan->id, 'downtime_id' => $actionPlan->mhe_downtime_id],
        );
    }

    public function notifyActionPlanImplemented(MheDowntimeActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('mheDowntime');

        $this->dispatcher->notifyUsers(
            $this->fastAdminResolver->resolveForSite($actionPlan->mheDowntime?->site_id),
            new DowntimeActionPlanWorkflowNotification($actionPlan, WorkflowNotificationEvent::ActionPlanImplemented),
            'downtime_action_plan_implemented',
            ['action_plan_id' => $actionPlan->id, 'downtime_id' => $actionPlan->mhe_downtime_id],
        );
    }

    public function notifyActionPlanConfirmed(MheDowntimeActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('mheDowntime');

        $downtime = $actionPlan->mheDowntime;

        if ($downtime === null) {
            return;
        }

        $this->dispatcher->notifyUsers(
            $this->supplierInchargeResolver->resolve($downtime),
            new DowntimeActionPlanWorkflowNotification($actionPlan, WorkflowNotificationEvent::ActionPlanConfirmed),
            'downtime_action_plan_confirmed',
            ['action_plan_id' => $actionPlan->id, 'downtime_id' => $downtime->id],
        );
    }

    public function notifyActionPlanRejected(MheDowntimeActionPlan $actionPlan, string $rejectionRemarks): void
    {
        $actionPlan->loadMissing('mheDowntime');

        $downtime = $actionPlan->mheDowntime;

        if ($downtime === null) {
            return;
        }

        $this->dispatcher->notifyUsers(
            $this->supplierInchargeResolver->resolve($downtime),
            new DowntimeActionPlanWorkflowNotification(
                $actionPlan,
                WorkflowNotificationEvent::ActionPlanRejected,
                $rejectionRemarks,
            ),
            'downtime_action_plan_rejected',
            ['action_plan_id' => $actionPlan->id, 'downtime_id' => $downtime->id],
        );
    }
}
