<?php

namespace App\Notifications;

use App\Models\MheDowntime;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DowntimePostedNotification extends Notification implements ShouldQueue
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
            ->subject('IMPORTANT: MHE Downtime - '.$siteName.' - '.$downtime->title)
            ->greeting('Dear Supplier Incharge,')
            ->line('A new MHE downtime incident has been posted and requires an action plan.')
            ->line('Trans ID: '.$downtime->id)
            ->line('Site: '.$siteName)
            ->line('Unit No.: '.$unitNo)
            ->line('When: '.$downtime->date_of_incident?->format('Y-m-d H:i'))
            ->line('Description: '.$downtime->description)
            ->action('View Downtime & Create Action Plan', route('mhe-downtimes.show', $downtime))
            ->line('Please log in and create an action plan for this downtime.');
    }
}
