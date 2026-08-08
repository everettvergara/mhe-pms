<?php

namespace App\Notifications;

use App\Enums\WorkflowNotificationEvent;
use App\Models\MheDowntimeActionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DowntimeActionPlanWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MheDowntimeActionPlan $actionPlan,
        protected WorkflowNotificationEvent $event,
        protected ?string $rejectionRemarks = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actionPlan = $this->actionPlan->loadMissing(['mheDowntime.site', 'mheDowntime.mheInventory']);
        $downtime = $actionPlan->mheDowntime;
        $siteName = $downtime?->site?->site_name ?? 'Unknown Site';
        $unitNo = $downtime?->ref_unit_no ?? $downtime?->mheInventory?->unit_no ?? '—';

        $message = (new MailMessage)
            ->subject($this->subject($siteName, $actionPlan->action_plan_no))
            ->greeting($this->greeting())
            ->line($this->introLine())
            ->line('Action Plan No.: '.$actionPlan->action_plan_no)
            ->line('Title: '.$actionPlan->title)
            ->line('Downtime Trans ID: '.($downtime?->id ?? '—'))
            ->line('Site: '.$siteName)
            ->line('Unit No.: '.$unitNo);

        if ($this->event === WorkflowNotificationEvent::ActionPlanRejected && filled($this->rejectionRemarks)) {
            $message->line('Rejection Remarks: '.$this->rejectionRemarks);
        }

        return $message
            ->action($this->actionLabel(), route('mhe-downtimes.show', $downtime))
            ->line($this->closingLine());
    }

    protected function subject(string $siteName, string $actionPlanNo): string
    {
        return match ($this->event) {
            WorkflowNotificationEvent::ActionPlanCreated => "MHE Downtime Action Plan Created - {$siteName} - {$actionPlanNo}",
            WorkflowNotificationEvent::ActionPlanImplemented => "MHE Downtime Action Plan Implemented - {$siteName} - {$actionPlanNo}",
            WorkflowNotificationEvent::ActionPlanConfirmed => "MHE Downtime Action Plan Confirmed - {$siteName} - {$actionPlanNo}",
            WorkflowNotificationEvent::ActionPlanRejected => "MHE Downtime Action Plan Rejected - {$siteName} - {$actionPlanNo}",
        };
    }

    protected function greeting(): string
    {
        return match ($this->event) {
            WorkflowNotificationEvent::ActionPlanCreated,
            WorkflowNotificationEvent::ActionPlanImplemented => 'Dear FAST Administrator,',
            WorkflowNotificationEvent::ActionPlanConfirmed,
            WorkflowNotificationEvent::ActionPlanRejected => 'Dear Supplier Incharge,',
        };
    }

    protected function introLine(): string
    {
        return match ($this->event) {
            WorkflowNotificationEvent::ActionPlanCreated => 'A supplier has created a new action plan for an MHE downtime incident.',
            WorkflowNotificationEvent::ActionPlanImplemented => 'A supplier has marked an MHE downtime action plan as implemented and it is waiting for your confirmation.',
            WorkflowNotificationEvent::ActionPlanConfirmed => 'Your MHE downtime action plan has been confirmed by FAST.',
            WorkflowNotificationEvent::ActionPlanRejected => 'Your MHE downtime action plan has been rejected by FAST.',
        };
    }

    protected function actionLabel(): string
    {
        return match ($this->event) {
            WorkflowNotificationEvent::ActionPlanCreated,
            WorkflowNotificationEvent::ActionPlanImplemented => 'Review Action Plan',
            WorkflowNotificationEvent::ActionPlanConfirmed,
            WorkflowNotificationEvent::ActionPlanRejected => 'View Action Plan',
        };
    }

    protected function closingLine(): string
    {
        return match ($this->event) {
            WorkflowNotificationEvent::ActionPlanCreated => 'Please log in to review the new action plan.',
            WorkflowNotificationEvent::ActionPlanImplemented => 'Please log in to confirm or reject this action plan.',
            WorkflowNotificationEvent::ActionPlanConfirmed => 'Please log in to view the confirmed action plan.',
            WorkflowNotificationEvent::ActionPlanRejected => 'Please log in to review the rejection remarks and update the action plan.',
        };
    }
}
