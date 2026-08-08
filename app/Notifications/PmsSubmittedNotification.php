<?php

namespace App\Notifications;

use App\Models\PmsHeader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PmsSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PmsHeader $pmsHeader,
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
        $pmsHeader = $this->pmsHeader->loadMissing(['site', 'supplier', 'mheType']);
        $siteName = $pmsHeader->site?->site_name ?? 'Unknown Site';

        return (new MailMessage)
            ->subject('PMS Submitted - '.$siteName.' - '.$pmsHeader->pms_no)
            ->greeting('Dear FAST Administrator,')
            ->line('A supplier has submitted a preventive maintenance record.')
            ->line('PMS No.: '.$pmsHeader->pms_no)
            ->line('Site: '.$siteName)
            ->line('Unit No.: '.($pmsHeader->unit_number ?: '—'))
            ->line('Status: '.$pmsHeader->status->value)
            ->action('View PMS', route('pms.show', $pmsHeader))
            ->line('Please log in to review this PMS record.');
    }
}
