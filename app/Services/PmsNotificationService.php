<?php

namespace App\Services;

use App\Enums\WorkflowNotificationEvent;
use App\Models\ActionPlan;
use App\Models\PmsHeader;
use App\Notifications\PmsActionPlanWorkflowNotification;
use App\Notifications\PmsSubmittedNotification;

class PmsNotificationService
{
    public function __construct(
        protected SupplierInchargeResolver $supplierInchargeResolver,
        protected FastAdminResolver $fastAdminResolver,
        protected WorkflowNotificationDispatcher $dispatcher,
    ) {}

    public function notifySubmitted(PmsHeader $pmsHeader): void
    {
        $pmsHeader->loadMissing(['site', 'supplier']);

        $this->dispatcher->notifyUsers(
            $this->fastAdminResolver->resolveForSite($pmsHeader->site_id),
            new PmsSubmittedNotification($pmsHeader),
            'pms_submitted',
            ['pms_id' => $pmsHeader->id, 'site_id' => $pmsHeader->site_id],
        );
    }

    public function notifyActionPlanCreated(ActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null) {
            return;
        }

        $this->dispatcher->notifyUsers(
            $this->fastAdminResolver->resolveForSite($pmsHeader->site_id),
            new PmsActionPlanWorkflowNotification($actionPlan, WorkflowNotificationEvent::ActionPlanCreated),
            'pms_action_plan_created',
            ['action_plan_id' => $actionPlan->id, 'pms_id' => $pmsHeader->id],
        );
    }

    public function notifyActionPlanImplemented(ActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null) {
            return;
        }

        $this->dispatcher->notifyUsers(
            $this->fastAdminResolver->resolveForSite($pmsHeader->site_id),
            new PmsActionPlanWorkflowNotification($actionPlan, WorkflowNotificationEvent::ActionPlanImplemented),
            'pms_action_plan_implemented',
            ['action_plan_id' => $actionPlan->id, 'pms_id' => $pmsHeader->id],
        );
    }

    public function notifyActionPlanConfirmed(ActionPlan $actionPlan): void
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null) {
            return;
        }

        $this->dispatcher->notifyUsers(
            $this->supplierInchargeResolver->resolveFromPmsHeader($pmsHeader),
            new PmsActionPlanWorkflowNotification($actionPlan, WorkflowNotificationEvent::ActionPlanConfirmed),
            'pms_action_plan_confirmed',
            ['action_plan_id' => $actionPlan->id, 'pms_id' => $pmsHeader->id],
        );
    }

    public function notifyActionPlanRejected(ActionPlan $actionPlan, string $rejectionRemarks): void
    {
        $actionPlan->loadMissing('pmsDetail.pmsHeader');

        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;

        if ($pmsHeader === null) {
            return;
        }

        $this->dispatcher->notifyUsers(
            $this->supplierInchargeResolver->resolveFromPmsHeader($pmsHeader),
            new PmsActionPlanWorkflowNotification(
                $actionPlan,
                WorkflowNotificationEvent::ActionPlanRejected,
                $rejectionRemarks,
            ),
            'pms_action_plan_rejected',
            ['action_plan_id' => $actionPlan->id, 'pms_id' => $pmsHeader->id],
        );
    }
}
