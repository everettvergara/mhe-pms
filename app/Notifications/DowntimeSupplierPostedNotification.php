<?php

namespace App\Notifications;

use App\Models\MheDowntime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DowntimeSupplierPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected MheDowntime $downtime,
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
        $downtime = $this->downtime->loadMissing(['site', 'mheInventory', 'supplier']);
        $siteName = $downtime->site?->site_name ?? 'Unknown Site';
        $unitNo = $downtime->ref_unit_no ?? $downtime->mheInventory?->unit_no ?? '—';

        return (new MailMessage)
            ->subject('IMPORTANT: MHE Downtime Posted - '.$siteName.' - '.$downtime->title)
            ->greeting('Dear FAST Administrator,')
            ->line('A supplier has posted a new MHE downtime incident.')
            ->line('Trans ID: '.$downtime->id)
            ->line('Site: '.$siteName)
            ->line('Unit No.: '.$unitNo)
            ->line('When: '.$downtime->date_of_incident?->format('Y-m-d H:i'))
            ->line('Description: '.$downtime->description)
            ->action('View Downtime', route('mhe-downtimes.show', $downtime))
            ->line('Please log in to review this downtime record.');
    }
}
