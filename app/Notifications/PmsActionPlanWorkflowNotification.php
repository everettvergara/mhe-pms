<?php

namespace App\Notifications;

use App\Enums\WorkflowNotificationEvent;
use App\Models\ActionPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PmsActionPlanWorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ActionPlan $actionPlan,
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
        $actionPlan = $this->actionPlan->loadMissing(['pmsDetail.pmsHeader.site', 'pmsDetail.pmsHeader.mheType']);
        $pmsHeader = $actionPlan->pmsDetail?->pmsHeader;
        $siteName = $pmsHeader?->site?->site_name ?? 'Unknown Site';

        $message = (new MailMessage)
            ->subject($this->subject($siteName, $actionPlan->action_plan_no))
            ->greeting($this->greeting())
            ->line($this->introLine())
            ->line('Action Plan No.: '.$actionPlan->action_plan_no)
            ->line('Title: '.$actionPlan->title)
            ->line('PMS No.: '.($pmsHeader?->pms_no ?? '—'))
            ->line('Site: '.$siteName)
            ->line('Unit No.: '.($pmsHeader?->unit_number ?: '—'));

        if ($this->event === WorkflowNotificationEvent::ActionPlanRejected && filled($this->rejectionRemarks)) {
            $message->line('Rejection Remarks: '.$this->rejectionRemarks);
        }

        $actionUrl = $actionPlan->parentShowUrl() ?? route('action-plans.show', $actionPlan);

        return $message
            ->action($this->actionLabel(), $actionUrl)
            ->line($this->closingLine());
    }

    protected function subject(string $siteName, string $actionPlanNo): string
    {
        return match ($this->event) {
            WorkflowNotificationEvent::ActionPlanCreated => "PMS Action Plan Created - {$siteName} - {$actionPlanNo}",
            WorkflowNotificationEvent::ActionPlanImplemented => "PMS Action Plan Implemented - {$siteName} - {$actionPlanNo}",
            WorkflowNotificationEvent::ActionPlanConfirmed => "PMS Action Plan Confirmed - {$siteName} - {$actionPlanNo}",
            WorkflowNotificationEvent::ActionPlanRejected => "PMS Action Plan Rejected - {$siteName} - {$actionPlanNo}",
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
            WorkflowNotificationEvent::ActionPlanCreated => 'A supplier has created a new action plan for a PMS finding.',
            WorkflowNotificationEvent::ActionPlanImplemented => 'A supplier has marked a PMS action plan as implemented and it is waiting for your confirmation.',
            WorkflowNotificationEvent::ActionPlanConfirmed => 'Your PMS action plan has been confirmed by FAST.',
            WorkflowNotificationEvent::ActionPlanRejected => 'Your PMS action plan has been rejected by FAST.',
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
